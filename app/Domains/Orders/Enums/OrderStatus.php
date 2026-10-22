<?php

namespace App\Domains\Orders\Enums;

/**
 * Centraliza os valores válidos de `OrderStatus` no domínio de pedidos, para que API, interface e regras de negócio usem as mesmas opções.
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
