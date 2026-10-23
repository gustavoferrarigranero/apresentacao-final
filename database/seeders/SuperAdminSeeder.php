<?php

namespace Database\Seeders;

use App\Domains\Users\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Popula a base de dados com os dados necessários através de `SuperAdminSeeder`.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('auth.super_admin.email');
        $password = config('auth.super_admin.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || strlen($password) < 16) {
            throw new RuntimeException(
                'Configure SUPER_ADMIN_EMAIL e SUPER_ADMIN_PASSWORD (mínimo 16 caracteres) antes de executar db:seed.'
            );
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($password),
                'role' => UserRole::SUPER_ADMIN->value,
                'restaurant_id' => null,
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
    }
}
