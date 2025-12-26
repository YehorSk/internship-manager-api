<?php

namespace App\Jobs;

use App\Enums\ReportStatusEnum;
use App\Enums\ReportTypeEnum;
use App\Models\Report;
use App\Models\Practice;
use App\Models\Document;
use App\Models\Student;
use App\Models\Company;
use App\Models\StudyProgram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Report $report)
    {
    }

    public function handle(): void
    {
        $report = $this->report->fresh();
        $report->started_at = now();
        $report->status = ReportStatusEnum::RUNNING->value;
        $report->message = null;
        $report->save();

        try {
            $params = $report->params ?? [];
            $filter = $params['filter'] ?? [];
            $s3Filename = 'reports/' . $report->id . '_' . now()->timestamp . '.csv';
            $stream = fopen('php://temp', 'r+');

            if ($stream === false) {
                throw new \RuntimeException('Failed to open temporary stream for CSV generation');
            }

            $rowsCount = 0;
            $type = $report->report_type;

            switch ($type) {
                case ReportTypeEnum::PRACTICES_LIST->value:
                    fputcsv($stream, ['practice_id', 'student_name', 'student_email', 'company_name', 'academic_year', 'semester', 'study_program', 'start_date', 'end_date', 'status']);
                    $rowsCount++;

                    $practicesQuery = $this->buildPracticesQuery($filter, true);
                    $practicesQuery->orderBy('id');

                    foreach ($practicesQuery->cursor() as $practice) {
                        $studentName = trim($practice->student?->user?->name);
                        $studentEmail = $practice->student?->student_email;
                        $companyName = $practice->practiceCompany?->name ?? '';
                        $studyProgram = $practice->studyProgram?->name ?? '';
                        $start_date = $practice->start_date?->format('Y-m-d') ?? '';
                        $end_date = $practice->end_date?->format('Y-m-d') ?? '';
                        fputcsv($stream, [$practice->id, $studentName, $studentEmail, $companyName, $practice->academic_year, $practice->semester, $studyProgram, $start_date, $end_date, $practice->status]);
                        $rowsCount++;
                    }

                    break;
                case ReportTypeEnum::PRACTICES_STATUS_SUMMARY->value:
                    fputcsv($stream, ['status', 'count']);
                    $rowsCount++;

                    $practicesQuery = $this->buildPracticesQuery($filter, false)
                        ->select('status', DB::raw('count(*) as cnt'))
                        ->groupBy('status')
                        ->orderBy('cnt', 'desc');

                    foreach ($practicesQuery->get() as $practice) {
                        fputcsv($stream, [$practice->status, $practice->cnt]);
                        $rowsCount++;
                    }

                    break;
                case ReportTypeEnum::COMPANIES_WITHOUT_ACTIVATION->value:
                    $rowsCount += $this->writeCompaniesCsv($stream, $filter, function ($q) {
                        $q->where('status', false);
                    });
                    break;
                case ReportTypeEnum::COMPANIES_WITHOUT_PRACTICES->value:
                    $rowsCount += $this->writeCompaniesCsv($stream, $filter, function ($q) {
                        $q->whereDoesntHave('practices');
                    });
                    break;
                default:
                    throw new \RuntimeException('Unknown report type: ' . $type);
            }

            rewind($stream);
            $s3Disk = Storage::disk('s3');
            $operator = $s3Disk->getDriver();
            $uploaded = false;

            try {
                $operator->writeStream($s3Filename, $stream);
                $uploaded = true;
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (!$uploaded) {
                throw new \RuntimeException('Failed to upload report to S3');
            }

            $report->file_path = $s3Filename;
            $report->ended_at = now();
            $report->status = ReportStatusEnum::SUCCESS->value;
            $report->message = 'Rows: ' . $rowsCount;
            $report->save();
        } catch (\Throwable $e) {
            $report->ended_at = now();
            $report->status = ReportStatusEnum::FAILED->value;
            $report->message = Str::limit($e->getMessage(), 1000);
            $report->save();
        }
    }

    private function buildPracticesQuery(array $filters, bool $withRelations = false)
    {
        $practicesQuery = $withRelations ? Practice::with(['student.user', 'practiceCompany', 'studyProgram']) : Practice::query();
        $practicesQuery
            ->when(!empty($filters['company_name']), function ($q) use ($filters) {
                $cname = trim($filters['company_name']);

                if ($cname !== '') {
                    $q->whereRelation('practiceCompany', 'name', 'like', '%' . $cname . '%');
                }
            })
            ->when(!empty($filters['academic_year']), function ($q) use ($filters) {
                $q->where('academic_year', $filters['academic_year']);
            })
            ->when(!empty($filters['semester']), function ($q) use ($filters) {
                $q->where('semester', $filters['semester']);
            })
            ->when(!empty($filters['study_program_name']), function ($q) use ($filters) {
                $pname = trim($filters['study_program_name']);

                if ($pname !== '') {
                    $q->whereRelation('studyProgram', 'name', 'like', '%' . $pname . '%');
                }
            })
            ->when(!empty($filters['start_date']), function ($q) use ($filters) {
                $q->whereDate('start_date', '>=', $filters['start_date']);
            })
            ->when(!empty($filters['end_date']), function ($q) use ($filters) {
                $q->whereDate('end_date', '<=', $filters['end_date']);
            })
            ->when(!empty($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            });
        return $practicesQuery;
    }

    private function writeCompaniesCsv($stream, array $filter, ?callable $modifier = null): int
    {
        $rows = 0;
        fputcsv($stream, ['user_id', 'name', 'address', 'contact_name', 'contact_position', 'company_email', 'contact_email', 'contact_phone', 'created_at']);
        $rows++;
        $query = Company::query()->select(['user_id','name','address','contact_name','contact_position','company_email','contact_email','contact_phone','created_at']);

        if ($modifier) {
            $modifier($query);
        }

        $query->when(!empty($filter['company_name']), function ($q) use ($filter) {
            $cname = trim($filter['company_name']);

            if ($cname !== '') {
                $q->where('name', 'like', '%' . $cname . '%');
            }
        });

        $query->orderBy('id')
            ->lazyById(100, 'id')
            ->each(function ($company) use ($stream, &$rows) {
                fputcsv($stream, [$company->user_id, $company->name, $company->address, $company->contact_name, $company->contact_position, $company->company_email, $company->contact_email, $company->contact_phone, $company->created_at->format('Y-m-d H:i:s')]);
                $rows++;
            });

        return $rows;
    }
}
