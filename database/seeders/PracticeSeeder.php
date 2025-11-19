<?php

namespace Database\Seeders;

use App\Enums\PracticeStatusEnum;
use App\Enums\SemesterEnum;
use App\Models\Company;
use App\Models\Practice;
use App\Models\PracticeCompany;
use App\Models\PracticeStatusHistory;
use Database\Factories\PracticeCompanyFactory;
use Database\Factories\PracticeStatusHistoryFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

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
                'semester' => SemesterEnum::WINTER->value,
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
                'semester' => SemesterEnum::WINTER->value,
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
                'semester' => SemesterEnum::SUMMER->value,
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
                'semester' => SemesterEnum::WINTER->value,
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
                        'ico' => $company->ico,
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
