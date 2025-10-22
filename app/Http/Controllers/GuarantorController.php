<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyListRequest;
use App\Mail\CompanyApprovedMail;
use App\Mail\CompanyRejectedMail;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class GuarantorController extends Controller
{
    //
    public function listCompanies(CompanyListRequest $request)
    {
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

    public function getCompany($id)
    {
        $company = Company::find($id);
        if (!$company) {
            return response()->json(['status' => false, 'message' => __('company.not_found')], 404);
        }
        return response()->json($company);
    }

    public function changeCompanyStatus(Request $request, $id)
    {
        $company = Company::find($id);
        if ($company) {
            $company->status = $request->input('status');
            $company->save();
            if ($company->status) {
                Mail::to($company->contact_email)->send(new CompanyApprovedMail($company, config('constants.MAIL_FROM_ADDRESS')));
            } else {
                Mail::to($company->contact_email)->send(new CompanyRejectedMail($company, config('constants.MAIL_FROM_ADDRESS')));
            }
            return response()->json(['message' => __('company.status_updated')], 200);
        } else {
            return response()->json(['message' => __('company.not_found')], 404);
        }
    }
}
