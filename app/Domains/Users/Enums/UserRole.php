<?php

namespace App\Domains\Users\Enums;

/**
 * Centraliza os valores válidos de `UserRole` no domínio de utilizadores, para que API, interface e regras de negócio usem as mesmas opções.
 */
enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case RESTAURANT_ADMIN = 'restaurant_admin';
    case STAFF = 'staff';
    case CUSTOMER = 'customer';
}
