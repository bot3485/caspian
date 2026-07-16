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
            'locale' => ['nullable', 'string', 'in:en,ru,tr'],
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

        // ДОБАВЛЯЕМ ПРАВИЛА ДЛЯ ПОЛА И ВОЗРАСТА:
            'gender' => ['nullable', 'string', 'in:male,female'],
            'age' => ['nullable', 'integer', 'min:18', 'max:99'],
            
            // (Опционально) Правила для фильтров поиска, если хочешь их менять и здесь тоже:
            'target_gender' => ['nullable', 'string', 'in:male,female,all'],
            'target_age_min' => ['nullable', 'integer', 'min:18', 'max:99'],
            'target_age_max' => ['nullable', 'integer', 'min:18', 'max:99'],
            ];
    }
}