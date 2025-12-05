<?php

namespace App\Http\Requests;

use App\Enums\PracticeStatusEnum;
use App\Enums\SemesterEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
            'company_id' => 'nullable|exists:companies,user_id',
            'study_program_id' => 'required|exists:study_programs,id',
            'semester' => ['required', 'string', Rule::in(array_map(fn($case) => $case->value, SemesterEnum::cases()))],
            'academic_year' => ['required', 'string', 'max:9'],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'job_description' => 'nullable|string',
            'is_paid' => 'nullable|boolean',
        ];
        if(!$this->filled('company_id')){
            $rules = array_merge($rules, [
                'company_name' => 'required|string|max:255',
                'company_address' => 'required|string|max:255',
                'contact_name' => 'required|string|max:255',
                'contact_email' => 'required|email',
                'contact_phone' => 'required|string|max:50',
                'contact_position' => 'required|string|max:50',
            ]);
            if($this->filled('id')){
                $rules += [
                    'company_email' => [
                        'required',
                        'email',
                        'unique:companies,company_email'
                    ],
                    'ico' => [
                        'required',
                        'string',
                        'regex:/^\d{8}$/',
                        'unique:companies,ico'
                    ],
                ];
            }else{
                $rules += [
                    'company_email' => [
                        'required',
                        'email',
                        Rule::unique('companies', 'company_email')
                            ->ignore($this->input('company_id')),
                    ],
                    'ico' => [
                        'required',
                        'string',
                        'regex:/^\d{8}$/',
                        Rule::unique('companies', 'ico')
                            ->ignore($this->input('company_id')),
                    ],
                ];
            }
        }
        return $rules;
    }
}
