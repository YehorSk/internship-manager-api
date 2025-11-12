<?php

namespace App\Http\Controllers;

use App\Enums\DocumentTypeEnum;
use App\Enums\RoleEnum;
use App\Enums\PracticeStatusEnum;
use App\Http\Requests\DownloadDocumentRequest;
use App\Http\Requests\PracticeListRequest;
use App\Http\Requests\StorePracticeRequest;
use App\Http\Requests\UpdateDocumentStatusRequest;
use App\Http\Requests\UploadDocumentRequest;
use App\Http\Resources\PracticeResource;
use App\Mail\AgreementConfirmationRequestedMail;
use App\Mail\NotifyStudentAgreementStatusMail;
use App\Mail\NotifySupervisorAgreementStatusMail;
use App\Mail\PracticeDeletedBySupervisor;
use App\Mail\PracticeUpdatedBySupervisor;
use App\Models\Company;
use App\Models\Document;
use App\Models\Practice;
use App\Models\PracticeCompany;
use App\Models\PracticeStatusHistory;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

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

            $practice['student_id'] = $user->id;

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

    public function statistics(Request $request){
        $user = $request->user();
        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);

        $pending = Practice::query()
            ->when($isCompany, function ($query) use ($user) {
                $query->where('company_id', $user->id);
            })
            ->whereIn('status', [
                PracticeStatusEnum::AGREEMENT_CONFIRM_REQUESTED->value,
                PracticeStatusEnum::REPORT_CONFIRM_REQUESTED->value,
            ])
            ->get();

        $statistics = Practice::query()
            ->when($isCompany, function ($query) use ($user) {
                $query->where('company_id', $user->id);
            })
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $active = Practice::query()
            ->when($isCompany, function ($query) use ($user) {
                $query->where('company_id', $user->id);
            })
            ->whereNotIn('status', [PracticeStatusEnum::CANCELED->value, PracticeStatusEnum::DEFENDED->value])
            ->count();

        $finished = Practice::query()
            ->when($isCompany, function ($query) use ($user) {
                $query->where('company_id', $user->id);
            })
            ->whereIn('status', [PracticeStatusEnum::DEFENDED->value])
            ->count();

        $cancelled = Practice::query()
            ->when($isCompany, function ($query) use ($user) {
                $query->where('company_id', $user->id);
            })
            ->whereIn('status', [PracticeStatusEnum::CANCELED->value])
            ->count();

        $students = Student::orderBy('id')
            ->when($isCompany, function ($query) use ($user) {
                $query->whereHas('practices', function ($practiceQuery) use ($user) {
                    $practiceQuery->where('company_id', $user->id);
                });
            })
            ->distinct()
            ->count();

        return response()->json([
            'pending' => PracticeResource::collection($pending),
            'statistics' => $statistics,
            'students' => $students,
            'active' => $active,
            'cancelled' => $cancelled,
            'finished' => $finished,
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
            ->orderBy($request->input('sortBy', 'created_at'), $request->input('sortOrder', 'desc'))
            ->paginate($request->input('itemsPerPage', 10), ['*'], 'page', $request->input('page', 1));

        return PracticeResource::collection($practices);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);
        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);
        $isSupervisor = $user && $user->hasRoleId(RoleEnum::SUPERVISOR->value);

        $with = ['studyProgram', 'practiceStatusHistory', 'documents'];

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
        $isSupervisor = $user && $user->hasRoleId(RoleEnum::SUPERVISOR->value);

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

        if($isSupervisor){
            Mail::to($practice->practiceCompany->contact_email)->send(new PracticeUpdatedBySupervisor(
                practice: $practice,
                student: null,
                company: $practice->practiceCompany
            ));
            Mail::to($practice->student->student_email)->send(new PracticeUpdatedBySupervisor(
                practice: $practice,
                student: $practice->student,
                company: null
            ));
        }

        $with = ['studyProgram', 'practiceStatusHistory', 'documents'];

        if ($isStudent || $isSupervisor) {
            $with[] = 'practiceCompany';
        }

        if ($isCompany || $isSupervisor) {
            $with[] = 'student';
        }

        return response()->json([
            'success' => true,
            'data' => new PracticeResource($practice),
            'statusCode' => 200,
            'message' => __('practice.updated_successfully')
        ]);
    }

    public function uploadDocument(UploadDocumentRequest $request)
    {
        $user = $request->user();
        $practice = Practice::where('id', $request->input('practice_id'))->first();

        if (!$user->student || $practice->student_id !== $user->id) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => __('practice.not_your_practice'),
            ]);
        }

        $documentType = [
            'agreement' => DocumentTypeEnum::AGREEMENT,
            'report' => DocumentTypeEnum::REPORT,
        ];

        if ($practice->hasDocumentType($documentType[$request->input('document_type')]->value)) {
            $lastDocument = $practice->documents()
                ->where('type', $documentType[$request->input('document_type')]->value)
                ->latest()
                ->first();

            if ($lastDocument) {
                Storage::disk('s3')->delete($lastDocument->file_path);
                $lastDocument->delete();
            }
        }

        if ($request->input('document_type') === 'agreement') {
            if (
                !$practice->lastStatusIs(PracticeStatusEnum::CREATED) &&
                !$practice->lastStatusIs(PracticeStatusEnum::AGREEMENT_REJECTED_BY_SUPERVISOR) &&
                !$practice->lastStatusIs(PracticeStatusEnum::AGREEMENT_REJECTED_BY_COMPANY)
            ) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => __('practice.cannot_upload_document_in_this_status'),
                ]);
            }
        } elseif ($request->input('document_type') === 'report') {
            if (
                !$practice->lastStatusIs(PracticeStatusEnum::AGREEMENT_CONFIRMED_BY_SUPERVISOR) &&
                !$practice->lastStatusIs(PracticeStatusEnum::AGREEMENT_CONFIRMED_BY_COMPANY) &&
                !$practice->lastStatusIs(PracticeStatusEnum::REPORT_REJECTED_BY_SUPERVISOR) &&
                !$practice->lastStatusIs(PracticeStatusEnum::REPORT_REJECTED_BY_COMPANY)
            ) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => __('practice.cannot_upload_document_in_this_status'),
                ]);
            }
        }

        $file = $request->file('document');
        $filename = $user->student->id . '_' . time() . '_' . $file->getClientOriginalName();

        $folder = $request->input('document_type') === 'agreement' ? 'agreements' : 'reports';
        $key = $file->storeAs($folder, $filename, 's3');

        if (!$key) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => __('practice.file_upload_failed'),
            ]);
        }

        $document = new Document([
            'practice_id' => $practice->id,
            'type' => $documentType[$request->input('document_type')],
            'file_path' => $key,
        ]);
        $document->save();

        return response()->json(['success' => true, 'statusCode' => 200, 'message' => __('practice.updated_successfully')]);
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);
        $isSupervisor = $user && $user->hasRoleId(RoleEnum::SUPERVISOR->value);

        if (!$isStudent && !$isSupervisor) {
            return response()->json(['success' => false, 'statusCode' => 403, 'message' => __('practice.delete_not_allowed')], 403);
        }

        $practice = Practice::query()
            ->when($isStudent, function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
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

        if($isSupervisor){
            Mail::to($practice->practiceCompany->contact_email)->send(new PracticeDeletedBySupervisor(
                practice: $practice,
                student: null,
                company: $practice->practiceCompany
            ));
            Mail::to($practice->student->student_email)->send(new PracticeDeletedBySupervisor(
                practice: $practice,
                student: $practice->student,
                company: null
            ));
        }

        return response()->json(['success' => true, 'statusCode' => 200, 'message' => __('practice.deleted_successfully')]);
    }

    public function downloadAgreement($id, Request $request)
    {
        $practice = $this->getPracticeForStudentDocument($request, (int) $id);

        $practice_hours = '150';

        $values = [
            'student_full_name' => $practice->student->user->name ?? trim(($practice->student->first_name ?? '') . ' ' . ($practice->student->last_name ?? '')),
            'student_address' => $practice->student->address ?? '',
            'student_email' => $practice->student->student_email ?? '',
            'student_phone' => $practice->student->phone ?? '',
            'student_study_program' => $practice->studyProgram->name ?? '',
            'company_name' => $practice->practiceCompany->name ?? '',
            'company_address' => $practice->practiceCompany->address ?? '',
            'company_contact_name' => $practice->practiceCompany->contact_name ?? '',
            'company_contact_position' => $practice->practiceCompany->contact_position ?? '',
            'practice_hours' => $practice_hours,
            'practice_start_date' => $practice->start_date?->format('d.m.Y') ?? '—',
            'practice_end_date' => $practice->end_date?->format('d.m.Y') ?? '—',
        ];

        return $this->returnPracticeDocumentsAsDocx(
            practice: $practice,
            s3Path: 'templates/Dohoda_o_odbornej_praxi_študenta-AI.docx',
            values: $values,
            filenamePrefix: 'agreement_practice'
        );
    }

    public function reportConfirmationRequest($id, Request $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);

        if (!$isStudent) {
            return response()->json(['success' => false, 'statusCode' => 403, 'message' => __('practice.request_approval_not_allowed')], 403);
        }

        $with = ['studyProgram', 'practiceCompany', 'student'];
        $practice = Practice::query()
            ->where('student_id', $user->id)
            ->whereIn('status', [
                PracticeStatusEnum::AGREEMENT_CONFIRMED_BY_SUPERVISOR->value,
                PracticeStatusEnum::AGREEMENT_CONFIRMED_BY_COMPANY->value,
                PracticeStatusEnum::REPORT_REJECTED_BY_SUPERVISOR->value,
                PracticeStatusEnum::REPORT_REJECTED_BY_COMPANY->value,
            ])
            ->where('id', $id)
            ->with($with)
            ->first();
        if (!$practice) {
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('practice.not_found')], 404);
        }
        if (!$practice->hasDocumentType(DocumentTypeEnum::REPORT->value)) {
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('practice.report_not_found')], 404);
        }
        $practice->status = PracticeStatusEnum::REPORT_CONFIRM_REQUESTED->value;
        $practice->save();
        PracticeStatusHistory::create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'status' => PracticeStatusEnum::REPORT_CONFIRM_REQUESTED->value,
            'comment' => __('practice.report_confirmation_requested'),
        ]);
        if ($practice->company_id) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

            $firstInit = mb_substr($practice->student->first_name, 0, 1);
            $lastInit  = mb_substr($practice->student->last_name, 0, 1);
            $printName = trim($firstInit . '. ' . $lastInit . '.');

            $options = [
                'isReconfirm' => false,
                'updateLink' => $frontendUrl . '/login',
                'printName' => $printName,
                'actionType' => 'report',
            ];
            Mail::to($practice->practiceCompany->contact_email)->send(new AgreementConfirmationRequestedMail($practice, $options));
        }
        return response()->json(['success' => true, 'statusCode' => 200, 'message' => __('practice.report_approval_requested_successfully')]);
    }

    public function agreementConfirmationRequest($id, Request $request)
    {
        $user = $request->user();

        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);

        if (!$isStudent) {
            return response()->json(['success' => false, 'statusCode' => 403, 'message' => __('practice.request_approval_not_allowed')], 403);
        }

        $with = ['studyProgram', 'practiceCompany', 'student'];

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

        if (!$practice->hasDocumentType(DocumentTypeEnum::AGREEMENT->value)) {
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('practice.agreement_not_found')], 404);
        }

        if(!$practice->start_date && !$practice->end_date){
            return response()->json(['success' => false, 'statusCode' => 422, 'message' => __('practice.please_fill_date_fields')], 422);
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
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

            $firstInit = mb_substr($practice->student->first_name, 0, 1);
            $lastInit  = mb_substr($practice->student->last_name, 0, 1);
            $printName = trim($firstInit . '. ' . $lastInit . '.');

            $options = [
                'isReconfirm' => $currentStatus !== PracticeStatusEnum::CREATED->value,
                'updateLink' => $frontendUrl,
                'printName' => $printName,
                'actionType' => 'agreement',
            ];

            Mail::to($practice->practiceCompany->contact_email)->send(new AgreementConfirmationRequestedMail($practice, $options));
        }

        return response()->json(['success' => true, 'statusCode' => 200, 'message' => __('practice.agreement_approval_requested_successfully')]);
    }

    public function downloadReport($id, Request $request)
    {
        $practice = $this->getPracticeForStudentDocument($request, (int) $id);

        $values = [
            'student_full_name' => $practice->student->user->name ?? trim(($practice->student->first_name ?? '') . ' ' . ($practice->student->last_name ?? '')),
            'student_study_program' => $practice->studyProgram->name ?? '',
            'student_school_name' => 'FPVaI UKF v Nitre',
            'company_name' => $practice->practiceCompany->name ?? '',
            'company_address' => $practice->practiceCompany->address ?? '',
            'company_contact_name' => $practice->practiceCompany->contact_name ?? '',
            'practice_start_date' => $practice->start_date?->format('d.m.Y') ?? '—',
            'practice_end_date' => $practice->end_date?->format('d.m.Y') ?? '—',
            'practice_hours' => '150',
        ];

        return $this->returnPracticeDocumentsAsDocx(
            practice: $practice,
            s3Path: 'templates/Priloha_Vykaz_o_vykonanej_odbornej_praxi-AI.docx',
            values: $values,
            filenamePrefix: 'report_practice'
        );
    }

    public function updateDocumentStatus($id, UpdateDocumentStatusRequest $request){
        $user = $request->user();
        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);
        $practice = Practice::where('id', $id)->first();

        $allowedStatuses = [
            'agreement' => PracticeStatusEnum::AGREEMENT_CONFIRM_REQUESTED,
            'report' => PracticeStatusEnum::REPORT_CONFIRM_REQUESTED,
        ];

        $documentType = $request->input('document_type');
        $statusAction = $request->input('status');

        if(! $practice->lastStatusIs($allowedStatuses[$documentType])){
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => __('practice.cannot_upload_document_in_this_status'),
            ]);
        }
        $student = User::where('id', $practice->student->user_id)->first();
        if ($documentType === 'agreement') {
            if ($statusAction === 'agree') {
                $status = $isCompany
                    ? PracticeStatusEnum::AGREEMENT_CONFIRMED_BY_COMPANY->value
                    : PracticeStatusEnum::AGREEMENT_CONFIRMED_BY_SUPERVISOR->value;
            } elseif ($statusAction === 'reject') {
                $status = $isCompany
                    ? PracticeStatusEnum::AGREEMENT_REJECTED_BY_COMPANY->value
                    : PracticeStatusEnum::AGREEMENT_REJECTED_BY_SUPERVISOR->value;
            }
        } elseif ($documentType === 'report') {
            if ($statusAction === 'agree') {
                $status = $isCompany
                    ? PracticeStatusEnum::REPORT_CONFIRMED_BY_COMPANY->value
                    : PracticeStatusEnum::REPORT_CONFIRMED_BY_SUPERVISOR->value;
            } elseif ($statusAction === 'reject') {
                $status = $isCompany
                    ? PracticeStatusEnum::REPORT_REJECTED_BY_COMPANY->value
                    : PracticeStatusEnum::REPORT_REJECTED_BY_SUPERVISOR->value;
            }
        }

        PracticeStatusHistory::create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'status' => $status,
            'comment' => $request->input('comment') ?: null,
        ]);

        $practice->status = $status;
        $practice->save();

        $mailStatus = ($statusAction === 'agree') ? __('practice.document_confirmed', locale: $student->language) : __('practice.document_rejected', locale: $student->language);
        Mail::to($student)->send(new NotifyStudentAgreementStatusMail($practice, $mailStatus, $user));

        $supervisors = Supervisor::all();
        foreach ($supervisors as $supervisor) {
            $to = User::where('id', $supervisor->user_id)->first();
            $mailStatus = ($statusAction === 'agree') ? __('practice.document_confirmed', locale: $to->language) : __('practice.document_rejected', locale: $to->language);
            Mail::to($to)->send(new NotifySupervisorAgreementStatusMail($practice, $mailStatus, $user));
        }

        return response()->json(['success' => true, 'statusCode' => 200, 'message' => __('practice.updated_successfully')]);

    }

    public function downloadDocument($practice_id, DownloadDocumentRequest $request){
        $user = $request->user();
        $practice = Practice::where('id', $practice_id)->first();

        if (!$practice) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => __('practice.not_found'),
            ]);
        }

        $isStudent = $user->hasRoleId(RoleEnum::STUDENT->value);
        $isSupervisor = $user->hasRoleId(RoleEnum::SUPERVISOR->value);
        $isCompany = $user->hasRoleId(RoleEnum::COMPANY->value);

        if (
            !(
                ($isStudent && $practice->student_id === $user->id) ||
                $isSupervisor ||
                ($isCompany && $practice->company_id === $user->id)
            )
        ) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => __('practice.document_download_not_allowed'),
            ], 403);
        }

        $filePath = $request->input('file_path');

        if (!$filePath || !Storage::disk('s3')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => __('practice.file_not_found'),
            ], 404);
        }

        $url = Storage::disk('s3')->temporaryUrl(
            $filePath,
            now()->addMinutes(5),
            [
                'ResponseContentType' => 'application/octet-stream',
                'ResponseContentDisposition' => 'attachment; filename="' . basename($filePath) . '"',
            ]
        );

        return response()->json(['url' => $url]);
    }

    public function deleteDocument($practice_id, DownloadDocumentRequest $request)
    {
        $user = $request->user();
        $filePath = $request->input('file_path');

        $practice = Practice::find($practice_id);
        if (!$practice) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => __('practice.not_found'),
            ]);
        }

        if (!$user->student || $practice->student_id !== $user->id) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => __('practice.not_your_practice'),
            ]);
        }

        $document = Document::where('file_path', $filePath)->first();
        if (!$document) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => __('practice.document_doesnt_exist'),
            ]);
        }

        if (!$practice->hasDocument($filePath)) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => __('practice.document_does_not_belong_to_practice'),
            ]);
        }

        Storage::disk('s3')->delete($filePath);
        $document->delete();

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => __('practice.document_deleted_successfully'),
        ]);
    }

    private function returnPracticeDocumentsAsDocx(Practice $practice, string $s3Path, array $values, string $filenamePrefix)
    {
        try {
            $templateContent = Storage::disk('s3')->get($s3Path);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'statusCode' => 500, 'message' => __('practice.document_doesnt_exist')], 500);
        }

        try {
            $tmpDir = sys_get_temp_dir();
            $tmpTemplate = $tmpDir . DIRECTORY_SEPARATOR . 'tpl_' . bin2hex(random_bytes(12)) . '.docx';
            file_put_contents($tmpTemplate, $templateContent, LOCK_EX);
        } catch (\Exception $e) {
            if (isset($tmpTemplate) && file_exists($tmpTemplate)) {
                @unlink($tmpTemplate);
            }

            return response()->json(['success' => false, 'statusCode' => 500, 'message' => __('practice.document_processing_failed')], 500);
        }

        try {
            $templateProcessor = new TemplateProcessor($tmpTemplate);
            foreach ($values as $key => $val) {
                $templateProcessor->setValue($key, $val === null ? '' : $val);
            }
            $outPath = $tmpDir . DIRECTORY_SEPARATOR . $filenamePrefix . '_' . $practice->id . '_' . bin2hex(random_bytes(12)) . '.docx';
            $templateProcessor->saveAs($outPath);
        } catch (\Exception $e) {
            if (isset($outPath) && file_exists($outPath)) {
                @unlink($outPath);
            }

            if (isset($tmpTemplate) && file_exists($tmpTemplate)) {
                @unlink($tmpTemplate);
            }

            return response()->json(['success' => false, 'statusCode' => 500, 'message' => __('practice.document_generation_failed')], 500);
        }

        $filename = $filenamePrefix . '_' . $practice->id . '.docx';

        return response()->streamDownload(function () use ($outPath, $tmpTemplate) {
            readfile($outPath);
            @unlink($outPath);
            @unlink($tmpTemplate);
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    private function getPracticeForStudentDocument(Request $request, int $id)
    {
        $user = $request->user();
        $isStudent = $user && $user->hasRoleId(RoleEnum::STUDENT->value);

        if (!$isStudent) {
            return response()->json(['success' => false, 'statusCode' => 403, 'message' => __('practice.document_download_not_allowed')], 403);
        }

        $with = ['studyProgram', 'practiceCompany', 'student'];
        $practice = Practice::query()
            ->where('student_id', $user->id)
            ->where('id', $id)
            ->with($with)
            ->first();

        if (!$practice) {
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('practice.not_found')], 404);
        }

        return $practice;
    }
}
