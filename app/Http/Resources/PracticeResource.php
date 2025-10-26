<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PracticeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'company_id' => $this->company_id,
            'semester' => $this->semester,
            'academic_year' => $this->academic_year,
            'start_date' => $this->start_date ? $this->start_date->toDateString() : null,
            'end_date' => $this->end_date ? $this->end_date->toDateString() : null,
            'status' => $this->status,
            'job_title' => $this->job_title,
            'job_description' => $this->job_description,
            'study_program' => new StudyProgramResource($this->whenLoaded('studyProgram')),
            'practice_status_history' => PracticeStatusHistoryResource::collection($this->whenLoaded('practiceStatusHistory')),
            'practice_company' => $this->whenLoaded('practiceCompany'),
            'student' => new StudentResource($this->whenLoaded('student')),
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
