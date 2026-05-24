<?php

namespace App\Http\Requests;

use App\Models\CustomRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCustomRequestClientSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CustomRequest|null $customRequest */
        $customRequest = $this->route('customRequest');

        return $customRequest
            && (int) $customRequest->user_id === (int) $this->user()->id
            && $customRequest->canAccessConfirmTimesPage()
            && $customRequest->usesArtistOfferedSlotsPicker();
    }

    public function rules(): array
    {
        return [
            'client_session_slots' => ['required', 'array'],
            'client_session_slots.0.date' => ['required', 'date'],
            'client_session_slots.0.ranges' => ['required', 'array', 'min:1'],
            'client_session_slots.0.ranges.0.from' => ['required', 'date_format:H:i'],
            'client_session_slots.0.ranges.0.to' => ['required', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var CustomRequest $customRequest */
            $customRequest = $this->route('customRequest');
            $this->assertSelectionMatchesOffer(
                $validator,
                'client_session_slots',
                $customRequest->offeredSlotsMapForPicker(),
                'Tattoo session'
            );
        });
    }

    /**
     * @param  array<string, array<int, array{from: string, to: string}>>  $offeredMap
     */
    private function assertSelectionMatchesOffer(
        Validator $validator,
        string $field,
        array $offeredMap,
        string $label
    ): void {
        $selection = $this->input($field, []);
        $date = (string) ($selection[0]['date'] ?? '');
        $from = substr((string) ($selection[0]['ranges'][0]['from'] ?? ''), 0, 5);
        $to = substr((string) ($selection[0]['ranges'][0]['to'] ?? ''), 0, 5);

        if (!isset($offeredMap[$date])) {
            $validator->errors()->add($field, "{$label}: selected date is not among the artist's offered dates.");

            return;
        }

        $valid = false;
        foreach ($offeredMap[$date] as $range) {
            if ($range['from'] === $from && $range['to'] === $to) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            $validator->errors()->add($field, "{$label}: selected time is not among the artist's offered windows.");
        }
    }

    /**
     * @return array{client_session_slots: array<int, array{date: string, ranges: array<int, array{from: string, to: string}>}>}
     */
    public function normalizedPayload(): array
    {
        $input = $this->input('client_session_slots', []);
        $date = trim((string) ($input[0]['date'] ?? ''));
        $from = substr(trim((string) ($input[0]['ranges'][0]['from'] ?? '')), 0, 5);
        $to = substr(trim((string) ($input[0]['ranges'][0]['to'] ?? '')), 0, 5);

        return [
            'client_session_slots' => [['date' => $date, 'ranges' => [['from' => $from, 'to' => $to]]]],
        ];
    }
}
