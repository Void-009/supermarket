<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    public function show()
    {
        return view('pages.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        if (RateLimiter::tooManyAttempts('contact-form:' . $request->ip(), 3)) {
            return back()->withErrors(['form' => 'Too many messages. Please try again later.']);
        }
        RateLimiter::hit('contact-form:' . $request->ip(), 3600);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::create($validated);

        return back()->with('status', 'Your message has been sent. We will get back to you soon.');
    }
}
