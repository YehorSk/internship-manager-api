<?php

namespace App\Http\Requests;

use App\Enums\ReportTypeEnum;
use App\Enums\SemesterEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\ReportStatusEnum;

class GenerateReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "report_type" => ['sometimes', 'string', Rule::in(array_map(fn($case) => $case->value, ReportTypeEnum::cases()))],
            'study_program_name' => 'sometimes|string',
            'semester' => ['sometimes', 'string', Rule::in(array_map(fn($case) => $case->value, SemesterEnum::cases()))],
            'academic_year' => 'sometimes|integer|min:2000|max:2100',
            'company_name' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => ['sometimes', 'string', Rule::in(array_map(fn($case) => $case->value, ReportStatusEnum::cases()))],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
