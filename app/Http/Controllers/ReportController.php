<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatusEnum;
use App\Http\Requests\GenerateReportRequest;
use App\Http\Requests\ReportListRequest;
use App\Http\Resources\ReportResource;
use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Models\Company;
use App\Models\StudyProgram;
use App\Models\Practice;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function list(ReportListRequest $request)
    {
        $user = $request->user();

        $reports = Report::query()
            ->where('user_id', $user->id)
            ->when($request->filled('search.status'), function ($query) use ($request) {
                $query->where('status', $request->input('search.status'));
            })
            ->when($request->filled('search.report_type'), function ($query) use ($request) {
                $query->where('report_type', $request->input('search.report_type'));
            })
            ->when($request->filled('search.task_id'), function ($query) use ($request) {
                $query->where('task_id', $request->input('search.task_id'));
            })
            ->when($request->filled('search.started_at'), function ($query) use ($request) {
                $query->whereDate('started_at', $request->input('search.started_at'));
            })
            ->when($request->filled('search.ended_at'), function ($query) use ($request) {
                $query->whereDate('ended_at', $request->input('search.ended_at'));
            })
            ->orderBy($request->input('sortBy', 'id'), $request->input('sortOrder', 'asc'))
            ->paginate($request->input('itemsPerPage', 10), ['*'], 'page', $request->input('page', 1));

        return ReportResource::collection($reports);
    }

    public function show($id)
    {
        $report = Report::where('id', $id)->first();

        if (!$report) {
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('report.not_found')], 404);
        }

        return new ReportResource($report);
    }

    public function generate(GenerateReportRequest $request)
    {
        $validated = $request->validated();

        $params = [
            'filter' => [
                'company_name' => $validated['company_name'] ?? null,
                'academic_year' => $validated['academic_year'] ?? null,
                'semester' => $validated['semester'],
                'study_program_name' => $validated['study_program_name'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status' => $validated['status'] ?? null,
            ],
            'order_by' => null,
        ];

        $user = $request->user();

        $report = Report::create([
            'user_id' => $user->id,
            'report_type' => $validated['report_type'],
            'params' => $params,
            'status' => ReportStatusEnum::PENDING->value,
        ]);

        $taskId = (string)Str::uuid();
        $report->task_id = $taskId;
        $report->save();

        GenerateReportJob::dispatch($report)->onQueue('reports');

        return (new ReportResource($report))->response()->setStatusCode(201);
    }

    public function download(Request $request, $id)
    {
        $user = $request->user();
        $report = Report::where('id', $id)->where('user_id', $user->id)->first();

        if (!$report) {
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('report.not_found')], 404);
        }

        if (empty($report->file_path) || !Storage::disk('s3')->exists($report->file_path)) {
            return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('report.file_not_found')], 404);
        }

        $s3Stream = Storage::disk('s3')->readStream($report->file_path);

        if ($s3Stream === false) {
            return response()->json(['success' => false, 'statusCode' => 500, 'message' => __('Failed to open stream')], 500);
        }

        return response()->streamDownload(function () use ($s3Stream) {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                if (is_resource($s3Stream)) {
                    fclose($s3Stream);
                }
                return;
            }

            stream_copy_to_stream($s3Stream, $out);

            if (is_resource($out)) {
                fclose($out);
            }

            if (is_resource($s3Stream)) {
                fclose($s3Stream);
            }
        }, basename($report->file_path));
    }

    public function academicYears(Request $request)
    {
        $years = Practice::query()->distinct()->orderBy('academic_year', 'desc')->pluck('academic_year');
        return response()->json($years);
    }
}
