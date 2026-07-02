<?php

namespace Database\Seeders;

use App\Models\Style;
use Illuminate\Database\Seeder;

class StyleSeeder extends Seeder
{
    public function run(): void
    {
        $styles = [
            'Blackwork',
            'Color',
            'Traditional (Old School)',
            'Japanese / Irezumi',
            'Fine Line',
            'Realism / Black & Grey Realism',
            'Neo-Traditional',
            'Minimalist',
            'Geometric',
            'Script/Lettering',
            'Watercolor',
            'Tribal',
            'New School',
            'Portrait',
            'Biomechanical',
            'Dotwork',
            'Sketch Style',
            'Trash Polka',
            'Chicano',
            'Ornamental',
            'Illustrative',
            'Micro Realism',
            'Abstract Linework',
            'Surrealism',
            'Cybersigilism',
            'Patchwork',
            'Linework (single-line)',
            'Anime',
            'Engraving',
            'Handpoke',
            'Cartoon & Comics',
            'Ignorant',
            'Floral',
            'Contemporary',
            'Streetstyle',
            'Dark Art & Horror',
        ];

        foreach ($styles as $name) {
            $style = Style::query()->firstOrCreate(
                ['name' => $name],
                [
                    'status' => 'active',
                    'sort_order' => 0,
                    'appear_on_question' => true,
                ]
            );

            if ((int) $style->sort_order !== (int) $style->id) {
                $style->update(['sort_order' => $style->id]);
            }
        }
    }
}
