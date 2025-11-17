<?php

namespace Database\Seeders;

use App\Models\InkJinArtist;
use App\Models\InkJinTattoo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InkJinDataSeeder extends Seeder
{
    /**
     * Google Sheets IDs
     */
    private const ARTIST_SHEET_ID = '1n3AMmofSsFj1wh-CpqgQp4KU-yeOQOJRUPVm_HX2U3w';
    private const TATTOO_SHEET_ID = '1fNF9uL4fUxDa0IBn4bSjjDVKqxGXJqMQz2VOA9r-O_I';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting InkJin data import from Google Sheets...');

        // Import artists first
        $this->importArtists();

        // Import tattoos
        $this->importTattoos();

        $this->command->info('Data import completed!');
    }

    /**
     * Import artists from Google Sheets
     */
    private function importArtists(): void
    {
        $this->command->info('Importing artists...');

        try {
            $csvData = $this->downloadCsvFromGoogleSheets(self::ARTIST_SHEET_ID);
            $rows = $this->parseCsv($csvData);

            if (empty($rows)) {
                $this->command->warn('No artist data found in Google Sheets.');
                return;
            }

            // Get header row
            $headers = array_shift($rows);
            $headers = array_map('trim', $headers);

            $this->command->info("Found " . count($rows) . " artists to import.");

            $imported = 0;
            $skipped = 0;

            foreach ($rows as $rowIndex => $row) {
                try {
                    // Combine headers with row data
                    $data = array_combine($headers, $row);

                    // Map Google Sheets columns to database columns
                    $artistData = $this->mapArtistData($data);

                    if (empty($artistData['Artist_Handle']) || empty($artistData['Email'])) {
                        $this->command->warn("Skipping row " . ($rowIndex + 2) . ": Missing Artist_Handle or Email");
                        $skipped++;
                        continue;
                    }

                    // Check if artist already exists
                    $existing = InkJinArtist::where('Artist_Handle', $artistData['Artist_Handle'])
                        ->orWhere('Email', $artistData['Email'])
                        ->first();

                    if ($existing) {
                        // Update existing artist
                        $existing->update($artistData);
                        $this->command->line("Updated artist: " . $artistData['Artist_Handle']);
                    } else {
                        // Create new artist
                        InkJinArtist::create($artistData);
                        $this->command->line("Created artist: " . $artistData['Artist_Handle']);
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $this->command->error("Error importing artist row " . ($rowIndex + 2) . ": " . $e->getMessage());
                    Log::error("Artist import error", [
                        'row' => $rowIndex + 2,
                        'error' => $e->getMessage(),
                        'data' => $row
                    ]);
                    $skipped++;
                }
            }

            $this->command->info("Artists import completed: {$imported} imported, {$skipped} skipped.");
        } catch (\Exception $e) {
            $this->command->error("Failed to import artists: " . $e->getMessage());
            Log::error("Artist import failed", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Import tattoos from Google Sheets
     */
    private function importTattoos(): void
    {
        $this->command->info('Importing tattoos...');

        try {
            $csvData = $this->downloadCsvFromGoogleSheets(self::TATTOO_SHEET_ID);
            $rows = $this->parseCsv($csvData);

            if (empty($rows)) {
                $this->command->warn('No tattoo data found in Google Sheets.');
                return;
            }

            // Get header row
            $headers = array_shift($rows);
            $headers = array_map('trim', $headers);

            $this->command->info("Found " . count($rows) . " tattoos to import.");

            $imported = 0;
            $skipped = 0;

            foreach ($rows as $rowIndex => $row) {
                try {
                    // Combine headers with row data
                    $data = array_combine($headers, $row);

                    // Map Google Sheets columns to database columns
                    $tattooData = $this->mapTattooData($data);

                    if (empty($tattooData['Artist_Handle']) || empty($tattooData['Title'])) {
                        $this->command->warn("Skipping row " . ($rowIndex + 2) . ": Missing Artist_Handle or Title");
                        $skipped++;
                        continue;
                    }

                    // Verify artist exists
                    $artist = InkJinArtist::where('Artist_Handle', $tattooData['Artist_Handle'])->first();
                    if (!$artist) {
                        $this->command->warn("Skipping row " . ($rowIndex + 2) . ": Artist not found: " . $tattooData['Artist_Handle']);
                        $skipped++;
                        continue;
                    }

                    // Check if tattoo already exists (by Title and Artist_Handle)
                    $existing = InkJinTattoo::where('Artist_Handle', $tattooData['Artist_Handle'])
                        ->where('Title', $tattooData['Title'])
                        ->first();

                    if ($existing) {
                        // Update existing tattoo
                        $existing->update($tattooData);
                        $this->command->line("Updated tattoo: " . $tattooData['Title']);
                    } else {
                        // Create new tattoo
                        InkJinTattoo::create($tattooData);
                        $this->command->line("Created tattoo: " . $tattooData['Title']);
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $this->command->error("Error importing tattoo row " . ($rowIndex + 2) . ": " . $e->getMessage());
                    Log::error("Tattoo import error", [
                        'row' => $rowIndex + 2,
                        'error' => $e->getMessage(),
                        'data' => $row
                    ]);
                    $skipped++;
                }
            }

            $this->command->info("Tattoos import completed: {$imported} imported, {$skipped} skipped.");
        } catch (\Exception $e) {
            $this->command->error("Failed to import tattoos: " . $e->getMessage());
            Log::error("Tattoo import failed", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Download CSV from Google Sheets
     */
    private function downloadCsvFromGoogleSheets(string $sheetId): string
    {
        // Google Sheets CSV export URL (exports first sheet by default)
        $url = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv";

        $this->command->info("Downloading data from Google Sheets: {$sheetId}");

        $response = Http::timeout(60)->get($url);

        if (!$response->successful()) {
            throw new \Exception("Failed to download CSV from Google Sheets. Status: " . $response->status());
        }

        return $response->body();
    }

    /**
     * Parse CSV data
     */
    private function parseCsv(string $csvData): array
    {
        $lines = explode("\n", $csvData);
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Parse CSV line (handles quoted fields)
            $row = str_getcsv($line);
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Map Google Sheets artist data to database columns
     */
    private function mapArtistData(array $data): array
    {
        // Normalize keys (case-insensitive, handle spaces/underscores)
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[strtolower(str_replace([' ', '_'], '', $key))] = $value;
        }

        return [
            'Artist_Handle' => $this->getValue($normalized, ['artisthandle', 'handle', 'username']),
            'Visibility' => $this->getValue($normalized, ['visibility', 'visible']),
            'Email' => $this->getValue($normalized, ['email', 'e-mail']),
            'First_Name' => $this->getValue($normalized, ['firstname', 'first_name', 'first']),
            'Last_Name' => $this->getValue($normalized, ['lastname', 'last_name', 'last']),
            'Mobile_Phone' => $this->getValue($normalized, ['mobilephone', 'mobile_phone', 'phone', 'mobile']),
            'Nickname' => $this->getValue($normalized, ['nickname', 'nick']),
            'Profile_Name' => $this->getValue($normalized, ['profilename', 'profile_name', 'displayname', 'display_name', 'name']),
            'City' => $this->getValue($normalized, ['city']),
            'State_Province' => $this->getValue($normalized, ['stateprovince', 'state_province', 'state', 'province']),
            'Country' => $this->getValue($normalized, ['country']),
            'Style' => $this->getValue($normalized, ['style', 'primarystyle', 'primary_style']),
            'Other_Styles' => $this->getValue($normalized, ['otherstyles', 'other_styles', 'styles']),
            'Since' => $this->getValue($normalized, ['since', 'tattooingsince', 'tattooing_since']),
            'Studio' => $this->getValue($normalized, ['studio', 'studioname', 'studio_name']),
            'Instagram' => $this->getValue($normalized, ['instagram', 'insta']),
            'Tiktok' => $this->getValue($normalized, ['tiktok', 'tik-tok']),
            'Website' => $this->getValue($normalized, ['website', 'web', 'url']),
            'Artist_Dashboard_Signup' => $this->getBooleanValue($normalized, ['artistdashboardsignup', 'artist_dashboard_signup', 'dashboardsignup']),
        ];
    }

    /**
     * Map Google Sheets tattoo data to database columns
     */
    private function mapTattooData(array $data): array
    {
        // Normalize keys (case-insensitive, handle spaces/underscores)
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[strtolower(str_replace([' ', '_'], '', $key))] = $value;
        }

        return [
            'Artist_Handle' => $this->getValue($normalized, ['artisthandle', 'handle', 'username', 'artist']),
            'Type' => $this->getValue($normalized, ['type']),
            'Visibility' => $this->getValue($normalized, ['visibility', 'visible']),
            'Filename' => $this->getValue($normalized, ['filename', 'file_name', 'image', 'file']),
            'INK' => $this->getBooleanValue($normalized, ['ink']),
            'AR' => $this->getBooleanValue($normalized, ['ar', 'augmentedreality']),
            'Repeatable' => $this->getBooleanValue($normalized, ['repeatable', 'repeat']),
            'Sensitive' => $this->getBooleanValue($normalized, ['sensitive', 'mature']),
            'Title' => $this->getValue($normalized, ['title', 'name']),
            'Description' => $this->getValue($normalized, ['description', 'desc']),
            'Primary_Style' => $this->getValue($normalized, ['primarystyle', 'primary_style', 'style']),
            'Other_Styles' => $this->getValue($normalized, ['otherstyles', 'other_styles', 'styles']),
            'Suggested_Placement' => $this->getValue($normalized, ['suggestedplacement', 'suggested_placement', 'placement']),
            'Color' => $this->getValue($normalized, ['color', 'colour']),
            'Tags' => $this->getValue($normalized, ['tags', 'tag']),
            'Price' => $this->getNumericValue($normalized, ['price']),
            'Max_Price' => $this->getNumericValue($normalized, ['maxprice', 'max_price']),
            'Size_Height' => $this->getNumericValue($normalized, ['sizeheight', 'size_height', 'height']),
            'Size_Width' => $this->getNumericValue($normalized, ['sizewidth', 'size_width', 'width']),
            'Cost_per_Session' => $this->getNumericValue($normalized, ['costpersession', 'cost_per_session', 'sessioncost']),
            'Min_Sessions' => $this->getNumericValue($normalized, ['minsessions', 'min_sessions', 'minsession']),
            'Max_Sessions' => $this->getNumericValue($normalized, ['maxsessions', 'max_sessions', 'maxsession']),
            'Session' => $this->getValue($normalized, ['session', 'sessions']),
            'Time_h' => $this->getNumericValue($normalized, ['timeh', 'time_h', 'time', 'hours']),
            'Currency' => $this->getValue($normalized, ['currency', 'curr']),
            'Price_model' => $this->getValue($normalized, ['pricemodel', 'price_model', 'pricingmodel']),
            'Notes' => $this->getValue($normalized, ['notes', 'note']),
        ];
    }

    /**
     * Get value from normalized array with multiple possible keys
     */
    private function getValue(array $normalized, array $possibleKeys): ?string
    {
        foreach ($possibleKeys as $key) {
            if (isset($normalized[$key]) && !empty($normalized[$key])) {
                return trim($normalized[$key]);
            }
        }
        return null;
    }

    /**
     * Get boolean value from normalized array
     */
    private function getBooleanValue(array $normalized, array $possibleKeys): bool
    {
        $value = $this->getValue($normalized, $possibleKeys);
        if ($value === null) {
            return false;
        }

        $value = strtolower(trim($value));
        return in_array($value, ['1', 'true', 'yes', 'y', 'on']);
    }

    /**
     * Get numeric value from normalized array
     */
    private function getNumericValue(array $normalized, array $possibleKeys): ?float
    {
        $value = $this->getValue($normalized, $possibleKeys);
        if ($value === null || $value === '') {
            return null;
        }

        // Remove currency symbols and commas
        $value = preg_replace('/[^0-9.]/', '', $value);
        
        return $value !== '' ? (float) $value : null;
    }
}
