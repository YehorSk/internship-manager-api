<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'name'          => $this->name,
            'address'       => $this->address,
            'contact_position' => $this->contact_position,
            'contact_name'  => $this->contact_name,
            'company_email'  => $this->company_email,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'registered_by' => $this->registered_by,
            'student' => new StudentResource($this->whenLoaded('student')),
            'status' => $this->status,
            'ico' => $this->ico
        ];
    }
}
