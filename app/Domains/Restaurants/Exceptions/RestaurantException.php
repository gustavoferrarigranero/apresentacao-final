<?php

namespace App\Domains\Restaurants\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Representa erros específicos do domínio de restaurantes, distinguindo falhas de negócio de erros genéricos da aplicação.
 */
final class RestaurantException extends RuntimeException
{
    public static function creationFailed(Throwable $previous): self
    {
        return new self('Não foi possível criar o restaurante.', 0, $previous);
    }

    public static function updateFailed(Throwable $previous): self
    {
        return new self('Não foi possível atualizar o restaurante.', 0, $previous);
    }

    public static function deactivationFailed(Throwable $previous): self
    {
        return new self('Não foi possível desativar o restaurante.', 0, $previous);
    }

    public static function retrievalFailed(Throwable $previous): self
    {
        return new self('Não foi possível consultar o restaurante.', 0, $previous);
    }
}
