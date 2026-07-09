<?php

namespace App\Rules;

use App\Services\ReservedArtistHandleService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ReservedArtistUsername implements ValidationRule
{
    public function __construct(private readonly string $userEmail) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $service = app(ReservedArtistHandleService::class);

        if (! $service->canUseUsername($this->userEmail, (string) $value)) {
            $fail('This username is already taken.');
        }
    }
}
