<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Jobs\CheckIsCompanyActivated;
use App\Mail\CompanyConfirmationMail;
use App\Models\Company;
use App\Models\User;
use App\Services\Contracts\CompanyServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CompanyService implements CompanyServiceInterface
{

    public function studentRegisterCompany($data, $student, $practice): Company
    {
       return DB::transaction(function () use ($data, $student, $practice) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['company_email'],
                'password' => Hash::make(Str::random(12)),
            ]);
           $activationToken = Str::random(64);
           $company = Company::create([
                'name' => $data['name'],
                'address' => $data['address'],
                'contact_name' => $data['contact_name'],
                'contact_position' => $data['contact_position'],
                'company_email' => $data['company_email'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'],
                'registered_by' => $student->id,
                'ico' => $data['ico'],
                'user_id' => $user->id,
                'activation_token' => $activationToken,
            ]);
           $user->company()->save($company);
           $user->roles()->attach(RoleEnum::COMPANY->value);
           Mail::to($company->company_email)->send(new CompanyConfirmationMail($company, true));
           CheckIsCompanyActivated::dispatch($company, $practice)
               ->delay(now()->addDay());
           return $company;
        });
    }
}
