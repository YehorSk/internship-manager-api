<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePracticeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'company_id' => 'nullable|exists:companies,id',
            'study_program_id' => 'required|exists:study_programs,id',
            'semester' => ['required', 'string', Rule::in(['summer', 'winter'])],
            'academic_year' => ['required', 'string', 'max:9'],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string',
        ];
        if(!$this->filled('company_id')){
            $rules = array_merge($rules, [
                'company_name' => 'required|string|max:255',
                'company_address' => 'required|string|max:255',
                'company_email' => 'required|email',
                'contact_name' => 'required|string',
                'contact_email' => 'required|email',
                'contact_phone' => 'required|numeric',
            ]);
        }
        return $rules;
    }
}
