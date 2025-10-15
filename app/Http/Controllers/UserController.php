<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use App\Mail\CompanyConfirmationMail;
use App\Mail\SendPasswordMail;
use App\Models\Company;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\Supervisor;
use App\Models\User;
use App\RoleEnum;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserController extends Controller
{

    public function register(RegisterRequest $request){
        $userData = $request->validated();
        $type = $userData['type'];
        $plainPassword = $type === RoleEnum::STUDENT->value
            ? Str::random(12)
            : $userData['password'];

        DB::transaction(function () use ($userData, $type, $plainPassword) {
            $email = match ($type) {
                RoleEnum::STUDENT->value => $userData['student_email'],
                RoleEnum::COMPANY->value => $userData['company_email'],
                default => null,
            };

            $name = match ($type) {
                RoleEnum::STUDENT->value => trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')),
                RoleEnum::COMPANY->value => $userData['name'] ?? null,
                default => null,
            };

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($plainPassword),
            ]);

            switch ($type) {
                case RoleEnum::STUDENT->value:
                    $student = new Student($userData);
                    $user->student()->save($student);

                    if (!empty($userData['study_program'])) {
                        $studyProgram = StudyProgram::where('id', $userData['study_program'])->first();
                        $student->studyPrograms()->sync([$studyProgram->id]);
                    }

                    Mail::to($student->student_email)->send(new SendPasswordMail($plainPassword, $user));
                    break;

                case RoleEnum::COMPANY->value:
                    $activationToken = Str::random(64);
                    $companyData = array_merge($userData, [
                        'status' => false,
                        'activation_token' => $activationToken,
                    ]);
                    $company = new Company($companyData);
                    $user->company()->save($company);

                    Mail::to($company->company_email)->send(new CompanyConfirmationMail($company));
                    break;
            }
            $user->roles()->attach($type);
        });

        return response()->json([
            'success' => true,
            'statusCode' => 201,
            'message' => __('auth.user_registered'),
        ], 201);
    }

    public function login(LoginRequest $request){
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => __('auth.invalid_credentials'),
            ], 401);
        }

        $user = Auth::user();

        if ($user->hasRole('company') && is_null($user->email_verified_at)) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => __('auth.email_not_verified'),
            ], 403);
        }

        $token = $user->createToken('LoginToken')->accessToken;
        $user = User::with(['roles', 'student', 'supervisor', 'company'])->find($user->id);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => __('auth.login_success'),
            'data' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(){
        if(Auth::check()){
            Auth::user()->token()->revoke();
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => __('auth.logout_success')
            ]);
        }else{
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => __('auth.unauthenticated'),
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
                'message' => __('auth.authenticated'),
                'data' => new UserResource($user),
            ]);
        }else{
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => __('auth.unauthenticated'),
            ], 401);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        if($user){
            if(!Hash::check($request->get('current_password'), $user->password)){
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => __('auth.password_mismatch'),
                ], 400);
            }
            $user->password = Hash::make($request->get('password'));
            $user->save();
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => __('auth.password_changed')
            ]);
        }else{
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => __('auth.unauthenticated'),
            ], 401);
        }
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => __('auth.password_updated'),
            ]);
        }

        if($status === Password::INVALID_USER){
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => __('auth.email_not_registered'),
            ], 404);
        }

        return response()->json([
            'success' => false,
            'statusCode' => 422,
            'message' => __('auth.password_update_failed'),
        ], 422);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => __('auth.reset_link_sent')
            ]);
        }

        if($status === Password::INVALID_USER){
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => __('auth.email_not_registered'),
            ], 404);
        }

        return response()->json([
            'success' => false,
            'statusCode' => 422,
            'message' => __('auth.reset_link_failed'),
        ], 422);
    }
}
