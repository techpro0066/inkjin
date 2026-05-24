<?php

namespace App\Http\Requests;

use App\Models\CustomRequest;
use App\Support\ArtistSessionSlots;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendCustomRequestQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CustomRequest|null $customRequest */
        $customRequest = $this->route('customRequest');

        return $customRequest
            && (int) $customRequest->artist_id === (int) $this->user()->id;
    }

    public function rules(): array
    {
        /** @var CustomRequest $customRequest */
        $customRequest = $this->route('customRequest');

        $rules = [
            'estimated_price' => ['required', 'numeric', 'min:0.01'],
            'estimated_time' => ['required', 'string', 'max:255'],
            'number_of_sessions' => ['required', 'string', 'max:255'],
            'message_for_client' => ['required', 'string', 'min:5', 'max:2000'],
        ];

        if ($customRequest->isManagedRequest()) {
            $rules['artist_session_slots'] = ['required', 'array', 'min:1'];
            $rules['artist_session_slots.*.date'] = ['required', 'date', 'after_or_equal:today'];
            $rules['artist_session_slots.*.ranges'] = ['required', 'array', 'min:1'];
            $rules['artist_session_slots.*.ranges.*.from'] = ['required', 'date_format:H:i'];
            $rules['artist_session_slots.*.ranges.*.to'] = ['required', 'date_format:H:i'];
        } else {
            $rules['artist_session_slots'] = ['nullable', 'array'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'artist_session_slots.required' => 'Add at least one tattoo session date with time windows.',
            'artist_session_slots.min' => 'Add at least one tattoo session date with time windows.',
            'artist_session_slots.*.date.after_or_equal' => 'Session dates must be today or in the future.',
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
            if (!$customRequest->isManagedRequest()) {
                return;
            }

            ArtistSessionSlots::validateCollection(
                $validator,
                $this->input('artist_session_slots', []),
                'artist_session_slots',
                'Tattoo session'
            );
        });
    }

    /**
     * @return array{
     *     estimated_price: float,
     *     estimated_time: string,
     *     number_of_sessions: string,
     *     message_for_client: string,
     *     artist_session_slots: array<int, array{date: string, ranges: array<int, array{from: string, to: string}>}>|null
     * }
     */
    public function normalizedPayload(): array
    {
        /** @var CustomRequest $customRequest */
        $customRequest = $this->route('customRequest');

        $payload = [
            'estimated_price' => round((float) $this->input('estimated_price'), 2),
            'estimated_time' => trim((string) $this->input('estimated_time')),
            'number_of_sessions' => trim((string) $this->input('number_of_sessions')),
            'message_for_client' => trim((string) $this->input('message_for_client')),
            'artist_session_slots' => null,
        ];

        if ($customRequest->isManagedRequest()) {
            $payload['artist_session_slots'] = ArtistSessionSlots::normalize(
                $this->input('artist_session_slots', [])
            );
        }

        return $payload;
    }
}
