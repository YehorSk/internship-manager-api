<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $rules = [];
        $user = $this->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);
        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);
        $isSupervisor = $user && $user->hasRoleId(RoleEnum::SUPERVISOR->value);

        if($isStudent){
            $rules = array_merge($rules, [
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'primary_email' => 'sometimes|required|email',
                'phone' => 'sometimes|required|string|max:20',
                'address' => 'sometimes|required|string|max:255',
                'study_program' => 'sometimes|required|exists:study_programs,id',
            ]);
        }
        if($isCompany){
            $rules = array_merge($rules, [
                'name' => 'sometimes|required|string|max:255',
                'address' => 'sometimes|required|string|max:255',
                'contact_name' => 'sometimes|required|string|max:255',
                'contact_position' => 'sometimes|required|string|max:255',
                'contact_email' => 'sometimes|required|email',
                'contact_phone' => 'sometimes|required|string|max:20',
                'ico' => [
                    'sometimes',
                    'required',
                    'string',
                    'regex:/^\d{8}$/',
                    Rule::unique('companies', 'ico')->ignore($user->company->id),
                ]
            ]);
        }
        if($isSupervisor){
            $rules = array_merge($rules, [
                'title' => 'sometimes|required|string|max:255',
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
            ]);
        }

        return $rules;
    }
}
