<?php

namespace App\Domains\Orders\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Representa erros específicos do domínio de pedidos, distinguindo falhas de negócio de erros genéricos da aplicação.
 */
final class OrderException extends RuntimeException
{
    public static function creationFailed(Throwable $previous): self
    {
        return new self('Não foi possível criar o pedido.', 0, $previous);
    }

    public static function updateFailed(Throwable $previous): self
    {
        return new self('Não foi possível atualizar o estado do pedido.', 0, $previous);
    }

    public static function cancellationFailed(Throwable $previous): self
    {
        return new self('Não foi possível cancelar o pedido.', 0, $previous);
    }

    public static function invalidTransition(): self
    {
        return new self('Transição de estado do pedido inválida.', 422);
    }

    public static function insufficientStock(string $productName): self
    {
        return new self("Stock insuficiente para o produto {$productName}.", 422);
    }
}
