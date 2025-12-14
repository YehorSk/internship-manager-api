<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    private Supervisor $supervisor;
    private Student $student;
    private Company $company;

    private $email_verified_at;
    private $password;
    private $activation_token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supervisor = Supervisor::first();
        $this->company = Company::where('status', true)->first();
        $this->activation_token = $this->company->activation_token;
        $this->email_verified_at = $this->company->user->email_verified_at;
        $this->password = $this->company->user->password;
        $this->student = Student::first();
        Mail::fake();
    }

    public function test_supervisor_can_list_companies(): void
    {
        Passport::actingAs($this->supervisor->user, ['*']);
        $response = $this->postJson('/api/companies/list', [
            'page' => 1,
            'itemsPerPage' => 10,
            'sortBy' => 'id',
            'sortOrder' => 'desc',
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'name', 'address', 'contact_position', 'contact_name', 'company_email', 'contact_email', 'contact_phone', 'registered_by', 'status', 'ico']
                ],
            ]);
        $this->assertGreaterThanOrEqual(0, count($response['data']['data'] ?? []));
    }

    public function test_student_cannot_access_list_companies(): void
    {
        Passport::actingAs($this->student->user, ['*']);
        $response = $this->postJson('/api/companies/list');
        $response->assertStatus(403);
    }

    public function test_company_cannot_access_list_companies(): void
    {
        Passport::actingAs($this->company->user, ['*']);
        $response = $this->postJson('/api/companies/list');
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_list_companies(): void
    {
        $response = $this->postJson('/api/companies/list');
        $response->assertStatus(401);
    }

    public function test_supervisor_can_show_company(): void
    {
        Passport::actingAs($this->supervisor->user, ['*']);
        $response = $this->getJson('/api/companies/' . $this->company->user_id);
        $response->assertStatus(200)
            ->assertJsonPath('id', $this->company->id)
            ->assertJsonPath('user_id', $this->company->user_id);
    }

    public function test_student_cannot_show_company(): void
    {
        Passport::actingAs($this->student->user, ['*']);
        $response = $this->getJson('/api/companies/' . $this->company->user_id);
        $response->assertStatus(403);
    }

    public function test_company_cannot_show_company(): void
    {
        Passport::actingAs($this->company->user, ['*']);
        $response = $this->getJson('/api/companies/' . $this->company->user_id);
        $response->assertStatus(403);
    }

    public function test_guest_cannot_show_company(): void
    {
        $response = $this->getJson('/api/companies/' . $this->company->user_id);
        $response->assertStatus(401);
    }

    public function test_supervisor_can_update_company_status(): void
    {
        Passport::actingAs($this->supervisor->user, ['*']);
        $response = $this->patchJson('/api/companies/' . $this->company->user_id, [
            'status' => false,
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('companies', [
            'id' => $this->company->id,
            'status' => false,
        ]);
    }

    public function test_activate_company(): void
    {
        $token = 'test_token_' . time();
        $this->company->update([
            'status' => 0,
            'activation_token' => $token,
        ]);
        $this->company->user()->update([
            'email_verified_at' => null,
        ]);
        $response = $this->getJson('/api/company/activate/' . $token);
        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->company->refresh();
        $this->assertNull($this->company->activation_token);
        $this->assertNotNull($this->company->user->email_verified_at);
        $this->assertTrue($this->company->user->fresh()->hasVerifiedEmail());
    }

    public function test_activate_company_with_data(): void
    {
        $token = 'test_token_' . time();
        $this->company->update([
            'status' => 0,
            'activation_token' => $token,
        ]);
        $this->company->user()->update([
            'email_verified_at' => null,
        ]);
        $payload = [
            'email' => $this->company->user->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];
        $response = $this->postJson('/api/company/activate-data/' . $token, $payload);
        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->company->refresh();
        $this->assertNull($this->company->activation_token);
        $this->assertNotNull($this->company->user->email_verified_at);
        $this->assertTrue($this->company->user->fresh()->hasVerifiedEmail());
    }

    public function test_supervisor_search_companies(): void
    {
        Passport::actingAs($this->supervisor->user, ['*']);
        $response = $this->getJson('/api/company/search?value=Energo');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'name', 'address', 'contact_position', 'contact_name', 'company_email', 'contact_email', 'contact_phone', 'registered_by', 'status', 'ico']
                ],
            ]);
    }

    public function test_student_search_companies(): void
    {
        Passport::actingAs($this->student->user, ['*']);
        $response = $this->getJson('/api/company/search?value=Energo');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'name', 'address', 'contact_position', 'contact_name', 'company_email', 'contact_email', 'contact_phone', 'registered_by', 'status', 'ico']
                ],
            ]);
    }

    public function test_company_cannot_search_companies(): void
    {
        Passport::actingAs($this->company->user, ['*']);
        $response = $this->getJson('/api/company/search?value=Energo');
        $response->assertStatus(403);
    }

    protected function tearDown(): void
    {
        $this->company = $this->company->fresh() ?? $this->company;
        $this->company->update([
            'status' => 1,
            'activation_token' => $this->activation_token,
        ]);
        $this->company->user()->update([
            'password' => $this->password,
            'email_verified_at' => $this->email_verified_at,
        ]);
        parent::tearDown();
    }
}
