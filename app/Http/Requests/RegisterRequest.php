<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
            'type' => ['required', Rule::in(['student', 'company'])],
        ];

        switch ($this->input('type')) {
            case 'student':
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
                    'study_program' => 'required|exists:study_programs,name'
                ]);
                break;

            case 'company':
                $rules = array_merge($rules, [
                    'name' => 'required|string|max:255',
                    'address' => 'required|string|max:255',
                    'contact_name' => 'required|string|max:255',
                    'contact_email' => 'required|email|unique:companies,contact_email',
                    'contact_phone' => 'nullable|string|max:20',
                ]);
                break;
        }

        return $rules;
    }

}
