<?php

namespace App\Http\Requests;

use App\Models\UserDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $userName = ltrim(trim((string) $this->input('user_name')), '@');
        $mobileRaw = trim((string) $this->input('mobile_number'));

        $mobileNumber = $mobileRaw;
        if ($mobileRaw !== '' && str_starts_with($mobileRaw, '+')) {
            $mobileNumber = '+'.preg_replace('/\D/', '', substr($mobileRaw, 1));
        }

        $this->merge([
            'user_name' => $userName,
            'mobile_number' => $mobileNumber,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var \App\Models\User $user */
        $user = $this->user();
        $userDetail = $user ? $user->userDetail : null;

        return [
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,heif,heic', 'max:2048'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'user_name' => [
                'required',
                'string',
                'min:1',
                'max:30',
                'regex:/^[a-zA-Z0-9._]+$/',
                Rule::unique(UserDetail::class, 'user_name')->ignore($userDetail?->id),
            ],
            'mobile_number' => [
                'required',
                'string',
                'regex:/^\+[1-9]\d{1,14}$/',
                Rule::unique(UserDetail::class, 'mobile_number')->ignore($userDetail?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_name.regex' => 'Username may only contain letters, numbers, periods (.), and underscores (_), with no spaces or other symbols.',
            'user_name.max' => 'Username may not be longer than 30 characters.',
            'mobile_number.regex' => 'Enter a valid E.164 phone number: start with +, then country code and digits only (no spaces, dashes, or parentheses).',
        ];
    }
}
