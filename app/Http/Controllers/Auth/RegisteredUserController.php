<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\InkJinArtist;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,artist,user'],
        ]);

        // Check if email exists in inkjin_artists table
        $artist = InkJinArtist::where('email', $request->email)->first();
        
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'on_boarding' => $request->role === 'artist' ? 'no' : 'yes',
            'on_app' => $artist ? 1 : 0,
            'app_id' => $artist ? $artist->id : null,
        ];

        $user = User::create($userData);

        // Login user temporarily so they can access verification page
        Auth::login($user);

        // The Registered event triggers Laravel's built-in SendEmailVerificationNotification listener,
        // which automatically calls $user->sendEmailVerificationNotification() since User implements MustVerifyEmail.
        // No need to call it manually — doing so would send duplicate emails.
        event(new Registered($user));

        // Set session flag to indicate email was sent during registration
        $request->session()->put('email_sent_on_registration', true);

        // Redirect to verification notice
        return redirect()->route('verification.notice');
    }
}
