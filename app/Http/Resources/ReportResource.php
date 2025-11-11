<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Report */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_type' => $this->report_type,
            'params' => $this->params,
            'file_path' => $this->file_path,
            'started_at' => $this->started_at ? $this->started_at->toDateTimeString() : null,
            'ended_at' => $this->ended_at ? $this->ended_at->toDateTimeString() : null,
            'status' => $this->status,
            'message' => $this->message,
            'task_id' => $this->task_id,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            'user_id' => $this->user_id,
        ];
    }
}
