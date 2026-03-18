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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'user_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(UserDetail::class, 'user_name')->ignore($userDetail?->id),
            ],
            'mobile_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique(UserDetail::class, 'mobile_number')->ignore($userDetail?->id),
            ],
        ];
    }
}
