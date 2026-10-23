<?php

namespace Tests\Feature;

use App\Domains\Users\Enums\UserRole;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_uses_configuration_and_can_be_run_again_without_duplicate_accounts(): void
    {
        config(['auth.super_admin.email' => 'admin@example.test', 'auth.super_admin.password' => 'secure-password-123']);
        $this->seed(SuperAdminSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $user = User::query()->sole();
        $this->assertSame('admin@example.test', $user->email);
        $this->assertSame(UserRole::SUPER_ADMIN->value, $user->role);
        $this->assertNull($user->restaurant_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('secure-password-123', $user->password));
    }

    #[DataProvider('invalidCredentials')]
    public function test_invalid_bootstrap_credentials_do_not_create_an_admin(?string $email, ?string $password): void
    {
        config(['auth.super_admin.email' => $email, 'auth.super_admin.password' => $password]);

        try {
            $this->seed(SuperAdminSeeder::class);
            $this->fail('O seeder deve rejeitar credenciais ausentes ou uma palavra-passe curta.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('mínimo 16 caracteres', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public static function invalidCredentials(): array
    {
        return [
            'missing email' => [null, 'secure-password-123'],
            'empty email' => ['', 'secure-password-123'],
            'missing password' => ['admin@example.test', null],
            'short password' => ['admin@example.test', '123456789012345'],
        ];
    }
}
