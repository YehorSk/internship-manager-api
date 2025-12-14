<?php

namespace Database\Factories;

use App\Enums\ReportStatusEnum;
use App\Enums\ReportTypeEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'report_type' => fake()->randomElement(ReportTypeEnum::cases())->value,
            'params' => [
                'filter' => [
                    'company_name' => null,
                    'academic_year' => fake()->numberBetween(2020, 2025),
                    'semester' => fake()->randomElement([1, 2]),
                    'study_program_name' => null,
                    'start_date' => null,
                    'end_date' => null,
                    'status' => null,
                ],
                'order_by' => null,
            ],
            'file_path' => 'reports/test_' . time() . '.csv',
            'started_at' => null,
            'ended_at' => null,
            'status' => fake()->randomElement(ReportStatusEnum::cases())->value,
            'message' => null,
            'task_id' => (string) Str::uuid(),
        ];
    }
}
