<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            [
                'label' => 'Tiny',
                'cm_min' => null,
                'cm_max' => 5,
                'in_min' => null,
                'in_max' => 2,
            ],
            [
                'label' => 'Small',
                'cm_min' => 5,
                'cm_max' => 10,
                'in_min' => 2,
                'in_max' => 4,
            ],
            [
                'label' => 'Medium',
                'cm_min' => 10,
                'cm_max' => 20,
                'in_min' => 4,
                'in_max' => 8,
            ],
            [
                'label' => 'Large',
                'cm_min' => 20,
                'cm_max' => 35,
                'in_min' => 8,
                'in_max' => 14,
            ],
            [
                'label' => 'Extra Large',
                'cm_min' => 35,
                'cm_max' => null,
                'in_min' => 14,
                'in_max' => null,
            ],
        ];

        foreach ($sizes as $index => $row) {
            $size = Size::query()->updateOrCreate(
                ['label' => $row['label']],
                [
                    'cm_min' => $row['cm_min'],
                    'cm_max' => $row['cm_max'],
                    'in_min' => $row['in_min'],
                    'in_max' => $row['in_max'],
                    'status' => 'active',
                    'sort_order' => $index + 1,
                ]
            );

            // Keep sort_order stable after first seed if IDs differ.
            if ((int) $size->sort_order !== ($index + 1)) {
                $size->update(['sort_order' => $index + 1]);
            }
        }
    }
}
