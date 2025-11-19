<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InkjinArtistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $artists = [
            [
                'artist_handle' => 'inkmaster_alex',
                'visibility' => 'public',
                'email' => 'alex@inkstudio.com',
                'first_name' => 'Alex',
                'last_name' => 'Martinez',
                'mobile_phone' => '+1-555-0101',
                'nickname' => 'InkMaster',
                'profile_name' => 'Alex Martinez - Ink Master',
                'city' => 'Los Angeles',
                'state_province' => 'California',
                'country' => 'USA',
                'style' => 'Realism',
                'other_styles' => 'Portrait, Black and Grey, Color Realism',
                'since' => '2015',
                'studio' => 'Elite Ink Studio, 123 Main St, Los Angeles, CA 90001',
                'instagram' => '@inkmaster_alex',
                'tiktok' => '@inkmaster_alex',
                'website' => 'https://inkmasteralex.com',
                'artist_dashboard_signup' => '2020-01-15',
            ],
            [
                'artist_handle' => 'tattoo_sarah',
                'visibility' => 'public',
                'email' => 'sarah@artisticink.com',
                'first_name' => 'Sarah',
                'last_name' => 'Chen',
                'mobile_phone' => '+1-555-0102',
                'nickname' => 'Artistic Sarah',
                'profile_name' => 'Sarah Chen - Artistic Ink',
                'city' => 'New York',
                'state_province' => 'New York',
                'country' => 'USA',
                'style' => 'Watercolor',
                'other_styles' => 'Abstract, Floral, Geometric',
                'since' => '2018',
                'studio' => 'Artistic Ink Studio, 456 Broadway, New York, NY 10013',
                'instagram' => '@tattoo_sarah',
                'tiktok' => '@tattoo_sarah',
                'website' => 'https://artisticsarah.com',
                'artist_dashboard_signup' => '2020-03-20',
            ],
            [
                'artist_handle' => 'dark_ink_mike',
                'visibility' => 'public',
                'email' => 'mike@darkinkstudio.com',
                'first_name' => 'Mike',
                'last_name' => 'Thompson',
                'mobile_phone' => '+1-555-0103',
                'nickname' => 'Dark Ink Mike',
                'profile_name' => 'Mike Thompson - Dark Ink',
                'city' => 'Chicago',
                'state_province' => 'Illinois',
                'country' => 'USA',
                'style' => 'Traditional',
                'other_styles' => 'American Traditional, Neo-Traditional, Japanese',
                'since' => '2012',
                'studio' => 'Dark Ink Studio, 789 State St, Chicago, IL 60601',
                'instagram' => '@dark_ink_mike',
                'tiktok' => '@dark_ink_mike',
                'website' => 'https://darkinkmike.com',
                'artist_dashboard_signup' => '2019-11-10',
            ],
            [
                'artist_handle' => 'minimalist_emma',
                'visibility' => 'public',
                'email' => 'emma@minimalink.com',
                'first_name' => 'Emma',
                'last_name' => 'Wilson',
                'mobile_phone' => '+1-555-0104',
                'nickname' => 'Minimalist Emma',
                'profile_name' => 'Emma Wilson - Minimal Ink',
                'city' => 'Portland',
                'state_province' => 'Oregon',
                'country' => 'USA',
                'style' => 'Minimalist',
                'other_styles' => 'Fine Line, Single Needle, Dotwork',
                'since' => '2019',
                'studio' => 'Minimal Ink Studio, 321 Pearl St, Portland, OR 97204',
                'instagram' => '@minimalist_emma',
                'tiktok' => '@minimalist_emma',
                'website' => 'https://minimalinkemma.com',
                'artist_dashboard_signup' => '2021-05-12',
            ],
            [
                'artist_handle' => 'colorful_james',
                'visibility' => 'public',
                'email' => 'james@colorfulink.com',
                'first_name' => 'James',
                'last_name' => 'Rodriguez',
                'mobile_phone' => '+1-555-0105',
                'nickname' => 'Colorful James',
                'profile_name' => 'James Rodriguez - Colorful Ink',
                'city' => 'Miami',
                'state_province' => 'Florida',
                'country' => 'USA',
                'style' => 'Color',
                'other_styles' => 'Neo-Traditional, New School, Japanese Color',
                'since' => '2016',
                'studio' => 'Colorful Ink Studio, 654 Ocean Dr, Miami, FL 33139',
                'instagram' => '@colorful_james',
                'tiktok' => '@colorful_james',
                'website' => 'https://colorfuljames.com',
                'artist_dashboard_signup' => '2020-07-08',
            ],
        ];

        foreach ($artists as $artist) {
            DB::table('inkjin_artists')->insert(array_merge($artist, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('Seeded 5 artists successfully!');
    }
}
