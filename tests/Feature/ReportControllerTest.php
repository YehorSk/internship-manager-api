<?php

namespace Tests\Feature;

use App\Enums\ReportStatusEnum;
use App\Enums\ReportTypeEnum;
use App\Enums\SemesterEnum;
use App\Models\Company;
use App\Models\Report;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    private Supervisor $supervisor;
    private Student $student;
    private Company $company;
    private $reportFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supervisor = Supervisor::first();
        $this->company = Company::where('status', true)->first();
        $this->student = Student::first();

    }

    public function test_supervisor_can_list_reports(): void
    {
        Passport::actingAs($this->supervisor->user, ['*']);

        Report::factory()->count(3)->create([
            'user_id' => $this->supervisor->user->id,
        ]);

        $response = $this->postJson('/api/reports/list', [
            'page' => 1,
            'itemsPerPage' => 10,
            'sortBy' => 'id',
            'sortOrder' => 'desc',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'report_type', 'status', 'started_at', 'ended_at']
                ],
            ]);

        $this->assertGreaterThanOrEqual(3, count($response['data']));
    }

    /**
     * QUEUE_CONNECTION=sync в .env.testing, поэтому задача выполняется синхронно
     */
    public function test_supervisor_can_generate_report(): void
    {
        Passport::actingAs($this->supervisor->user, ['*']);

        $response = $this->postJson('/api/reports/generate', [
            'report_type' => ReportTypeEnum::PRACTICES_LIST->value,
            'academic_year' => "2025/2026",
            'semester' => SemesterEnum::SUMMER->value,
        ]);

        $response->assertStatus(201)->assertJsonStructure(['data' => ['id', 'status', 'task_id', 'report_type']]);

        $this->assertDatabaseHas('reports', [
            'id' => $response['data']['id'],
            'user_id' => $this->supervisor->user->id,
            'report_type' => ReportTypeEnum::PRACTICES_LIST->value,
        ]);

        $report = Report::find($response['data']['id']);

        $this->assertNotEquals(ReportStatusEnum::FAILED->value, $report?->status);

        if ($report?->status === ReportStatusEnum::SUCCESS->value) {
            $this->assertNotNull($report->file_path);
            $this->assertTrue(Storage::disk('s3')->exists($report->file_path));
            Storage::disk('s3')->delete($report->file_path);
        }

        Report::where('id', $report?->id)->delete();
    }

    public function test_supervisor_can_show_report(): void
    {
        Passport::actingAs($this->supervisor->user, ['*']);

        $file_path = 'reports/test_' . time() . '.csv';

        Storage::disk('s3')->put($file_path, "id,name\n1,test");

        $report = Report::factory()->create([
            'user_id' => $this->supervisor->user->id,
            'file_path' => $file_path,
            'status' => ReportStatusEnum::SUCCESS->value,
        ]);

        $response = $this->getJson("/api/reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonPath('data.user_id', $this->supervisor->user->id);
    }

    public function test_supervisor_can_download_report(): void
    {
        Passport::actingAs($this->supervisor->user, ['*']);

        $this->reportFilePath = 'reports/test_' . time() . '.csv';

        Storage::disk('s3')->put($this->reportFilePath, "id,name\n1,test");

        $report = Report::factory()->create([
            'user_id' => $this->supervisor->user->id,
            'file_path' => $this->reportFilePath,
            'status' => ReportStatusEnum::SUCCESS->value,
        ]);

        $response = $this->getJson("/api/reports/{$report->id}/download");

        $response->assertStatus(200);

        $content = $response->getContent();

        if (is_bool($content)) {
            $content = Storage::disk('s3')->get($this->reportFilePath);
        }

        $this->assertIsString($content);
        $this->assertGreaterThan(0, strlen($content));
    }

    public function test_unauthorized_guest_cannot_access_reports(): void
    {
        $response = $this->postJson('/api/reports/list');
        $response->assertStatus(401);
    }

    public function test_student_cannot_access_reports(): void
    {
        Passport::actingAs($this->student->user, ['*']);
        $response = $this->postJson('/api/reports/list');
        $response->assertStatus(403);
    }

    public function test_company_cannot_access_reports(): void
    {
        Passport::actingAs($this->company->user, ['*']);
        $response = $this->postJson('/api/reports/list');
        $response->assertStatus(403);
    }

    protected function tearDown(): void
    {
        $testFiles = Storage::disk('s3')->files('reports');

        foreach ($testFiles as $file) {
            if (str_contains($file, 'test_')) {
                Storage::disk('s3')->delete($file);
            }
        }

        Report::where('user_id', $this->supervisor->user->id)->where('file_path', 'like', 'reports/test_%')
            ->delete();

        parent::tearDown();
    }
}
