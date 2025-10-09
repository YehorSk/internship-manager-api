<?php

namespace Database\Seeders;

use App\Models\Supervisor;
use App\Models\User;
use App\RoleEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SupervisorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $supervisors = [
            [
                'name' => 'Ing.Ján Novák',
                'email' => 'jan.novak@ukf.sk',
                'password' => bcrypt('heslo'),
                'email_verified_at' => now(),
                'remember_token' => null,
                'title' => 'Ing.',
                'first_name' => 'Ján',
                'last_name' => 'Novák',
            ],
            [
                'name' => 'Mgr.Petra Kováčová',
                'email' => 'petra.kovacova@ukf.sk',
                'password' => bcrypt('heslo'),
                'email_verified_at' => now(),
                'remember_token' => null,
                'title' => 'Mgr.',
                'first_name' => 'Petra',
                'last_name' => 'Kováčová',
            ],
            [
                'name' => 'PhDr.Marek Horváth',
                'email' => 'marek.horvath@ukf.sk',
                'password' => bcrypt('heslo'),
                'email_verified_at' => now(),
                'remember_token' => null,
                'title' => 'PhDr.',
                'first_name' => 'Marek',
                'last_name' => 'Horváth',
            ],
        ];

        Schema::disableForeignKeyConstraints();
        Supervisor::truncate();

        foreach ($supervisors as $supervisor) {
            $user = User::create([
                'name' => $supervisor['name'],
                'email' => $supervisor['email'],
                'password' => $supervisor['password'],
                'email_verified_at' => $supervisor['email_verified_at'],
                'remember_token' => $supervisor['remember_token'],
            ]);

            if (!$user) {
                continue; // Skip if user creation failed
            }

            $user->roles()->attach(RoleEnum::SUPERVISOR->value);

            Supervisor::create([
                'user_id' => $user->id,
                'title' => $supervisor['title'],
                'first_name' => $supervisor['first_name'],
                'last_name' => $supervisor['last_name'],
                'email' => $supervisor['email'],
            ]);
        }

        Schema::enableForeignKeyConstraints();
    }
}
