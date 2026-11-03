<?php

namespace App\Rules;

use App\Models\Studio;
use App\Models\UserDetail;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ensures a public handle is free across both artist (user_details.user_name)
 * and studio (studios.username) namespaces.
 */
class UniquePublicUsername implements ValidationRule
{
    public function __construct(
        private readonly ?int $ignoreUserDetailId = null,
        private readonly ?int $ignoreStudioId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $username = ltrim(trim((string) $value), '@');
        if ($username === '') {
            return;
        }

        $key = strtolower($username);

        $takenByArtist = UserDetail::query()
            ->whereRaw('LOWER(user_name) = ?', [$key])
            ->when(
                $this->ignoreUserDetailId !== null,
                fn ($query) => $query->where('id', '!=', $this->ignoreUserDetailId)
            )
            ->exists();

        if ($takenByArtist) {
            $fail('This username is already taken.');

            return;
        }

        $takenByStudio = Studio::query()
            ->whereRaw('LOWER(username) = ?', [$key])
            ->when(
                $this->ignoreStudioId !== null,
                fn ($query) => $query->where('id', '!=', $this->ignoreStudioId)
            )
            ->exists();

        if ($takenByStudio) {
            $fail('This username is already taken.');
        }
    }
}
