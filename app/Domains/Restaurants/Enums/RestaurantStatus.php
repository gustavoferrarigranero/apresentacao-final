<?php

namespace App\Domains\Restaurants\Enums;

/**
 * Centraliza os valores válidos de `RestaurantStatus` no domínio de restaurantes, para que API, interface e regras de negócio usem as mesmas opções.
 */
enum RestaurantStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
