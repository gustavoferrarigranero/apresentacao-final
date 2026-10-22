<?php

namespace App\Domains\Products\Enums;

/**
 * Centraliza os valores válidos de `ProductStatus` no domínio de produtos, para que API, interface e regras de negócio usem as mesmas opções.
 */
enum ProductStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
