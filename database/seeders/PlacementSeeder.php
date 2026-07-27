<?php

namespace Database\Seeders;

use App\Models\Placement;
use Illuminate\Database\Seeder;

class PlacementSeeder extends Seeder
{
    public function run(): void
    {
        $placements = [
            'Upper Arm',
            'Forearm',
            'Shoulder',
            'Back',
            'Chest',
            'Ribs',
            'Thigh',
            'Calf',
            'Ankle',
            'Wrist',
            'Neck',
            'Hand',
            'Not Sure',
        ];

        foreach ($placements as $name) {
            $placement = Placement::query()->firstOrCreate(
                ['name' => $name],
                [
                    'status' => 'active',
                    'sort_order' => 0,
                    'appear_on_question' => true,
                ]
            );

            if ((int) $placement->sort_order !== (int) $placement->id) {
                $placement->update(['sort_order' => $placement->id]);
            }
        }
    }
}
