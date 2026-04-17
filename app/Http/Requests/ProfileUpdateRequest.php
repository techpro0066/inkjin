<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\UserDetail;

class ProfileUpdateRequest extends FormRequest
{
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
                'regex:/^[A-Za-z0-9._]+$/',
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
            'user_name.regex' => 'Username can only include letters, numbers, periods (.) and underscores (_).',
            'user_name.max' => 'Username must not be greater than 30 characters.',
            'mobile_number.regex' => 'Mobile number must be in E.164 format (example: +447911123456) with no spaces, dashes, or parentheses.',
        ];
    }
}
