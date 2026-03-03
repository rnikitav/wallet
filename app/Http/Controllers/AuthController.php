<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user  = User::query()->create([
            'name' => $data['name'], 
            'email' => $data['email'], 
            'password' => Hash::make($data['password'])
        ]);
        $token = $user->createToken('api')->plainTextToken;

        return response()->json(['token' => $token], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json(['token' => $token]);
    }
}