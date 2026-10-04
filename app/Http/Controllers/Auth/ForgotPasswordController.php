<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        Password::broker('users')->sendResetLink([
            'email' => Str::lower($request->input('email')),
        ]);

        // Always reply with the same generic message regardless of whether the
        // account exists, so this endpoint cannot be used to enumerate users.
        // Laravel's broker still throttles reset attempts for existing accounts.
        return back()->with('status', __('passwords.sent'));
    }
}
