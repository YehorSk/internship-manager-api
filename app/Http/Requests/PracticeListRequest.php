<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PracticeListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sortBy' => 'sometimes|string|in:id,name,address,contact_name,contact_email,contact_phone,status,created_at,updated_at',
            'sortOrder' => 'sometimes|string|in:asc,desc',
            'itemsPerPage' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'search.status' => 'sometimes|string',
            'search.student_name' => 'sometimes|string',
            'search.company_name' => 'sometimes|string',
            'search.semester' => 'sometimes|string',
            'search.academic_year' => 'sometimes|string',
            'search.start_date' => 'sometimes|string',
            'search.end_date' => 'sometimes|string',
            'search.study_program_name' => 'sometimes|string',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
