<?php

namespace App\Domains\Products\Data;

use Spatie\LaravelData\Data;

/**
 * Define os dados usados pelo caso de uso `ProductData` no domínio de produtos; funciona como contrato interno entre validação e lógica de negócio.
 */
final class ProductData extends Data
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?float $price = null,
        public readonly ?int $quantity = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $status = null,
    ) {
    }

    /**
     * @param array{name?: string, type?: string, price?: numeric-string|float|int, quantity?: numeric-string|int, category_id?: numeric-string|int, status?: string} $attributes
     */
    public static function fromValidated(array $attributes): self
    {
        return new self(
            name: $attributes['name'] ?? null,
            type: $attributes['type'] ?? null,
            price: isset($attributes['price']) ? (float) $attributes['price'] : null,
            quantity: isset($attributes['quantity']) ? (int) $attributes['quantity'] : null,
            categoryId: isset($attributes['category_id']) ? (int) $attributes['category_id'] : null,
            status: $attributes['status'] ?? null,
        );
    }

    /**
     * @return array<string, string|int|float>
     */
    public function attributes(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'category_id' => $this->categoryId,
            'status' => $this->status,
        ], static fn (string|int|float|null $value): bool => $value !== null);
    }
}
