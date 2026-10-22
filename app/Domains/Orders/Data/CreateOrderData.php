<?php

namespace App\Domains\Orders\Data;

use Spatie\LaravelData\Data;

/**
 * Define os dados usados pelo caso de uso `CreateOrderData` no domínio de pedidos; funciona como contrato interno entre validação e lógica de negócio.
 */
final class CreateOrderData extends Data
{
    /**
     * @param array<int, array{product_id: int, quantity: int}> $items
     */
    public function __construct(
        public readonly int $restaurantId,
        public readonly string $address,
        public readonly array $items,
        public readonly ?string $deliveryDate = null,
    ) {
    }

    /**
     * @param array{restaurant_id: int, address: string, items: array<int, array{product_id: int, quantity: int}>, delivery_date?: string} $attributes
     */
    public static function fromValidated(array $attributes): self
    {
        return new self(
            restaurantId: (int) $attributes['restaurant_id'],
            address: $attributes['address'],
            items: array_map(
                static fn (array $item): array => [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                ],
                $attributes['items'],
            ),
            deliveryDate: $attributes['delivery_date'] ?? null,
        );
    }
}
