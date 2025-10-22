<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePracticeRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'academic_year' => 'required|string|max:9',
            'semester' => 'required|in:summer,winter',
            'study_program_id' => 'required|integer|exists:study_programs,id',
        ];

        if ($this->filled('company_id')) {
            $rules = array_merge($rules, [
                'company_id' => 'integer|exists:companies,id',
            ]);
        } else {
            return array_merge($rules, [
                'company_name' => 'required|string|max:255',
                'company_address' => 'required|string|max:255',
                'company_email' => 'required|email',
                'contact_name' => 'required|string|max:255',
                'contact_email' => 'required|email',
                'contact_phone' => 'required|string|max:50',
            ]);
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true;
    }
}
