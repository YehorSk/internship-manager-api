<?php

namespace Database\Seeders;

use App\Enums\PracticeStatusEnum;
use App\Models\Company;
use App\Models\Practice;
use App\Models\PracticeCompany;
use App\Models\PracticeStatusHistory;
use Database\Factories\PracticeCompanyFactory;
use Database\Factories\PracticeStatusHistoryFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/*
        Schema::create('practices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->enum('semester', ['summer', 'winter']);
            $table->string('academic_year', 9);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', [
                'created',
                'agreement_confirm_requested',
                'agreement_confirmed_by_company',
                'agreement_confirmed_by_supervisor',
                'agreement_rejected_by_company',
                'agreement_rejected_by_supervisor',
                'report_confirm_requested',
                'report_confirmed_by_company',
                'report_confirmed_by_supervisor',
                'report_rejected_by_company',
                'report_rejected_by_supervisor',
                'canceled',
            ])->default('created');
            $table->string('job_title')->nullable();
            $table->string('job_description')->nullable();
            $table->unsignedBigInteger('study_program_id');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('study_program_id')->references('id')->on('study_programs')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('practice_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('practice_id');
            $table->string('name');
            $table->string('address');
            $table->string('company_email');
            $table->string('contact_phone', 50);
            $table->string('contact_email');
            $table->string('contact_name');
            $table->foreign('practice_id')->references('id')->on('practices')->
                onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('practice_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('practice_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', [
                'created',
                'agreement_confirm_requested',
                'agreement_confirmed_by_company',
                'agreement_confirmed_by_supervisor',
                'agreement_rejected_by_company',
                'agreement_rejected_by_supervisor',
                'report_confirm_requested',
                'report_confirmed_by_company',
                'report_confirmed_by_supervisor',
                'report_rejected_by_company',
                'report_rejected_by_supervisor',
                'canceled',
            ])->nullable();
            $table->text('comment')->nullable();
            $table->foreign('practice_id')->references('id')->on('practices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('student_email');
            $table->string('primary_email');
            $table->string('phone');
            $table->string('address');
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('address');
            $table->string('contact_name');
            $table->string('company_email')->unique();
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        $companies = [
            [
                'name' => 'Slovenské IT s.r.o.',
                'email' => 'michal.kral@slovenskeit.sk',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'remember_token' => null,
                'address' => 'Mlynské nivy 16, 821 09 Bratislava',
                'company_email' => 'slovenske@gmail.com',
                'contact_phone' => '+421-2-5555-1234',
                'contact_email' => 'michal.kral@slovenskeit.sk',
                'contact_name' => 'Michal Kráľ',
                'status' => 1,
            ],
            [
                'name' => 'Energo Slovensko a.s.',
                'email' => 'veronika.holubova@energosk.sk',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'remember_token' => null,
                'address' => 'Štefánikova 24, 811 05 Bratislava',
                'company_email' => 'energo@gmail.com',
                'contact_phone' => '+421-2-4444-5678',
                'contact_email' => 'veronika.holubova@energosk.sk',
                'contact_name' => 'Veronika Holubová',
                'status' => 1,
            ],
            [
                'name' => 'Zdravie Plus s.r.o.',
                'email' => 'lucia.benesova@zdravieplus.sk',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'remember_token' => null,
                'address' => 'Kukučínova 7, 040 01 Košice',
                'company_email' => 'zdravie@gmail.com',
                'contact_phone' => '+421-55-123-4567',
                'contact_email' => 'lucia.benesova@zdravieplus.sk',
                'contact_name' => 'Lucia Benešová',
                'status' => 0,
            ],
        ];

        $programs = [
            ['code' => 'INF-B', 'name' => 'Bachelor in Informatics'],
            ['code' => 'INF-M', 'name' => 'Master in Informatics'],
            ['code' => 'MAT-B', 'name' => 'Bachelor in Mathematics'],
            ['code' => 'MAT-M', 'name' => 'Master in Mathematics'],
            ['code' => 'PHY-B', 'name' => 'Bachelor in Physics'],
            ['code' => 'PHY-M', 'name' => 'Master in Physics'],
            ['code' => 'CHE-B', 'name' => 'Bachelor in Chemistry'],
            ['code' => 'BIO-B', 'name' => 'Bachelor in Biology'],
            ['code' => 'PED-B', 'name' => 'Bachelor in Pedagogy'],
            ['code' => 'ENG-B', 'name' => 'Bachelor in English Philology'],
        ];

        foreach ($programs as $program) {
            StudyProgram::updateOrCreate(
                ['code' => $program['code']],
                ['name' => $program['name']]
            );
        }

        Student::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'student_email' => 'john.student@example.com',
            'primary_email' => 'john.alt@example.com',
            'phone' => '123456789',
            'address' => '123 Main St, Cityville',
            'user_id' => $studentUser->id,
        ]);
 */

class PracticeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $practices = [
            [
                'student_id' => 1,
                'company_id' => 5,
                'semester' => 'winter',
                'academic_year' => '2025/2026',
                'start_date' => '2025-10-22',
                'end_date' => '2025-12-23',
                'status' => PracticeStatusEnum::CREATED->value,
                'job_title' => 'Software Developer Intern',
                'job_description' => 'Assist in developing web applications.',
                'study_program_id' => 1,
            ],
            [
                'student_id' => 1,
                'company_id' => 6,
                'semester' => 'winter',
                'academic_year' => '2025/2026',
                'start_date' => null,
                'end_date' => null,
                'status' => PracticeStatusEnum::CREATED->value,
                'job_title' => 'Data Analyst Intern',
                'job_description' => 'Support data analysis and reporting tasks.',
                'study_program_id' => 2,
            ],
            [
                'student_id' => 1,
                'company_id' => null,
                'semester' => 'summer',
                'academic_year' => '2025/2026',
                'start_date' => null,
                'end_date' => null,
                'status' => PracticeStatusEnum::CREATED->value,
                'job_title' => 'Marketing Intern',
                'job_description' => 'Help with marketing campaigns and social media.',
                'study_program_id' => 1,
            ],
            [
                'student_id' => 1,
                'company_id' => null,
                'semester' => 'winter',
                'academic_year' => '2025/2026',
                'start_date' => '2025-10-01',
                'end_date' => '2025-11-30',
                'status' => PracticeStatusEnum::CREATED->value,
                'job_title' => 'Research Intern',
                'job_description' => 'Assist in academic research projects.',
                'study_program_id' => 2,
            ],
        ];

        Schema::disableForeignKeyConstraints();

        Practice::truncate();
        PracticeCompany::truncate();
        PracticeStatusHistory::truncate();

        foreach ($practices as $practice) {

            $practiceModel = Practice::create($practice);

            $project_id = $practiceModel->id;

            if (!empty($practice['company_id'])) {
                $company = Company::where('user_id', $practice['company_id'])->first();

                if ($company) {
                    PracticeCompany::create([
                        'practice_id' => $project_id,
                        'name' => $company->name,
                        'address' => $company->address,
                        'company_email' => $company->company_email,
                        'contact_phone' => $company->contact_phone,
                        'contact_email' => $company->contact_email,
                        'contact_name' => $company->contact_name,
                        'contact_position' => $company->contact_position,
                    ]);
                } else {
                    $practiceModel->update(['company_id' => null]);

                    PracticeCompanyFactory::new()->create([
                        'practice_id' => $project_id,
                    ]);
                }
            } else {
                PracticeCompanyFactory::new()->create([
                    'practice_id' => $project_id,
                ]);
            }

            PracticeStatusHistory::create([
                'practice_id' => $project_id,
                'user_id' => $practice['student_id'],
                'status' => $practice['status'],
                'comment' => null,
            ]);

        }

        Schema::enableForeignKeyConstraints();
    }
}
