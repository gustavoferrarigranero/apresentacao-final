<?php

namespace App\Domains\Categories\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Representa erros específicos do domínio de categorias, distinguindo falhas de negócio de erros genéricos da aplicação.
 */
final class CategoryException extends RuntimeException
{
    public static function creationFailed(Throwable $previous): self
    {
        return new self('Não foi possível criar a categoria.', 0, $previous);
    }

    public static function updateFailed(Throwable $previous): self
    {
        return new self('Não foi possível atualizar a categoria.', 0, $previous);
    }

    public static function deletionFailed(Throwable $previous): self
    {
        return new self('Não foi possível desativar a categoria.', 0, $previous);
    }

    public static function retrievalFailed(Throwable $previous): self
    {
        return new self('Não foi possível consultar as categorias.', 0, $previous);
    }
}
