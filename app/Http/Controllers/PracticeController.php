<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Enums\PracticeStatusEnum;
use App\Http\Requests\PracticeListRequest;
use App\Http\Requests\StorePracticeRequest;
use App\Http\Requests\UpdatePracticeRequest;
use App\Models\Company;
use App\Models\Practice;
use App\Models\PracticeCompany;
use App\Models\PracticeStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PracticeController extends Controller
{
    public function store(StorePracticeRequest $request)
    {
        $data = $request->validated();
        $practice = new Practice();

        foreach (['start_date', 'end_date', 'academic_year', 'semester', 'study_program_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $practice->$field = $data[$field];
            }
        }

        $user = $request->user();
        $practice['student_id'] = $user->id;

        $practiceCompany = new PracticeCompany();
        // practice_id будет установлен после сохранения practice, иначе id будет null

        if ($request->company_id) {
            $company = Company::find($request->company_id);
            $practice->company_id = $request->company_id;
            $practiceCompany->name = $company->name;
            $practiceCompany->address = $company->address;
            $practiceCompany->contact_name = $company->contact_name;
            $practiceCompany->contact_email = $company->contact_email;
            $practiceCompany->contact_phone = $company->contact_phone;
        } else {
            $practice->company_id = null;
            foreach (['name', 'address', 'company_name', 'company_address', 'contact_name', 'contact_email', 'contact_phone'] as $field) {
                if (array_key_exists($field, $data)) {
                    $practiceCompany->$field = $data[$field];
                }
            }
        }
        $practice->save();
        // установить корректный practice_id после сохранения
        $practiceCompany->practice_id = $practice->id;
        $practiceCompany->save();

        PracticeStatusHistory::create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'status' => PracticeStatusEnum::CREATED,
            'comment' => null,
        ]);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => __('practice.practice_created_successfully'),
        ]);
    }

    function list(PracticeListRequest $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);
        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);
        $isSupervisor = $user && $user->hasRoleId(RoleEnum::SUPERVISOR->value);

        $with = ['studyProgram'];

        if ($isStudent || $isSupervisor) {
            $with[] = 'practiceCompany';
        }

        if ($isCompany || $isSupervisor) {
            $with[] = 'student';
        }

        $practices = Practice::query()
            ->when($isStudent, function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->when($isCompany, function ($query) use ($user) {
                $query->where('company_id', $user->id);
            })
            ->when($request->has('search.status'), function ($query) use ($request) {
                $query->where('status', $request->input('search.status'));
            })
            ->when($request->has('search.semester'), function ($query) use ($request) {
                $query->where('semester', trim($request->input('search.semester')));
            })
            ->when($request->has('search.academic_year'), function ($query) use ($request) {
                $query->where('academic_year', trim($request->input('search.academic_year')));
            })
            ->when($request->has('search.start_date'), function ($query) use ($request) {
                $query->where('start_date', '>=', $request->input('search.start_date'));
            })
            ->when($request->has('search.end_date'), function ($query) use ($request) {
                $query->where('end_date', '<=', $request->input('search.end_date'));
            })
            ->when($request->filled('search.study_program_name'), function ($query) use ($request) {
                $pname = trim($request->input('search.study_program_name'));
                if ($pname === '') {
                    return;
                }
                $query->whereHas('studyProgram', function ($q) use ($pname) {
                    $q->where('name', 'like', '%' . $pname . '%');
                });
            })
            ->when($request->filled('search.student_name') && ($isCompany || $isSupervisor), function ($query) use ($request) {
                $name = trim($request->input('search.student_name'));
                if ($name === '') {
                    return;
                }
                $query->whereHas('student', function ($q) use ($name) {
                    $q->where('name', 'like', '%' . $name . '%');
                });
            })
            ->when($request->filled('search.company_name') && ($isStudent || $isSupervisor), function ($query) use ($request) {
                $cname = trim($request->input('search.company_name'));
                if ($cname === '') {
                    return;
                }
                $query->whereHas('practiceCompany', function ($q) use ($cname) {
                    $q->where('name', 'like', '%' . $cname . '%');
                });
            })
            ->with($with)
            ->orderBy($request->input('sortBy', 'id'), $request->input('sortOrder', 'asc'))
            ->paginate($request->input('itemsPerPage', 10), ['*'], 'page', $request->input('page', 1));

        return response()->json($practices);
    }

    public function get($id, $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);
        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);
        $isSupervisor = $user && $user->hasRoleId(RoleEnum::SUPERVISOR->value);

        $with = [];

        if ($isStudent || $isSupervisor) {
            $with[] = 'practiceCompany';
        }

        if ($isCompany || $isSupervisor) {
            $with[] = 'student';
        }

        $practice = Practice::query()
            ->when($isStudent, function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->when($isCompany, function ($query) use ($user) {
                $query->where('company_id', $user->id);
            })
            ->where('id', $id)
            ->with($with)
            ->first();

        if (!$practice) {
            return response()->json(['status' => false, 'message' => __('practice.not_found')], 404);
        }

        return response()->json($practice);
    }

    public function update(int $id, UpdatePracticeRequest $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);
        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);

        $practice = Practice::query()
            ->when($isStudent, function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->when($isCompany, function ($query) use ($user) {
                $query->where('company_id', $user->id);
            })
            ->where('id', $id)
            ->first();

        if (!$practice) {
            return response()->json(['status' => false, 'message' => __('practice.not_found')], 404);
        }

        $validated = $request->validated();

        if ($isStudent && $practice->status !== PracticeStatusEnum::CREATED) {
            return response()->json(['status' => false, 'message' => __('practice.cannot_edit_after_company_confirm')], 403);
        }

        DB::transaction(function () use ($request, $practice, $validated, $user, $isStudent) {
            foreach (['start_date', 'end_date', 'academic_year', 'semester', 'study_program_id'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $practice->$field = $validated[$field];
                }
            }

            $practice->save();

            $practiceCompany = $practice->practiceCompany()->first() ?? new PracticeCompany();
            $practiceCompany->practice_id = $practice->id;

            if ($practice->company_id) {
                $practice->company_id = $validated['company_id'];
                $company = Company::find($request->company_id);
                $practiceCompany->name = $company->name;
                $practiceCompany->address = $company->address;
                $practiceCompany->contact_name = $company->contact_name;
                $practiceCompany->contact_email = $company->contact_email;
                $practiceCompany->contact_phone = $company->contact_phone;
            } else {
                $practice->company_id = null;
                foreach (['name', 'address', 'company_name', 'company_address', 'contact_name', 'contact_email', 'contact_phone'] as $field) {
                    if (array_key_exists($field, $validated)) {
                        $practiceCompany->$field = $validated[$field];
                    }
                }
            }

            $practice->practiceCompany()->save($practiceCompany);
        });

        return response()->json(['success' => true, 'statusCode' => 200, 'message' => __('practice.updated_successfully')]);
    }

    public function delete($id, Request $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);

        if (!$isStudent) {
            return response()->json(['status' => false, 'message' => __('practice.delete_not_allowed')], 403);
        }

        $practice = Practice::query()
            ->where('student_id', $user->id)
            ->where('status', PracticeStatusEnum::CREATED)
            ->where('id', $id)
            ->first();

        if (!$practice) {
            return response()->json(['status' => false, 'message' => __('practice.not_found')], 404);
        }

        $practice->status = PracticeStatusEnum::CANCELED;
        $practice->save();

        return response()->json(['status' => true, 'message' => __('practice.deleted_successfully')]);
    }
}
