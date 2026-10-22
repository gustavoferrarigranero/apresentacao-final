<?php

namespace App\Domains\Categories\Enums;

/**
 * Centraliza os valores válidos de `CategoryEnums` no domínio de categorias, para que API, interface e regras de negócio usem as mesmas opções.
 */
enum CategoryEnums: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
