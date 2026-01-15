<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CountriesController extends Controller
{
    /**
     * Get all countries
     */
    public function getCountries()
    {
        try {
            $data = $this->loadCountriesData();
            
            // Extract unique countries and sort them
            $countries = [];
            foreach ($data as $item) {
                if (isset($item['country']) && !in_array($item['country'], $countries)) {
                    $countries[] = $item['country'];
                }
            }
            sort($countries);
            
            return response()->json([
                'success' => true,
                'data' => $countries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load countries: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get cities for a specific country
     */
    public function getCities(Request $request, $country = null)
    {
        try {
            // Get country from route parameter or request input
            $country = $country ?? $request->input('country');
            
            // URL decode the country name in case it's encoded
            $country = urldecode($country);
            
            if (!$country) {
                return response()->json([
                    'success' => false,
                    'message' => 'Country parameter is required'
                ], 400);
            }
            
            $data = $this->loadCountriesData();
            
            // Find the country and return its cities (case-insensitive comparison)
            foreach ($data as $item) {
                if (isset($item['country']) && strcasecmp(trim($item['country']), trim($country)) === 0) {
                    $cities = isset($item['cities']) && is_array($item['cities']) ? $item['cities'] : [];
                    sort($cities);
                    
                    return response()->json([
                        'success' => true,
                        'data' => $cities,
                        'country' => $item['country']
                    ]);
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Country not found: ' . $country
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load cities: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all countries with their cities
     */
    public function getAll()
    {
        try {
            $data = $this->loadCountriesData();
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load data: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Load countries data from JSON file
     */
    private function loadCountriesData()
    {
        $filePath = storage_path('app/data/countries-cities.json');
        
        if (!File::exists($filePath)) {
            // Return empty array if file doesn't exist
            return [];
        }
        
        $json = File::get($filePath);
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON data: ' . json_last_error_msg());
        }
        
        return $data;
    }
}

