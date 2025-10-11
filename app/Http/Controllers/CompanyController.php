<?php

namespace App\Http\Controllers;

use App\Mail\CompanyApprovedMail;
use App\Mail\CompanyRejectedMail;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $companies = Company::query()
            ->when($request->has('search.status'), function ($query) use ($request) {
                $status = $request->input('search.status');
                if ($status === true) {
                    $query->where('status', true);
                } elseif ($status === false) {
                    $query->where('status', false);
                }
            })
            ->when($request->has('search.name'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('search.name') . '%');
            })
            ->when($request->has('search.address'), function ($query) use ($request) {
                $query->where('address', 'like', '%' . $request->input('search.address') . '%');
            })
            ->when($request->has('search.contact_name'), function ($query) use ($request) {
                $query->where('contact_name', 'like', '%' . $request->input('search.contact_name') . '%');
            })
            ->when($request->has('search.contact_email'), function ($query) use ($request) {
                $query->where('contact_email', 'like', '%' . $request->input('search.contact_email') . '%');
            })
            ->when($request->has('search.contact_phone'), function ($query) use ($request) {
                $query->where('contact_phone', 'like', '%' . $request->input('search.contact_phone') . '%');
            })
            ->orderBy($request->get('sortBy', 'id'), $request->get('sortOrder', 'asc'))
            ->paginate($request->get('itemsPerPage', 10), ['*'], 'page', $request->get('page', 1));

        return response()->json($companies);
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
