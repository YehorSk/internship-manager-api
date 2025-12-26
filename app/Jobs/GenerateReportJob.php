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
                    $query = Practice::query()
                        ->from('practices')
                        ->leftJoin('students', 'students.user_id', '=', 'practices.student_id')
                        ->leftJoin('users', 'users.id', '=', 'students.user_id')
                        ->leftJoin('practice_companies', 'practice_companies.practice_id', '=', 'practices.id')
                        ->leftJoin('study_programs', 'study_programs.id', '=', 'practices.study_program_id')
                        ->select(['practices.id as id', 'practices.id as practice_id', 'users.name as student_name', 'students.student_email as student_email', 'practice_companies.name as company_name', 'practices.academic_year', 'practices.semester', 'study_programs.name as study_program', 'practices.start_date', 'practices.end_date', 'practices.status',])
                        ->when(!empty($filter['company_name']), function ($q) use ($filter) {
                            $cname = trim($filter['company_name']);

                            if ($cname !== '') {
                                $q->where('practice_companies.name', 'like', "%{$cname}%");
                            }
                        })
                        ->when(!empty($filter['study_program_name']), function ($q) use ($filter) {
                            $pname = trim($filter['study_program_name']);

                            if ($pname !== '') {
                                $q->where('study_programs.name', 'like', "%{$pname}%");
                            }
                        })
                        ->when(!empty($filter['academic_year']), fn($q) => $q->where('practices.academic_year', $filter['academic_year']))
                        ->when(!empty($filter['semester']), fn($q) => $q->where('practices.semester', $filter['semester']))
                        ->when(!empty($filter['status']), fn($q) => $q->where('practices.status', $filter['status']))
                        ->orderBy('practices.id');
                    $query
                        ->lazyById(100, column: 'practices.id', alias: 'id')
                        ->each(function ($row) use ($stream, &$rowsCount) {
                            fputcsv($stream, [$row->practice_id, trim($row->student_name ?? ''), $row->student_email ?? '', $row->company_name ?? '', $row->academic_year, $row->semester, $row->study_program ?? '', $row->start_date ?? '', $row->end_date ?? '', $row->status,]);
                            $rowsCount++;
                        });
                    break;
                case ReportTypeEnum::PRACTICES_STATUS_SUMMARY->value:
                    fputcsv($stream, ['status', 'count']);
                    $rowsCount++;

                    $practicesQuery = Practice::query()
                        ->from('practices')
                        ->when(!empty($filter['company_name']), function ($q) {
                            $q->leftJoin('practice_companies', 'practice_companies.practice_id', '=', 'practices.id');
                        })
                        ->when(!empty($filter['study_program_name']), function ($q) {
                            $q->leftJoin('study_programs', 'study_programs.id', '=', 'practices.study_program_id');
                        })
                        ->when(!empty($filter['company_name']), function ($q) use ($filter) {
                            $cname = trim($filter['company_name']);

                            if ($cname !== '') {
                                $q->where('practice_companies.name', 'like', "%{$cname}%");
                            }
                        })
                        ->when(!empty($filter['study_program_name']), function ($q) use ($filter) {
                            $pname = trim($filter['study_program_name']);

                            if ($pname !== '') {
                                $q->where('study_programs.name', 'like', "%{$pname}%");
                            }
                        })
                        ->when(!empty($filter['academic_year']), fn($q) => $q->where('practices.academic_year', $filter['academic_year']))
                        ->when(!empty($filter['semester']), fn($q) => $q->where('practices.semester', $filter['semester']))
                        ->when(!empty($filter['start_date']), fn($q) => $q->where('practices.start_date', '>=', $filter['start_date']))
                        ->when(!empty($filter['end_date']), fn($q) => $q->where('practices.end_date', '<=', $filter['end_date']))
                        ->when(!empty($filter['status']), fn($q) => $q->where('practices.status', $filter['status']))
                        ->selectRaw('practices.status, count(*) as cnt')
                        ->groupBy('practices.status')
                        ->orderByDesc('cnt');

                    foreach ($practicesQuery->get() as $practice) {
                        fputcsv($stream, [$practice->status, $practice->cnt]);
                        $rowsCount++;
                    }

                    break;
                case ReportTypeEnum::COMPANIES_WITHOUT_ACTIVATION->value:
                    $rowsCount += $this->writeCompaniesCsv($stream, $filter, function ($q) {
                        $q->where('status', 0);
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

    private function writeCompaniesCsv($stream, array $filter, ?callable $modifier = null): int
    {
        $rows = 0;
        fputcsv($stream, ['user_id', 'name', 'address', 'contact_name', 'contact_position', 'company_email', 'contact_email', 'contact_phone', 'created_at']);
        $rows++;
        $query = Company::query()->select(['user_id', 'name', 'address', 'contact_name', 'contact_position', 'company_email', 'contact_email', 'contact_phone', 'created_at']);

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
