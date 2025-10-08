<?php

namespace Database\Seeders;

use App\Models\StudyProgram;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudyProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
    }
}
