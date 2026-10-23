<?php

namespace App\Repositories;

use App\Domains\Users\Data\CreateStaffData;
use App\Domains\Users\Data\CustomerRegistrationData;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Interfaces\UserInterface;
use App\Models\User;

/**
 * Centraliza a leitura e a persistência dos dados do domínio de utilizadores, evitando consultas à base de dados espalhadas pelos casos de uso.
 */
final class UserRepository implements UserInterface
{
    public function createCustomer(CustomerRegistrationData $data): User
    {
        return User::query()->create([
            'name' => $data->name,
            'email' => mb_strtolower($data->email),
            'password' => $data->password,
            'role' => UserRole::CUSTOMER->value,
            'status' => 'active',
        ]);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', mb_strtolower($email))->first();
    }

    public function createRestaurantStaff(int $restaurantId, CreateStaffData $data): User
    {
        return User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
            'role' => UserRole::STAFF->value,
            'restaurant_id' => $restaurantId,
            'status' => 'active',
        ]);
    }

    /**
     * @param  array{name?: string, email?: string}  $attributes
     */
    public function updateProfile(User $user, array $attributes): User
    {
        $user->fill($attributes);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->saveOrFail();

        return $user;
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->update(['password' => $password]);
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
