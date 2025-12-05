<?php

namespace App\Services\Contracts;

use App\Models\Company;

interface CompanyServiceInterface
{
    public function studentRegisterCompany($company, $student, $practice): Company;

}
