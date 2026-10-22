<?php

namespace App\Domains\Categories\Data;

use Spatie\LaravelData\Data;

/**
 * Define os dados usados pelo caso de uso `EditCategoryData` no domínio de categorias; funciona como contrato interno entre validação e lógica de negócio.
 */
final class EditCategoryData extends Data
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $status = null,
    ) {
    }

    /**
     * @return array{name?: string, status?: string}
     */
    public function attributes(): array
    {
        return array_filter([
            'name' => $this->name,
            'status' => $this->status,
        ], static fn (?string $value): bool => $value !== null);
    }
}
