<?php

namespace App\Http\Controllers\Api;

use App\Domains\Users\Actions\ManageAuthenticationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\ForgotPasswordRequest;
use App\Http\Requests\Api\Users\LoginRequest;
use App\Http\Requests\Api\Users\RegisterCustomerRequest;
use App\Http\Requests\Api\Users\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recebe pedidos HTTP em `AuthController`, delega o processamento à camada de domínio e devolve respostas para a interface ou para o cliente da API.
 */
class AuthController extends Controller
{
    public function __construct(private readonly ManageAuthenticationAction $authenticationAction)
    {
    }

    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Conta criada com sucesso.',
            ...$this->authenticationAction->registerCustomer($request->registrationData()),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return response()->json($this->authenticationAction->login($request->credentials()));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authenticationAction->logout($request->user());

        return response()->json(['message' => 'Sessão terminada.']);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authenticationAction->sendPasswordResetLink($request->validated('email'));

        return response()->json([
            'message' => 'Se existir uma conta para esse endereço, receberá instruções para repor a palavra-passe.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authenticationAction->resetPassword($request->passwordReset());

        return response()->json(['message' => 'Palavra-passe atualizada.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->authenticationAction->userPayload($request->user()),
        ]);
    }
}
