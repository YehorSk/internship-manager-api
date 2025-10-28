<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Enums\PracticeStatusEnum;
use App\Http\Requests\PracticeListRequest;
use App\Http\Requests\StorePracticeRequest;
use App\Http\Resources\PracticeResource;
use App\Mail\AgreementConfirmationRequestedMail;
use App\Models\Company;
use App\Models\Practice;
use App\Models\PracticeCompany;
use App\Models\PracticeStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;

class PracticeController extends Controller
{
    public function store(StorePracticeRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();

        DB::transaction(function () use ($request, $validated, $user) {
            $practice = new Practice();

            foreach (['start_date', 'end_date', 'academic_year', 'semester', 'study_program_id', 'job_title', 'job_description'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $practice->$field = $validated[$field];
                }
            }

            $practice['student_id'] = $user->student->id;

            $practiceCompany = new PracticeCompany();

            if ($validated['company_id']) {
                $company = Company::where('user_id', $validated['company_id'])->first();
                $practice->company_id = $validated['company_id'];
                $practiceCompany->name = $company->name;
                $practiceCompany->address = $company->address;
                $practiceCompany->contact_name = $company->contact_name;
                $practiceCompany->contact_email = $company->contact_email;
                $practiceCompany->contact_phone = $company->contact_phone;
                $practiceCompany->company_email = $company->company_email;
            } else {
                $practice->company_id = null;

                foreach (['company_email', 'contact_name', 'contact_email', 'contact_phone'] as $field) {
                    if (array_key_exists($field, $validated)) {
                        $practiceCompany->$field = $validated[$field];
                    }
                }

                if (array_key_exists('company_name', $validated)) {
                    $practiceCompany->name = $validated['company_name'];
                } elseif (array_key_exists('name', $validated)) {
                    $practiceCompany->name = $validated['name'];
                }

                if (array_key_exists('company_address', $validated)) {
                    $practiceCompany->address = $validated['company_address'];
                } elseif (array_key_exists('address', $validated)) {
                    $practiceCompany->address = $validated['address'];
                }
            }
            $practice->save();
            $practiceCompany->practice_id = $practice->id;
            $practiceCompany->save();

            PracticeStatusHistory::create([
                'practice_id' => $practice->id,
                'user_id' => $user->id,
                'status' => PracticeStatusEnum::CREATED->value,
                'comment' => null,
            ]);
        });

        return response()->json([
            'success' => true,
            'statusCode' => 201,
            'message' => __('practice.practice_created_successfully'),
        ], 201);
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
                $query->where('student_id', $user->student->id);
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
                $query->whereRelation('studyProgram', 'name', 'like', '%' . $pname . '%');

            })
            ->when($request->filled('search.student_name') && ($isCompany || $isSupervisor), function ($query) use ($request) {
                $fullName = trim($request->input('search.student_name'));
                if ($fullName === '') {
                    return;
                }
                $query->whereRelation('student.user', 'name', 'like', '%' . $fullName . '%');
            })
            ->when($request->filled('search.company_name') && ($isStudent || $isSupervisor), function ($query) use ($request) {
                $cname = trim($request->input('search.company_name'));
                if ($cname === '') {
                    return;
                }
                $query->whereRelation('practiceCompany', 'name', 'like', '%' . $cname . '%');
            })
            ->with($with)
            ->orderBy($request->input('sortBy', 'id'), $request->input('sortOrder', 'asc'))
            ->paginate($request->input('itemsPerPage', 10), ['*'], 'page', $request->input('page', 1));

        return PracticeResource::collection($practices);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);
        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);
        $isSupervisor = $user && $user->hasRoleId(RoleEnum::SUPERVISOR->value);

        $with = ['studyProgram', 'practiceStatusHistory'];

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
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('practice.not_found')], 404);
        }

        return new PracticeResource($practice);
    }

    public function update(int $id, StorePracticeRequest $request)
    {
        $validated = $request->validated();

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
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('practice.not_found')], 404);
        }

        if ($isStudent && $practice->status !== PracticeStatusEnum::CREATED->value) {
            return response()->json(['success' => false, 'statusCode' => 403, 'message' => __('practice.cannot_edit')], 403);
        }

        DB::transaction(function () use ($request, $practice, $validated, $user, $isStudent) {
            foreach (['start_date', 'end_date', 'academic_year', 'semester', 'study_program_id', 'job_title', 'job_description'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $practice->$field = $validated[$field];
                }
            }

            $practiceCompany = $practice->practiceCompany()->first() ?? new PracticeCompany();
            $practiceCompany->practice_id = $practice->id;

            if ($validated['company_id']) {
                $practice->company_id = $validated['company_id'];
                $company = Company::where('user_id', $validated['company_id'])->first();
                $practiceCompany->name = $company->name;
                $practiceCompany->address = $company->address;
                $practiceCompany->contact_name = $company->contact_name;
                $practiceCompany->contact_email = $company->contact_email;
                $practiceCompany->contact_phone = $company->contact_phone;
                $practiceCompany->company_email = $company->company_email;
            } else {
                $practice->company_id = null;
                foreach (['company_email', 'contact_name', 'contact_email', 'contact_phone'] as $field) {
                    if (array_key_exists($field, $validated)) {
                        $practiceCompany->$field = $validated[$field];
                    }
                }

                if (array_key_exists('company_name', $validated)) {
                    $practiceCompany->name = $validated['company_name'];
                } elseif (array_key_exists('name', $validated)) {
                    $practiceCompany->name = $validated['name'];
                }

                if (array_key_exists('company_address', $validated)) {
                    $practiceCompany->address = $validated['company_address'];
                } elseif (array_key_exists('address', $validated)) {
                    $practiceCompany->address = $validated['address'];
                }
            }

            $practice->save();
            $practiceCompany->save();
        });

        return response()->json(['success' => true, 'statusCode' => 200, 'message' => __('practice.updated_successfully')]);
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);

        if (!$isStudent) {
            return response()->json(['success' => false, 'statusCode' => 403, 'message' => __('practice.delete_not_allowed')], 403);
        }

        $practice = Practice::query()
            ->where('student_id', $user->id)
            ->where('status', PracticeStatusEnum::CREATED->value)
            ->where('id', $id)
            ->first();

        if (!$practice) {
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('practice.not_found')], 404);
        }

        $practice->status = PracticeStatusEnum::CANCELED->value;
        $practice->save();

        PracticeStatusHistory::create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'status' => PracticeStatusEnum::CANCELED->value,
            'comment' => null,
        ]);

        return response()->json(['success' => true, 'statusCode' => 200, 'message' => __('practice.deleted_successfully')]);
    }

    public function downloadAgreement($id, Request $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);
        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);
        $isSupervisor = $user && $user->hasRoleId(RoleEnum::SUPERVISOR->value);

        $with = ['studyProgram', 'practiceCompany', 'student'];

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
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('practice.not_found')], 404);
        }

