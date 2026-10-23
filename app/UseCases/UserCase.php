<?php

namespace App\UseCases;

use App\Domains\Users\Enums\UserRole;
use App\Models\User;

class UserCase
{
    public static function isAdmin(User $user): bool
    {
        return $user->role === UserRole::RESTAURANT_ADMIN->value;
    }

    public static function isSuperAdmin(?User $user): bool
    {
        return $user?->role === UserRole::SUPER_ADMIN->value;
    }

    public static function isCustomer(?User $user): bool
    {
        return $user?->role === UserRole::CUSTOMER->value;
    }

    public static function isStaf(User $user): bool
    {
        return $user->role === UserRole::STAFF->value;
    }
}
