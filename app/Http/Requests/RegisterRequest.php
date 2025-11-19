<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'type' => ['required', 'integer', Rule::in([RoleEnum::STUDENT->value, RoleEnum::COMPANY->value])],
            'language' => ['nullable', 'string', Rule::in(['sk','ua','be','ru','en'])],
        ];

        switch ($this->input('type')) {
            case RoleEnum::STUDENT->value:
                $rules = array_merge($rules, [
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'student_email' => [
                        'required',
                        'email',
                        'unique:students,student_email',
                        'regex:/^[\w\.\-]+@student\.ukf\.sk$/i'
                    ],
                    'primary_email' => 'required|nullable|email',
                    'phone' => 'required|string|max:20',
                    'address' => 'required|string|max:255',
                    'study_program' => 'required|exists:study_programs,id'
                ]);
                break;

            case RoleEnum::COMPANY->value:
                $rules = array_merge($rules, [
                    'name' => 'required|string|max:255',
                    'address' => 'required|string|max:255',
                    'contact_name' => 'required|string|max:255',
                    'contact_position' => 'nullable|string|max:255',
                    'company_email' => 'required|email|unique:companies,company_email',
                    'contact_email' => 'required|email',
                    'contact_phone' => 'required|string|max:20',
                    'ico' => ['required',
                              'string',
                              'regex:/^\d{8}$/',
                              'unique:companies,ico'
                    ],
                    'password' => 'required|string|min:8|confirmed', // пароль обязателен для компании
                ]);
                break;
        }

        return $rules;
    }

}