//        $student = $practice->student;
//        $studentUser = $student->user ?? null;
//        $company = $practice->practiceCompany;
//        $studyProgram = $practice->studyProgram;

        $data = [
            'practice' => $practice,
//            'student' => $student,
//            'studentUser' => $studentUser,
//            'company' => $company,
//            'studyProgram' => $studyProgram,
        ];

        try {
            $html = view('documents.agreement', $data)->render();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'statusCode' => 500, 'message' => __('practice.agreement_template_error')], 500);
        }

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        // отключаем выполнение PHP в шаблоне по соображениям безопасности
        $options->set('isPhpEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfContent = $dompdf->output();

        $disk = 'local';
        $prefix = 'documents/filled';
        $path = $prefix . '/practice_' . $practice->id . '.pdf';

        try {
            Storage::disk($disk)->put($path, $pdfContent);
            $saved = true;
        } catch (\Exception $e) {
            logger()->error('Cannot save generated agreement', ['error' => $e->getMessage()]);
            $saved = false;
        }

        $filename = 'agreement_practice_' . $practice->id . '.pdf';

        if (!empty($saved)) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    $stream = Storage::disk($disk)->readStream($path);
                    if ($stream) {
                        return response()->stream(function () use ($stream) {
                            fpassthru($stream);
                        }, 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                        ]);
                    }
                }
            } catch (\Exception $e) {
                logger()->warning('Saved agreement but cannot read stream', ['error' => $e->getMessage()]);
            }
        }

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function agreementConfirmationRequest($id, Request $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);

        if (!$isStudent) {
            return response()->json(['success' => false, 'statusCode' => 403, 'message' => __('practice.request_approval_not_allowed')], 403);
        }

        $with = ['studyProgram', 'practiceCompany', 'student'];
/*        DB::listen(function ($query) {
            // $query->sql, $query->bindings, $query->time
            logger()->info('SQL', ['sql' => $query->sql, 'bindings' => $query->bindings, 'time' => $query->time]);
        });*/
        $practice = Practice::query()
            ->where('student_id', $user->id)
            ->whereIn('status', [
                PracticeStatusEnum::CREATED->value,
                PracticeStatusEnum::AGREEMENT_REJECTED_BY_COMPANY->value,
                PracticeStatusEnum::AGREEMENT_REJECTED_BY_SUPERVISOR->value,
            ])
            ->where('id', $id)
            ->with($with)
            ->first();

        if (!$practice) {
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('practice.not_found')], 404);
        }

        $currentStatus = $practice->status;

        $practice->status = PracticeStatusEnum::AGREEMENT_CONFIRM_REQUESTED->value;
        $practice->save();

        PracticeStatusHistory::create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'status' => PracticeStatusEnum::AGREEMENT_CONFIRM_REQUESTED->value,
            'comment' => ($currentStatus === PracticeStatusEnum::CREATED->value) ? __('practice.agreement_confirmation_requested') : __('practice.agreement_reconfirmation_requested'),
        ]);

        if ($practice->company_id) {
            try {
                $confirmLink = route('practices.agreement.confirm', ['id' => $practice->id]);
            } catch (\Exception $e) {
                $confirmLink = null;
            }

            try {
                $rejectLink = route('practices.agreement.reject', ['id' => $practice->id]);
            } catch (\Exception $e) {
                $rejectLink = null;
            }

            $firstInit = mb_substr($practice->student->first_name, 0, 1);
            $lastInit  = mb_substr($practice->student->last_name, 0, 1);
            $printName = trim($firstInit . '. ' . $lastInit . '.');

            $options = [
                'isReconfirm' => $currentStatus !== PracticeStatusEnum::CREATED->value,
                'confirmLink' => $confirmLink,
                'rejectLink' => $rejectLink,
                'printName' => $printName,
            ];

            Mail::to($practice->practiceCompany->contact_email)->send(new AgreementConfirmationRequestedMail($practice, $options));
        }

        return response()->json(['success' => true, 'statusCode' => 200, 'message' => __('practice.agreement_approval_requested_successfully')]);
    }

    public function agreementConfirm($id, Request $request)
    {
        //
    }

    public function agreementReject($id, Request $request)
    {
        //
    }
}
