<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionSorting;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [
                'order' => 1,
                'question' => 'Where on your body?',
                'description' => 'Choose a placement for your tattoo.',
                'placeholder' => null,
                'type' => 'radio',
                'options' => ['Upper Arm', 'Forearm', 'Shoulder', 'Back', 'Chest', 'Ribs', 'Thigh', 'Calf', 'Ankle', 'Wrist', 'Neck', 'Hand', 'Not Sure'],
                'max_images' => null,
                'is_required' => true,
                'form_context' => 'default',
            ],
            [
                'order' => 2,
                'question' => 'What size?',
                'description' => 'How big should this tattoo be?',
                'placeholder' => null,
                'type' => 'sizes',
                'options' => null,
                'max_images' => null,
                'is_required' => true,
                'form_context' => 'default',
            ],
            [
                'order' => 3,
                'question' => 'Is this your first tattoo?',
                'description' => null,
                'placeholder' => null,
                'type' => 'radio',
                'options' => ['Yes', 'No'],
                'max_images' => null,
                'is_required' => false,
                'form_context' => 'default',
            ],
            [
                'order' => 4,
                'question' => 'Any health concerns we should know about?',
                'description' => 'Allergies, skin conditions, medications or anything relevant for your safety.',
                'placeholder' => 'e.g. I am allergic to latex. I have eczema on my arms…',
                'type' => 'textarea',
                'options' => null,
                'max_images' => null,
                'is_required' => false,
                'form_context' => 'default',
            ],
            [
                'order' => 5,
                'question' => 'Anything else you want to share?',
                'description' => 'Special requests, questions for the artist…',
                'placeholder' => 'e.g. I’d like to change the design slightly...',
                'type' => 'textarea',
                'options' => null,
                'max_images' => null,
                'is_required' => false,
                'form_context' => 'default',
            ],
            [
                'order' => 6,
                'question' => 'Snap a quick photo of the spot where you want to get it',
                'description' => 'This helps us get sizing and skin tone right. Just the area you want to ink, no need to show your face.',
                'placeholder' => 'Add a photo of the placement',
                'type' => 'image',
                'options' => null,
                'max_images' => 5,
                'is_required' => true,
                'form_context' => 'default',
            ],
            [
                'order' => 7,
                'question' => 'What kind of tattoo are you looking for?',
                'description' => 'Describe your idea, the story behind it, or anything that helps the artist understand your vision.',
                'placeholder' => 'A Japanese-style dragon wrapping around my forearm with cherry blossoms…',
                'type' => 'textarea',
                'options' => null,
                'max_images' => null,
                'is_required' => true,
                'form_context' => 'custom',
            ],
            [
                'order' => 8,
                'question' => 'Have any reference images?',
                'description' => 'Upload photos, screenshots, or sketches that show what you\'re going for.',
                'placeholder' => null,
                'type' => 'image',
                'options' => null,
                'max_images' => 5,
                'is_required' => false,
                'form_context' => 'custom',
            ],
            [
                'order' => 9,
                'question' => 'What style are you thinking?',
                'description' => 'Pick the style that best matches your vision.',
                'placeholder' => null,
                'type' => 'radio',
                'options' => ['Blackwork', 'Color', 'Traditional (Old School)', 'Japanese', 'Fine Line', 'Realism', 'Neo-Traditional', 'Minimalist', 'Geometric', 'Tribal', 'Watercolor', 'Not Sure'],
                'max_images' => null,
                'is_required' => true,
                'form_context' => 'custom',
            ],
            [
                'order' => 10,
                'question' => 'Color preference?',
                'description' => 'How would you like your tattoo colored?',
                'placeholder' => null,
                'type' => 'radio',
                'options' => ['🎨 Full Color', '🖤 Black & Grey', '⬛ Black Only', '🤷 Not Sure'],
                'max_images' => null,
                'is_required' => false,
                'form_context' => 'custom',
            ],
            [
                'order' => 11,
                'question' => 'Where on your body?',
                'description' => 'Choose a placement for your tattoo.',
                'placeholder' => null,
                'type' => 'radio',
                'options' => ['Upper Arm', 'Forearm', 'Shoulder', 'Back', 'Chest', 'Ribs', 'Thigh', 'Calf', 'Ankle', 'Wrist', 'Neck', 'Hand', 'Not Sure'],
                'max_images' => null,
                'is_required' => true,
                'form_context' => 'custom',
            ],
            [
                'order' => 12,
                'question' => 'What size?',
                'description' => 'How big should this tattoo be?',
                'placeholder' => null,
                'type' => 'sizes',
                'options' => null,
                'max_images' => null,
                'is_required' => true,
                'form_context' => 'custom',
            ],
            [
                'order' => 13,
                'question' => 'What\'s your budget range?',
                'description' => 'This helps the artist scope the work. No commitment.',
                'placeholder' => null,
                'type' => 'radio',
                'options' => ['Under €100', '€100 – €300', '€300 – €500', '€500 – €1,000', '€1,000+', 'Not Sure'],
                'max_images' => null,
                'is_required' => false,
                'form_context' => 'custom',
            ],
            [
                'order' => 14,
                'question' => 'Is this your first tattoo?',
                'description' => null,
                'placeholder' => null,
                'type' => 'radio',
                'options' => ['Yes', 'No'],
                'max_images' => null,
                'is_required' => false,
                'form_context' => 'custom',
            ],
            [
                'order' => 15,
                'question' => 'Any health concerns we should know about?',
                'description' => 'Allergies, skin conditions, medications or anything relevant for your safety.',
                'placeholder' => 'e.g. I am allergic to latex. I have eczema on my arms…',
                'type' => 'textarea',
                'options' => null,
                'max_images' => null,
                'is_required' => false,
                'form_context' => 'custom',
            ],
            [
                'order' => 16,
                'question' => 'Snap a quick photo of the spot where you want to get it',
                'description' => 'This helps us get sizing and skin tone right. Just the area you want to ink, no need to show your face.',
                'placeholder' => 'Add a photo of the placement',
                'type' => 'image',
                'options' => null,
                'max_images' => 5,
                'is_required' => true,
                'form_context' => 'custom',
            ],
        ];

        foreach ($questions as $row) {
            $order = $row['order'];
            unset($row['order']);

            $question = Question::query()->updateOrCreate(
                [
                    'user_id' => 1,
                    'form_context' => $row['form_context'],
                    'question' => $row['question'],
                ],
                $row
            );

            QuestionSorting::query()->updateOrCreate(
                [
                    'user_id' => 1,
                    'question_id' => $question->id,
                ],
                [
                    'order' => $order,
                    'is_active' => true,
                ]
            );
        }
    }
}
