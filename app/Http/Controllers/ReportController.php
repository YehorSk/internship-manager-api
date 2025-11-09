<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportListRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;

class ReportController extends Controller
{
    public function list(ReportListRequest $request)
    {
        $user = $request->user();

        $reports = Report::query()
            ->where('user_id', $user->id)
            ->when($request->has('search.status'), function ($query) use ($request) {
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

        return response()->json($report);
    }

    public function generate()
    {

    }

    public function download()
    {

    }
}
