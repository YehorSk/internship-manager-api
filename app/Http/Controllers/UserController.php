<?php

namespace App\Http\Controllers;

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

                    Mail::to($company->contact_email)->send(new SendPasswordMail($plainPassword, $user)); // Doesn't work for now!
                    break;
            }
            $user->roles()->attach($this->getRoleId($type));
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

        if ($user->hasRole('company') && !$user->company->status) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => __('auth.company_inactive'),
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

    public function reset_password($token,$email) {
        return redirect()->to(config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($email));
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
