<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function search(Request $request, $value){
        $user = $request->user();

        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);

        $students = Student::orderBy('id')
            ->when($isCompany, function ($query) use ($user) {
                $query->whereHas('practices', function ($practiceQuery) use ($user) {
                    $practiceQuery->where('company_id', $user->id);
                });
            })
            ->where(function ($query) use ($value) {
                $query->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%')
                    ->orWhere('student_email', 'like', '%'.$value.'%');
            })
            ->distinct()
            ->get();
        return StudentResource::collection($students);
    }
}
