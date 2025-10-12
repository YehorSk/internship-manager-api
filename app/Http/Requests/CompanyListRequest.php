<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        //
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
            'sortBy' => 'sometimes|string|in:id,name,address,contact_name,contact_email,contact_phone,status,created_at,updated_at',
            'sortOrder' => 'sometimes|string|in:asc,desc',
            'itemsPerPage' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'search.status' => 'sometimes|boolean',
            'search.name' => 'sometimes|string',
            'search.address' => 'sometimes|string',
            'search.contact_name' => 'sometimes|string',
            'search.contact_email' => 'sometimes|string',
            'search.contact_phone' => 'sometimes|string',
        ];
    }
}
