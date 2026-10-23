<?php

namespace Tests\Unit;

use App\Domains\Users\Enums\UserRole;
use App\Models\User;
use App\UseCases\UserCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    #[DataProvider('roles')]
    public function test_role_checks_do_not_grant_other_roles(UserRole $role): void
    {
        $user = new User(['role' => $role->value]);
        $this->assertSame($role === UserRole::RESTAURANT_ADMIN, UserCase::isAdmin($user));
        $this->assertSame($role === UserRole::SUPER_ADMIN, UserCase::isSuperAdmin($user));
        $this->assertSame($role === UserRole::CUSTOMER, UserCase::isCustomer($user));
        $this->assertSame($role === UserRole::STAFF, UserCase::isStaf($user));
    }

    public function test_a_guest_does_not_have_a_privileged_role(): void
    {
        $this->assertFalse(UserCase::isSuperAdmin(null));
        $this->assertFalse(UserCase::isCustomer(null));
    }

    public static function roles(): array
    {
        return array_map(fn (UserRole $role) => [$role], UserRole::cases());
    }
}
