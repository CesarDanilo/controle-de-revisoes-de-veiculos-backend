<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        if (!$user->status) {
            throw ValidationException::withMessages([
                'email' => ['Usuário inativo.'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'message' => 'Login realizado com sucesso.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
            ],
        ];
    }
}