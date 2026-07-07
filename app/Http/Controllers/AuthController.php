<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;

#[Group('Autenticação')]
class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    #[Endpoint('Fazer login', 'Realiza o login do usuário.')]
    public function login(LoginRequest $request)
    {
        return response()->json(
            $this->authService->login($request->validated())
        );
    }
}
