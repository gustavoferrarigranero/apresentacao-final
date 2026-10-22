<?php

namespace App\Domains\Categories\Data;

use Spatie\LaravelData\Data;

/**
 * Define os dados usados pelo caso de uso `CategoryFiltersData` no domínio de categorias; funciona como contrato interno entre validação e lógica de negócio.
 */
final class CategoryFiltersData extends Data
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $name = null,
        public readonly int $perPage = 10,
    ) {
    }

    /**
     * @param array{id?: int, name?: string, per_page?: int, perPage?: int} $filters
     */
    public static function fromValidated(array $filters): self
    {
        return new self(
            id: $filters['id'] ?? null,
            name: $filters['name'] ?? null,
            perPage: $filters['per_page'] ?? $filters['perPage'] ?? 10,
        );
    }
}
