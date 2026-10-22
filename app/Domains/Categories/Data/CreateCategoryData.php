<?php

namespace App\Domains\Categories\Data;

use Spatie\LaravelData\Data;

/**
 * Define os dados usados pelo caso de uso `CreateCategoryData` no domínio de categorias; funciona como contrato interno entre validação e lógica de negócio.
 */
final class CreateCategoryData extends Data
{

    public function __construct(
        public readonly string $name,
        public readonly string $status = 'active',
    ) {
    }
}
