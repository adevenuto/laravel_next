<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\DuplicateRegistrationNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        // Note: no `unique:users` rule here — we look up manually so the
        // response shape is identical whether or not the email exists.
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $existing = User::where('email', $validated['email'])->first();

        if (! $existing) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $user->notify(new WelcomeNotification);
        } else {
            // Spend a bcrypt round to keep timing uniform with the create path.
            Hash::make($validated['password']);
            $existing->notify(new DuplicateRegistrationNotification);
        }

        // Same response either way — no enumeration leak.
        return response()->json([
            'message' => 'Your account is ready. Please sign in.',
        ], 200);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Uniform-timing: always run a bcrypt operation, even if the user is missing,
        // so attackers can't distinguish "no such email" from "wrong password".
        $user = User::where('email', $request->input('email'))->first();
        if ($user) {
            $passwordValid = Hash::check($request->input('password'), $user->password);
        } else {
            // Discard result — we just need the bcrypt cost to balance the timing.
            Hash::make($request->input('password'));
            $passwordValid = false;
        }

        if (! $user || ! $passwordValid) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json(['user' => $user]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Capture the token (if any) BEFORE clearing auth state.
        $token = $request->user()?->currentAccessToken();

        // SPA path: end the session.
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Bearer-token path: revoke the token used for this request.
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
