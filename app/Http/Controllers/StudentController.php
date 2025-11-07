<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\StudentListRequest;
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
                    ->orWhere('last_name', 'like', '%'.$value.'%');
            })
            ->distinct()
            ->get();
        return StudentResource::collection($students);
    }

    public function list(StudentListRequest $request){
        $user = $request->user();

        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);

        $students = Student::query()
            ->when($isCompany, function ($query) use ($user) {
                $query->whereHas('practices', function ($practiceQuery) use ($user) {
                    $practiceQuery->where('company_id', $user->id);
                });
            })
            ->when($request->has('search.first_name'), function ($query) use ($request) {
                $query->where('first_name', $request->input('search.first_name'));
            })
            ->when($request->has('search.last_name'), function ($query) use ($request) {
                $query->where('last_name', $request->input('search.last_name'));
            })
            ->when($request->has('search.student_email'), function ($query) use ($request) {
                $query->where('student_email', $request->input('search.student_email'));
            })
            ->when($request->has('search.primary_email'), function ($query) use ($request) {
                $query->where('primary_email', $request->input('search.primary_email'));
            })
            ->when($request->has('search.phone'), function ($query) use ($request) {
                $query->where('phone', $request->input('search.phone'));
            })
            ->when($request->has('search.address'), function ($query) use ($request) {
                $query->where('address', $request->input('search.address'));
            })
            ->orderBy($request->input('sortBy', 'created_at'), $request->input('sortOrder', 'desc'))
            ->paginate($request->input('itemsPerPage', 10), ['*'], 'page', $request->input('page', 1));

        return StudentResource::collection($students);
    }
}
