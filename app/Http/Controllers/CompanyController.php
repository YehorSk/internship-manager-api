<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\CompanyListRequest;
use App\Http\Resources\CompanyResource;
use App\Mail\CompanyActivatedMail;
use App\Mail\CompanyApprovedMail;
use App\Mail\CompanyRejectedMail;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CompanyController extends Controller
{

    public function search($value){
        $companies = Company::orderBy('name')
            ->where('name', 'like', '%'.$value.'%')
            ->where('status', 1)
            ->get();
        return CompanyResource::collection($companies);
    }

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

    public function list(CompanyListRequest $request)
    {
        $user = $request->user();

        $companies = Company::query()
            ->when($request->has('search.status'), function ($query) use ($request) {
                $query->where('status', $request->boolean('search.status'));
            })
            ->when($request->filled('search.name'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . trim($request->input('search.name')) . '%');
            })
            ->when($request->filled('search.address'), function ($query) use ($request) {
                $query->where('address', 'like', '%' . trim($request->input('search.address')) . '%');
            })
            ->when($request->filled('search.contact_name'), function ($query) use ($request) {
                $query->where('contact_name', 'like', '%' . trim($request->input('search.contact_name')) . '%');
            })
            ->when($request->filled('search.contact_email'), function ($query) use ($request) {
                $query->where('contact_email', 'like', '%' . trim($request->input('search.contact_email')) . '%');
            })
            ->when($request->filled('search.contact_phone'), function ($query) use ($request) {
                $query->where('contact_phone', 'like', '%' . trim($request->input('search.contact_phone')) . '%');
            })
            ->orderBy($request->input('sortBy', 'id'), $request->input('sortOrder', 'asc'))
            ->paginate($request->input('itemsPerPage', 10), ['*'], 'page', $request->input('page', 1));

        return response()->json($companies);
    }

    public function show($id)
    {
        $user = auth()->user();

        $company = Company::find($id);
        if (!$company) {
            return response()->json(['status' => false, 'message' => __('company.not_found')], 404);
        }
        return response()->json($company);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
//        $isCompany = $user && $user->hasRoleId(RoleEnum::COMPANY->value);
        $isSupervisor = $user && $user->hasRoleId(RoleEnum::SUPERVISOR->value);

        if ($request->isMethod('patch') && $isSupervisor) {
            if ($request->has('status')) {
                $company = Company::find($id);

                if ($company) {
                    $currentStatus = $company->status;
                    $newStatus = $request->boolean('status');

                    if ($currentStatus === $newStatus) {
                        return response()->json(['message' => __('company.status_unchanged')]);
                    }

                    $company->status = $newStatus;
                    $company->save();

                    if ($company->status) {
                        Mail::to($company->contact_email)->send(new CompanyApprovedMail());
                    } else {
                        Mail::to($company->contact_email)->send(new CompanyRejectedMail());
                    }

                    return response()->json(['message' => __('company.status_updated')]);
                } else {
                    return response()->json(['message' => __('company.not_found')], 404);
                }
            } else {
                return response()->json(['message' => __('No valid status provided.')], 400);
            }
        } else {
            return response()->json(['message' => __('Method not allowed.')], 405);
        }
    }
}
