<?php

namespace App\Domains\Users\Actions;

use App\Domains\Users\Data\CustomerRegistrationData;
use App\Domains\Users\Data\LoginCredentialsData;
use App\Domains\Users\Data\PasswordResetData;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Executa o caso de uso `ManageAuthenticationAction` no domínio de utilizadores, mantendo a operação de negócio fora da camada HTTP.
 */
final class ManageAuthenticationAction
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    /**
     * @return array{user: array{id: int, name: string, email: string, role: string, restaurant_id: int|null}, token: string}
     */
    public function registerCustomer(CustomerRegistrationData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $user = $this->userRepository->createCustomer($data);

            return [
                'user' => $this->userPayload($user),
                'token' => $user->createToken('customer-api')->plainTextToken,
            ];
        });
    }

    /**
     * @return array{user: array{id: int, name: string, email: string, role: string, restaurant_id: int|null}, token: string}
     */
    public function login(LoginCredentialsData $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials->email);

        if ($user === null || ! Hash::check($credentials->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas são inválidas.'],
            ]);
        }

        if ($user->status === 'inactive') {
            throw new AuthorizationException('A conta está inativa.');
        }

        return [
            'user' => $this->userPayload($user),
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => mb_strtolower($email)]);
    }

    public function resetPassword(PasswordResetData $data): void
    {
        $status = Password::reset(
            [
                'email' => mb_strtolower($data->email),
                'token' => $data->token,
                'password' => $data->password,
            ],
            function (User $user, string $password): void {
                $this->userRepository->updatePassword($user, Hash::make($password));
                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    /**
     * @return array{id: int, name: string, email: string, role: string, restaurant_id: int|null}
     */
    public function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'restaurant_id' => $user->restaurant_id,
        ];
    }
}
