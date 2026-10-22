<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Publica a alteração de estado do pedido para o restaurante e o cliente associados.
 */
final class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $orderId,
        public readonly int $restaurantId,
        public readonly int $customerId,
        public readonly string $status,
    ) {}

    /**
     * Envia apenas aos canais privados autorizados dos participantes do pedido.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('restaurant.'.$this->restaurantId),
            new PrivateChannel('orders.'.$this->customerId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    /**
     * Expõe ao front-end só os identificadores e o estado necessários para atualizar a interface.
     *
     * @return array{order_id: int, restaurant_id: int, status: string}
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'restaurant_id' => $this->restaurantId,
            'status' => $this->status,
        ];
    }
}
