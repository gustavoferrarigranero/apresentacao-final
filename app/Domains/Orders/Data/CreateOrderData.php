<?php

namespace App\Domains\Orders\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Define os dados usados pelo caso de uso `CreateOrderData` no domínio de pedidos; funciona como contrato interno entre validação e lógica de negócio.
 */
#[MapName(SnakeCaseMapper::class)]
final class CreateOrderData extends Data
{
    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function __construct(
        public readonly int $restaurantId,
        public readonly string $address,
        public readonly array $items,
        public readonly ?string $deliveryDate = null,
    ) {}
}
