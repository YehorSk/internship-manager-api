<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sortBy' => 'sometimes|string|in:id,user_id,report_type,file_path,started_at,ended_at,status,task_id,created_at,updated_at',
            'sortOrder' => 'sometimes|string|in:asc,desc',
            'itemsPerPage' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'search.report_type' => 'sometimes|string',
            'search.status' => 'sometimes|string',
            'search.task_id' => 'sometimes|integer',
            'search.started_at' => 'sometimes|date',
            'search.ended_at' => 'sometimes|date',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
