<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('platform-token')->plainTextToken;
        Audit::record('auth', 'login', 'user', $user->id, ['method' => 'password']);

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email']),
            'tenants' => $user->tenants()->get(['tenants.id', 'tenants.slug', 'tenants.name', 'tenants.environment']),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $token = $user->createToken('platform-token')->plainTextToken;
        Audit::record('auth', 'register', 'user', $user->id);

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email']),
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'user' => $user->only(['id', 'name', 'email']),
            'tenants' => $user->tenants()->get(['tenants.id', 'tenants.slug', 'tenants.name', 'tenants.environment']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        Audit::record('auth', 'logout', 'user', $request->user()->id);
        return response()->json(['ok' => true]);
    }
}
