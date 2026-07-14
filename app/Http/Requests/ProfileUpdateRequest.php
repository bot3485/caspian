<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Enums\UserInterest;
use App\Enums\UserCountry; // <-- Импортируем наш Enum стран
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
public function rules(): array
    {
        return [
            // Добавляем 'sometimes', чтобы правила проверялись только если поля переданы в запросе
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 
                'required', 
                'string', 
                'lowercase', 
                'email', 
                'max:255', 
                Rule::unique(User::class)->ignore($this->user()->id)
            ],
            'interests' => ['nullable', 'array'],
            'interests.*' => [Rule::enum(UserInterest::class)],
            
            'target_country' => [
                'nullable', 
                'string', 
                Rule::in(array_merge(['global'], array_column(UserCountry::cases(), 'value')))
            ],
        ];
    }
}