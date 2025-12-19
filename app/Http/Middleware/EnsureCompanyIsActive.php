<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->has('company_id') && $request->input('company_id') !== null){
            $company = Company::where('user_id', $request->input('company_id'))->first();
            if(!$company){
                return response()->json(['success' => false, 'statusCode' => 404, 'message' => __('company.not_found')], 404);
            }
            if(!$company->isActive()){
                return response()->json(['success' => false, "test" => $company, 'statusCode' => 403, 'message' => __('company.not_active')], 403);
            }
        }

        return $next($request);
    }
}
