<?php

namespace App\Domains\Products\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Representa erros específicos do domínio de produtos, distinguindo falhas de negócio de erros genéricos da aplicação.
 */
final class ProductException extends RuntimeException
{
    public static function creationFailed(Throwable $previous): self
    {
        return new self('Não foi possível criar o produto.', 0, $previous);
    }

    public static function updateFailed(Throwable $previous): self
    {
        return new self('Não foi possível atualizar o produto.', 0, $previous);
    }

    public static function deactivationFailed(Throwable $previous): self
    {
        return new self('Não foi possível desativar o produto.', 0, $previous);
    }

    public static function retrievalFailed(Throwable $previous): self
    {
        return new self('Não foi possível consultar os produtos.', 0, $previous);
    }
}
