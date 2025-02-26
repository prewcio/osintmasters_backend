<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ])->withCookie(cookie(
            'token',
            $token,
            60 * 24, // 24 hours
            '/',
            env('SESSION_DOMAIN', '192.168.1.49'),
            env('APP_ENV') === 'production', // secure
            true, // httpOnly
            false,
            'lax'
        ));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'])
            ->withCookie(cookie()->forget('token'));
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function csrf()
    {
        return response()->json(['message' => 'CSRF cookie set'])->withHeaders([
            'X-CSRF-Cookie' => true
        ]);
    }
} 