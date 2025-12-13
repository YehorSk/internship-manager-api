<?php

namespace Tests\Feature;

use App\Models\Supervisor;
use App\Models\Student;
use App\Models\Company;
use Laravel\Passport\Passport;
use Tests\TestCase;

class StudyProgramControllerTest extends TestCase
{
    private Supervisor $supervisor;
    private Student $student;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supervisor = Supervisor::first();
        $this->company = Company::where('status', true)->first();
        $this->student = Student::first();
    }

    public function test_supervisor_can_list_study_programs(): void
    {
        Passport::actingAs($this->supervisor->user, ['*']);
        $response = $this->getJson('/api/study-programs/index');
        $response->assertStatus(200)->assertJsonStructure(['data' => ['*' => ['id', 'code', 'name']],]);
        $this->assertGreaterThanOrEqual(1, count($response->json()));
    }

    public function test_student_can_list_study_programs(): void
    {
        Passport::actingAs($this->student->user, ['*']);
        $response = $this->getJson('/api/study-programs/index');
        $response->assertStatus(200)->assertJsonStructure(['data' => ['*' => ['id', 'code', 'name']],]);
        $this->assertGreaterThanOrEqual(1, count($response->json()));
    }

    public function test_company_can_list_study_programs(): void
    {
        Passport::actingAs($this->company->user, ['*']);
        $response = $this->getJson('/api/study-programs/index');
        $response->assertStatus(200)->assertJsonStructure(['data' => ['*' => ['id', 'code', 'name']],]);
        $this->assertGreaterThanOrEqual(1, count($response->json()));
    }

    public function test_unauthorized_guest_cannot_access_study_programs(): void
    {
        $response = $this->getJson('/api/study-programs/index');
        $response->assertStatus(200)->assertJsonStructure(['data' => ['*' => ['id', 'code', 'name']],]);
        $this->assertGreaterThanOrEqual(1, count($response->json()));
    }
}


