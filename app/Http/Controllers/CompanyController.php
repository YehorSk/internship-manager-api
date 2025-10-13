<?php

namespace App\Http\Controllers;

use App\Mail\CompanyActivatedMail;
use App\Mail\CompanyApprovedMail;
use App\Mail\CompanyRejectedMail;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CompanyController extends Controller
{
    public function activate($token)
    {
        $company = Company::where('activation_token', $token)->first();
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Neplatný alebo expirovaný aktivačný token.'
            ], 404);
        }
        $user = $company->user;
        if (!$user || !$user->hasRole('company')) {
            return response()->json([
                'success' => false,
                'message' => 'Používateľ nie je spoločnosť.'
            ], 404);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Účet už bol aktivovaný.'
            ], 400);
        }
        $company->activation_token = null;
        $company->save();
        $user->markEmailAsVerified();
        Mail::to($user->email)->send(new CompanyActivatedMail());
        return response()->json([
            'success' => true,
            'message' => 'Účet spoločnosti bol úspešne aktivovaný. Môžete sa prihlásiť.'
        ]);
    }
}
