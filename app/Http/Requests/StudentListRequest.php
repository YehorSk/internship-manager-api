<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentListRequest extends FormRequest
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
        return [
            'sortBy' => 'sometimes|string|in:id,first_name,last_name,student_email,created_at,updated_at',
            'sortOrder' => 'sometimes|string|in:asc,desc',
            'itemsPerPage' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'search.first_name' => 'sometimes|string',
            'search.last_name' => 'sometimes|string',
            'search.student_email' => 'sometimes|string',
            'search.study_program_name' => 'sometimes|string',
        ];
    }
}
