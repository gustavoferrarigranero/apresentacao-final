<?php

namespace App\Domains\Categories\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Define os dados usados pelo caso de uso `CategoryFiltersData` no domínio de categorias; funciona como contrato interno entre validação e lógica de negócio.
 */
#[MapName(SnakeCaseMapper::class)]
final class CategoryFiltersData extends Data
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $name = null,
        public readonly int $perPage = 10,
    ) {}
}
