<?php

namespace App\Http\Requests;

use App\Models\BookingRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreClientSelectedSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BookingRequest|null $bookingRequest */
        $bookingRequest = $this->route('bookingRequest');

        return $bookingRequest
            && (int) $bookingRequest->user_id === (int) $this->user()->id
            && $bookingRequest->canAccessConfirmTimesPage();
    }

    public function rules(): array
    {
        /** @var BookingRequest $bookingRequest */
        $bookingRequest = $this->route('bookingRequest');

        $rules = [
            'client_session_slots' => ['required', 'array'],
            'client_session_slots.0.date' => ['required', 'date'],
            'client_session_slots.0.ranges' => ['required', 'array', 'min:1'],
            'client_session_slots.0.ranges.0.from' => ['required', 'date_format:H:i'],
            'client_session_slots.0.ranges.0.to' => ['required', 'date_format:H:i'],
        ];

        if ($bookingRequest->requiresConsultationPick()) {
            $rules['client_consultation_slots'] = ['required', 'array'];
            $rules['client_consultation_slots.0.date'] = ['required', 'date'];
            $rules['client_consultation_slots.0.ranges'] = ['required', 'array', 'min:1'];
            $rules['client_consultation_slots.0.ranges.0.from'] = ['required', 'date_format:H:i'];
            $rules['client_consultation_slots.0.ranges.0.to'] = ['required', 'date_format:H:i'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var BookingRequest $bookingRequest */
            $bookingRequest = $this->route('bookingRequest');

            $this->assertSelectionMatchesOffer(
                $validator,
                'client_session_slots',
                $bookingRequest->offeredSlotsMapForPicker('session'),
                'Tattoo session'
            );

            if ($bookingRequest->requiresConsultationPick()) {
                $this->assertSelectionMatchesOffer(
                    $validator,
                    'client_consultation_slots',
                    $bookingRequest->offeredSlotsMapForPicker('consult'),
                    'Consultation'
                );
            }
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
     * @return array{
     *     client_session_slots: array<int, array{date: string, ranges: array<int, array{from: string, to: string}>}>,
     *     client_consultation_slots: array<int, array{date: string, ranges: array<int, array{from: string, to: string}>}>|null
     * }
     */
    public function normalizedPayload(BookingRequest $bookingRequest): array
    {
        return [
            'client_session_slots' => $this->normalizeSingleSelection($this->input('client_session_slots', [])),
            'client_consultation_slots' => $bookingRequest->requiresConsultationPick()
                ? $this->normalizeSingleSelection($this->input('client_consultation_slots', []))
                : null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $input
     * @return array<int, array{date: string, ranges: array<int, array{from: string, to: string}>}>
     */
    private function normalizeSingleSelection(array $input): array
    {
        $date = trim((string) ($input[0]['date'] ?? ''));
        $from = substr(trim((string) ($input[0]['ranges'][0]['from'] ?? '')), 0, 5);
        $to = substr(trim((string) ($input[0]['ranges'][0]['to'] ?? '')), 0, 5);

        return [['date' => $date, 'ranges' => [['from' => $from, 'to' => $to]]]];
    }
}
