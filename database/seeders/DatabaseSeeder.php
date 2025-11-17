<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@inkjin.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'on_boarding' => 'yes',
            'email_verified_at' => now(),
        ]);

        // Seed app_users and app_tattoos tables
        // $this->call(AppUsersAndTattoosSeeder::class);
        
        // Seed artists from provided data
        $this->call(InkJinArtistsDataSeeder::class);
        
        // Seed tattoos from tatoo.txt file
        $this->call(InkJinTattoosDataSeeder::class);
        
        // Seed artists and tattoos from Google Sheets (alternative)
        // Uncomment the line below to import data from Google Sheets
        // $this->call(InkJinDataSeeder::class);
    }
}
