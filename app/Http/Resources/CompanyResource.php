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
            'name'          => $this->name,
            'address'       => $this->address,
            'contact_name'  => $this->contact_name,
            'company_email'  => $this->company_email,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
        ];
    }
}
