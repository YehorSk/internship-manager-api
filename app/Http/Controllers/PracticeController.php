<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePracticeRequest;
use App\Models\Practice;
use App\Models\PracticeCompany;

class PracticeController extends Controller
{
    public function store(StorePracticeRequest $request){
        $data = $request->validated();
        $practice = new Practice($data);
        $user = $request->user();
        $practice['student_id'] = $user->id;
        $practice->save();

        if (!$request->company_id) {
            $practiceCompany = new PracticeCompany([
                'name' => $request->company_name,
                'address' => $request->company_address,
                'company_email' => $request->company_email,
                'contact_name' => $request->contact_name,
                'contact_email' => $request->contact_email,
                'contact_phone' => $request->contact_phone,
            ]);

            $practice->practiceCompany()->save($practiceCompany);
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => __('practice.practice_created_successfully'),
        ]);
    }
}
