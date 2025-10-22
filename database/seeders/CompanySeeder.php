<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
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

        Schema::disableForeignKeyConstraints();
        Company::truncate();

        foreach ($companies as $company) {
            $user = \App\Models\User::create([
                'name' => $company['name'],
                'email' => $company['company_email'],
                'password' => $company['password'],
                'email_verified_at' => $company['email_verified_at'],
                'remember_token' => $company['remember_token'],
            ]);

            if (!$user) {
                continue;
            }

            $user->roles()->attach(RoleEnum::COMPANY->value);

            Company::create([
                'user_id' => $user->id,
                'name' => $company['name'],
                'address' => $company['address'],
                'company_email' => $company['company_email'],
                'contact_phone' => $company['contact_phone'],
                'contact_email' => $company['contact_email'],
                'contact_name' => $company['contact_name'],
                'status' => $company['status'],
            ]);
        }

    }
}
