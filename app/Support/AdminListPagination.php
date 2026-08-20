<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminListPagination
{
    /** @var list<int> */
    public const OPTIONS = [10, 25, 50, 100];

    public const DEFAULT = 10;

    public static function perPage(Request $request): int
    {
        $value = (int) $request->input('per_page', self::DEFAULT);

        return in_array($value, self::OPTIONS, true) ? $value : self::DEFAULT;
    }

    /**
     * @return array{per_page: int}
     */
    public static function validated(Request $request): array
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', Rule::in(self::OPTIONS)],
        ]);

        $perPage = (int) ($validated['per_page'] ?? self::DEFAULT);

        return [
            'per_page' => in_array($perPage, self::OPTIONS, true) ? $perPage : self::DEFAULT,
        ];
    }
}
