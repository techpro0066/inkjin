<?php

namespace Database\Seeders;

use App\Models\InkJinArtist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class InkJinArtistsDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting InkJin artists data import from CSV...');

        $artistsData = $this->getArtistsDataFromCsv();

        if (empty($artistsData)) {
            $this->command->warn('No artist data found.');
            return;
        }

        $this->command->info("Found " . count($artistsData) . " artists to import.");

        $imported = 0;
        $skipped = 0;
        $updated = 0;

        foreach ($artistsData as $index => $data) {
            try {
                if (empty($data['username']) || empty($data['email'])) {
                    $this->command->warn("Skipping row " . ($index + 1) . ": Missing username or email");
                    $skipped++;
                    continue;
                }

                // Clean up and map data
                $artistData = [
                    'user_id' => $this->parseNumeric($data['user_id'] ?? null),
                    'username' => trim($data['username']),
                    'display_name' => !empty($data['display_name']) ? trim($data['display_name']) : null,
                    'profile_id' => $this->parseNumeric($data['profile_id'] ?? null),
                    'email' => trim($data['email']),
                    'phone' => !empty($data['phone']) ? $this->cleanPhone($data['phone']) : null,
                    'instagram' => !empty($data['instagram']) ? trim($data['instagram']) : null,
                    'tiktok' => !empty($data['tiktok']) ? trim($data['tiktok']) : null,
                    'website' => !empty($data['website']) ? trim($data['website']) : null,
                    'studio' => !empty($data['studio']) ? trim($data['studio']) : null,
                    'primary_style' => !empty($data['primary_style']) ? trim($data['primary_style']) : null,
                    'style' => !empty($data['style']) ? trim($data['style']) : null,
                    'tattooing_since' => !empty($data['tattooing_since']) ? trim($data['tattooing_since']) : null,
                    'description' => !empty($data['description']) ? trim($data['description']) : null,
                    'address_number' => !empty($data['address_number']) ? trim($data['address_number']) : null,
                    'address_street' => !empty($data['address_street']) ? trim($data['address_street']) : null,
                    'city' => !empty($data['city']) ? trim($data['city']) : null,
                    'country' => !empty($data['country']) ? trim($data['country']) : null,
                    'followers_count' => $this->parseNumeric($data['followers_count'] ?? 0) ?? 0,
                    'tattoo_count' => $this->parseNumeric($data['tattoo_count'] ?? 0) ?? 0,
                    'allow_messages' => isset($data['allow_messages']) ? (bool) $data['allow_messages'] : true,
                    'profile_picture' => !empty($data['profile_picture']) ? trim($data['profile_picture']) : null,
                    'created_date' => !empty($data['created_date']) ? $this->parseDate($data['created_date']) : null,
                ];

                // Check if artist already exists
                $existing = InkJinArtist::where('username', $artistData['username'])
                    ->orWhere('email', $artistData['email'])
                    ->first();

                if ($existing) {
                    // Update existing artist
                    $existing->update($artistData);
                    $this->command->line("Updated artist: " . $artistData['username']);
                    $updated++;
                } else {
                    // Create new artist
                    InkJinArtist::create($artistData);
                    $this->command->line("Created artist: " . $artistData['username']);
                    $imported++;
                }
            } catch (\Exception $e) {
                $this->command->error("Error importing artist row " . ($index + 1) . ": " . $e->getMessage());
                Log::error("Artist import error", [
                    'row' => $index + 1,
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
                $skipped++;
            }
        }

        $this->command->info("Artists import completed: {$imported} created, {$updated} updated, {$skipped} skipped.");
    }

    /**
     * Get artists data from CSV file
     */
    private function getArtistsDataFromCsv(): array
    {
        $filePath = base_path('inkjin_artists.csv');

        if (!File::exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return [];
        }

        $content = File::get($filePath);
        $lines = explode("\n", trim($content));
        
        if (empty($lines)) {
            return [];
        }

        // Get header row
        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);

        $artists = [];

        foreach ($lines as $lineIndex => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Parse CSV line
            $row = str_getcsv($line);
            
            if (count($row) < count($headers)) {
                // Pad with empty strings if row is shorter
                $row = array_pad($row, count($headers), '');
            }

            // Combine headers with row data
            $data = array_combine($headers, array_slice($row, 0, count($headers)));
            
            if ($data === false) {
                $this->command->warn("Skipping line " . ($lineIndex + 2) . ": Column count mismatch");
                continue;
            }

            $artists[] = $data;
        }

        return $artists;
    }

    /**
     * Parse numeric value, return null if empty or invalid
     */
    private function parseNumeric(?string $value): ?int
    {
        if ($value === null || $value === '' || trim($value) === '') {
            return null;
        }

        // Handle scientific notation (e.g., 3.06973E+11)
        if (stripos($value, 'e') !== false) {
            $value = (string) (float) $value;
        }

        // Remove any non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', trim($value));
        
        return $cleaned !== '' ? (int) (float) $cleaned : null;
    }

    /**
     * Clean phone number (handle scientific notation)
     */
    private function cleanPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        // Handle scientific notation (e.g., 3.06973E+11)
        if (stripos($phone, 'e') !== false) {
            $phone = (string) (float) $phone;
        }

        return trim($phone);
    }

    /**
     * Parse date from various formats (MM/DD/YYYY, YYYY-MM-DD, etc.)
     */
    private function parseDate(?string $date): ?string
    {
        if ($date === null || $date === '' || trim($date) === '') {
            return null;
        }

        $date = trim($date);

        // Try to parse common date formats
        // Format: MM/DD/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
            $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "{$year}-{$month}-{$day}";
        }

        // Format: YYYY-MM-DD (already correct)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Try strtotime as fallback
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }
}
