<?php

namespace Database\Seeders;

use App\Models\InkJinArtist;
use App\Models\InkJinTattoo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class InkJinTattoosDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting InkJin tattoos data import from CSV...');

        $tattoosData = $this->getTattoosDataFromCsv();

        if (empty($tattoosData)) {
            $this->command->warn('No tattoo data found.');
            return;
        }

        $this->command->info("Found " . count($tattoosData) . " tattoos to import.");

        $imported = 0;
        $skipped = 0;
        $updated = 0;

        foreach ($tattoosData as $index => $data) {
            try {
                if (empty($data['title']) || empty($data['author_username'])) {
                    $this->command->warn("Skipping row " . ($index + 1) . ": Missing title or author_username");
                    $skipped++;
                    continue;
                }

                // Verify artist exists
                $artist = InkJinArtist::where('username', $data['author_username'])->first();
                if (!$artist) {
                    $this->command->warn("Skipping row " . ($index + 1) . ": Artist not found: " . $data['author_username']);
                    $skipped++;
                    continue;
                }

                // Clean up and map data
                $tattooData = [
                    'tattoo_id' => $this->parseNumeric($data['tattoo_id'] ?? null),
                    'title' => trim($data['title']),
                    'image' => !empty($data['image']) ? trim($data['image']) : null,
                    'tags' => !empty($data['tags']) ? trim($data['tags']) : null,
                    'color' => !empty($data['color']) ? trim($data['color']) : null,
                    'primary_style' => !empty($data['primary_style']) ? trim($data['primary_style']) : null,
                    'style' => !empty($data['style']) ? trim($data['style']) : null,
                    'suggested_placement' => !empty($data['suggested_placement']) ? trim($data['suggested_placement']) : null,
                    'available_to_ink' => isset($data['available_to_ink']) ? (bool) $data['available_to_ink'] : false,
                    'available_to_ar' => isset($data['available_to_ar']) ? (bool) $data['available_to_ar'] : false,
                    'mature_content' => isset($data['mature_content']) ? (bool) $data['mature_content'] : false,
                    'status' => !empty($data['status']) ? trim($data['status']) : null,
                    'liked_by_current_user' => isset($data['liked_by_current_user']) ? (bool) $data['liked_by_current_user'] : false,
                    'author_id' => $this->parseNumeric($data['author_id'] ?? null),
                    'author_username' => trim($data['author_username']),
                    'author_display_name' => !empty($data['author_display_name']) ? trim($data['author_display_name']) : null,
                    'author_profile_picture' => !empty($data['author_profile_picture']) ? trim($data['author_profile_picture']) : null,
                ];

                // Check if tattoo already exists (by tattoo_id or title + author_username)
                $existing = null;
                if ($tattooData['tattoo_id']) {
                    $existing = InkJinTattoo::where('tattoo_id', $tattooData['tattoo_id'])->first();
                }
                
                if (!$existing) {
                    $existing = InkJinTattoo::where('title', $tattooData['title'])
                        ->where('author_username', $tattooData['author_username'])
                        ->first();
                }

                if ($existing) {
                    // Update existing tattoo
                    $existing->update($tattooData);
                    $this->command->line("Updated tattoo: " . $tattooData['title'] . " by " . $tattooData['author_username']);
                    $updated++;
                } else {
                    // Create new tattoo
                    InkJinTattoo::create($tattooData);
                    $this->command->line("Created tattoo: " . $tattooData['title'] . " by " . $tattooData['author_username']);
                    $imported++;
                }
            } catch (\Exception $e) {
                $this->command->error("Error importing tattoo row " . ($index + 1) . ": " . $e->getMessage());
                Log::error("Tattoo import error", [
                    'row' => $index + 1,
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
                $skipped++;
            }
        }

        $this->command->info("Tattoos import completed: {$imported} created, {$updated} updated, {$skipped} skipped.");
    }

    /**
     * Get tattoos data from CSV file
     */
    private function getTattoosDataFromCsv(): array
    {
        $filePath = base_path('inkjin_tattoos - Copy.csv');

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

        $tattoos = [];

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

            $tattoos[] = $data;
        }

        return $tattoos;
    }

    /**
     * Parse numeric value, return null if empty or invalid
     */
    private function parseNumeric(?string $value): ?int
    {
        if ($value === null || $value === '' || trim($value) === '') {
            return null;
        }

        // Remove any non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', trim($value));
        
        return $cleaned !== '' ? (int) $cleaned : null;
    }
}
