<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Mail\CompanyConfirmationMail;
use App\Mail\SendPasswordMail;
use App\Models\Company;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{

    private function getRoleId($type)
    {
        return match($type) {
            'student' => 1,
            'supervisor' => 2,
            'company' => 3,
            default => 0
        };
    }

    public function register(RegisterRequest $request){
        $userData = $request->validated();
        $type = $userData['type'];
        $plainPassword = Str::random(12);

        DB::transaction(function () use ($userData, $type, $plainPassword) {

            $email = match ($type) {
                'student' => $userData['student_email'],
                'company' => $userData['contact_email'],
                default => null,
            };

            $name = match ($type) {
                'student' => $userData['first_name'],
                'company' => $userData['name'],
                default => null,
            };

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($plainPassword),
            ]);

            switch ($type) {
                case 'student':
                    $student = new Student($userData);
                    $user->student()->save($student);

                    if (!empty($userData['study_program'])) {
                        $studyProgram = StudyProgram::where('name', $userData['study_program'])->first();
                        $student->studyPrograms()->sync([$studyProgram->id]);
                    }

                    Mail::to($student->student_email)->send(new SendPasswordMail($plainPassword, $user));
                    break;

                case 'company':
                    $company = new Company($userData);
                    $user->company()->save($company);

                    Mail::to($company->contact_email)->send(new CompanyConfirmationMail()); // Doesn't work for now!
                    break;
            }
            $user->roles()->attach($this->getRoleId($type));
        });

        return response()->json([
            'success' => true,
            'statusCode' => 201,
            'message' => 'User has been registered successfully. Please check your email to verify your account.',
        ], 201);
    }

    public function login(LoginRequest $request){
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = Auth::user();

        if ($user->hasRole('company') && !$user->company->status) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => 'Company account is inactive.',
            ], 403);
        }

        $user['token'] = $user->createToken('LoginToken')->accessToken;
        $user = User::with(['roles', 'student', 'supervisor', 'company'])->find($user->id);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'User logged in successfully.',
            'data' => new UserResource($user),
        ]);
    }

    public function logout(){
        if(Auth::check()){
            Auth::user()->token()->revoke();
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'User logged out successfully.'
            ]);
        }else{
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => 'Unauthenticated.',
            ], 401);
        }
    }

    public function user(Request $request){
        $user = $request->user();
        if($user){
            $user = User::with(['roles', 'student', 'supervisor', 'company'])->find($user->id);
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Authenticated.',
                'data' => new UserResource($user),
            ]);
        }else{
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => 'Unauthenticated.',
            ], 401);
        }
    }
}
