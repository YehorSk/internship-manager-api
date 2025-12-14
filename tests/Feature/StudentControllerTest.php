<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Company;
use Laravel\Passport\Passport;
use Tests\TestCase;

class StudentControllerTest extends TestCase
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

    public function test_supervisor_can_list_students(): void
    {
        Passport::actingAs($this->supervisor->user, ['*']);
        $response = $this->postJson('/api/students/list', [
            'page' => 1,
            'itemsPerPage' => 10,
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'user_id', 'first_name', 'last_name', 'student_email', 'primary_email', 'phone', 'address',
                        'study_program' => [
                            '*' => ['id', 'code', 'name']
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page', 'from', 'last_page', 'links',
                    'per_page', 'to', 'total'
                ],

            ]);
    }

    public function test_supervisor_can_search_students(): void
    {
        Passport::actingAs($this->supervisor->user, ['*']);
        $response = $this->getJson('/api/students/search?value=John');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'user_id', 'first_name', 'last_name', 'student_email', 'primary_email', 'phone', 'address'
                    ]
                ]
            ]);
    }

    public function test_company_can_search_students(): void
    {
        Passport::actingAs($this->company->user, ['*']);
        $response = $this->getJson('/api/students/search?value=John');
        $response->assertStatus(200);
    }

    public function test_student_cannot_access_list_students(): void
    {
        Passport::actingAs($this->student->user, ['*']);
        $list = $this->postJson('/api/students/list');
        $list->assertStatus(403);
    }

    public function test_student_cannot_access_search_students(): void
    {
        Passport::actingAs($this->student->user, ['*']);
        $search = $this->getJson('/api/students/search?value=John');
        $search->assertStatus(403);
    }

    public function test_guest_cannot_access_list_students(): void
    {
        $list = $this->postJson('/api/students/list');
        $list->assertStatus(401);
    }

    public function test_guest_cannot_access_search_students(): void
    {
        $search = $this->getJson('/api/students/search?value=John');
        $search->assertStatus(401);
    }
}
