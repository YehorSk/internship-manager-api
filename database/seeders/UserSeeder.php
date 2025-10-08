<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $studentUser = User::create([
            'name' => 'John Doe',
            'email' => 'john.student@example.com',
            'password' => Hash::make('password123'),
        ]);

        Student::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'student_email' => 'john.student@example.com',
            'primary_email' => 'john.alt@example.com',
            'phone' => '123456789',
            'address' => '123 Main St, Cityville',
            'user_id' => $studentUser->id,
        ]);

        $studentUser->roles()->attach(1);
    }
}
