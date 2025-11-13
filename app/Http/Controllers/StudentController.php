<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\StudentListRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function search(Request $request){
        $user = $request->user();

        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);
        $value = $request->query('value');
        if (!$value) {
            return response()->json([
                'data' => []
            ]);
        }
        $students = Student::query()
            ->when($isCompany, function ($query) use ($user) {
                $query->whereHas('practices', function ($practiceQuery) use ($user) {
                    $practiceQuery->where('company_id', $user->id);
                });
            })
            ->when($value, fn($query) => $query->where(function ($query) use ($value) {
                $query->where('first_name', 'like', '%'.$value.'%')
                    ->orWhere('last_name', 'like', '%'.$value.'%');
            }))
            ->distinct()
            ->orderBy('id')
            ->get();
        return StudentResource::collection($students);
    }

    public function list(StudentListRequest $request){
        $user = $request->user();

        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);

        $students = Student::with('studyPrograms')
            ->when($isCompany, function ($query) use ($user) {
                $query->whereHas('practices', function ($practiceQuery) use ($user) {
                    $practiceQuery->where('company_id', $user->id);
                });
            })
            ->when($request->has('search.first_name'), function ($query) use ($request) {
                $query->where('first_name', 'like', '%' . trim($request->input('search.first_name')) . '%');
            })
            ->when($request->has('search.last_name'), function ($query) use ($request) {
                $query->where('last_name', 'like', '%' . trim($request->input('search.last_name')) . '%');
            })
            ->when($request->has('search.student_email'), function ($query) use ($request) {
                $query->where('student_email', 'like', '%' . trim($request->input('search.student_email')) . '%');
            })
            ->when($request->filled('search.study_program_name'), function ($query) use ($request) {
                $pname = trim($request->input('search.study_program_name'));
                if ($pname === '') {
                    return;
                }
                $query->whereRelation('studyPrograms', 'name', 'like', '%' . $pname . '%');

            })
            ->orderBy($request->input('sortBy', 'created_at'), $request->input('sortOrder', 'desc'))
            ->paginate($request->input('itemsPerPage', 10), ['*'], 'page', $request->input('page', 1));

        return StudentResource::collection($students);
    }
}
