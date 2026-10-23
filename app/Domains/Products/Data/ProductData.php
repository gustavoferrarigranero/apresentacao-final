<?php

namespace App\Domains\Products\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Define os dados usados pelo caso de uso `ProductData` no domínio de produtos; funciona como contrato interno entre validação e lógica de negócio.
 */
#[MapName(SnakeCaseMapper::class)]
final class ProductData extends Data
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?float $price = null,
        public readonly ?int $quantity = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $status = null,
    ) {}

    /**
     * @return array<string, mixed>
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
        ], static fn (mixed $value): bool => $value !== null);
    }
}
