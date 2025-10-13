<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\PasswordResetToken;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        print("function was called");
        $request->validate(['email' => 'required|email']);

        print("got email");
        $user = User::where('email', $request->email)->first();
        print("recuest to db");
        if (!$user) {
            return response()->json(['message' => 'If account exists, email sent'], 200);
        }
        print("found");
        $token = Str::random(64);
        PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );
        $link = config('app.frontend_url') . '/new-password?token=' . $token . '&email=' . urlencode($user->email);

        Mail::send('emails.password_reset', ['link' => $link, 'user' => $user], function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Obnova hesla – Internship Manager');
        });

        return response()->json(['message' => 'Reset link sent']);
    }
}
