<?php

namespace App\Repositories;

use App\Domains\Orders\Data\CreateOrderData;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Exceptions\OrderException;
use App\Domains\Orders\Interfaces\OrdersInterface;
use App\Models\Order;
use App\Models\Produt;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

/**
 * Centraliza a leitura e a persistência dos dados do domínio de pedidos, evitando consultas à base de dados espalhadas pelos casos de uso.
 */
final class OrderRepository implements OrdersInterface
{
    /** @return LengthAwarePaginator<int, Order> */
    public function forCustomer(int $customerId, int $perPage): LengthAwarePaginator
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->with(['restaurant', 'items.product'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return LengthAwarePaginator<int, Order> */
    public function forRestaurant(int $restaurantId, int $perPage): LengthAwarePaginator
    {
        return Order::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['customer', 'items.product'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForCustomerOrFail(int $customerId, int $orderId): Order
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->with(['restaurant', 'items.product'])
            ->findOrFail($orderId);
    }

    public function findForRestaurantOrFail(int $restaurantId, int $orderId): Order
    {
        return Order::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['customer', 'items.product'])
            ->findOrFail($orderId);
    }

    public function create(User $customer, CreateOrderData $data): Order
    {
        $restaurant = Restaurant::query()
            ->whereKey($data->restaurantId)
            ->where('status', 'active')
            ->firstOrFail();

        $order = new Order([
            'restaurant_id' => $restaurant->id,
            'customer_id' => $customer->id,
            'user_id' => $customer->id,
            'address' => $data->address,
            'delivery_date' => $data->deliveryDate ?? now()->toDateString(),
            'payment_status' => OrderStatus::PENDING->value,
            'payment_value' => 0,
        ]);

        if (! $order->save()) {
            throw new RuntimeException('A gravação do pedido foi interrompida.');
        }

        $total = 0.0;
        foreach ($data->items as $item) {
            $product = Produt::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('status', 'active')
                ->whereKey($item['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($product->quantity < $item['quantity']) {
                throw OrderException::insufficientStock($product->name);
            }

            $unitPrice = (float) $product->price;
            $product->quantity -= $item['quantity'];
            if (! $product->save()) {
                throw new RuntimeException('A atualização do stock foi interrompida.');
            }

            $orderItem = $order->items()->make([
                'product_id' => $product->id,
                'value' => $unitPrice,
                'quantity' => $item['quantity'],
            ]);
            if (! $orderItem->save()) {
                throw new RuntimeException('A gravação do item do pedido foi interrompida.');
            }

            $total += $unitPrice * $item['quantity'];
        }

        $order->payment_value = number_format($total, 2, '.', '');
        if (! $order->save()) {
            throw new RuntimeException('A atualização do pedido foi interrompida.');
        }

        return $order->refresh()->load(['restaurant', 'items.product']);
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        if ($status === OrderStatus::CANCELLED) {
            $order->loadMissing('items');
            foreach ($order->items as $item) {
                $product = Produt::query()
                    ->where('restaurant_id', $order->restaurant_id)
                    ->lockForUpdate()
                    ->find($item->product_id);
                if ($product !== null) {
                    $product->quantity += $item->quantity;
                    if (! $product->save()) {
                        throw new RuntimeException('A atualização do stock foi interrompida.');
                    }
                }
            }
        }

        $order->payment_status = $status->value;

        if (! $order->save()) {
            throw new RuntimeException('A atualização do pedido foi interrompida.');
        }

        return $order->refresh()->load(['customer', 'restaurant', 'items.product']);
    }

    public function transitionForRestaurant(int $restaurantId, int $orderId, OrderStatus $status): Order
    {
        $order = $this->findForRestaurantOrFail($restaurantId, $orderId);

        return $this->updateStatus($order, $status);
    }

    public function cancelByCustomer(int $customerId, int $orderId): Order
    {
        $order = Order::query()
            ->where('customer_id', $customerId)
            ->where('payment_status', OrderStatus::PENDING->value)
            ->with('items.product')
            ->lockForUpdate()
            ->findOrFail($orderId);

        foreach ($order->items as $item) {
            $product = Produt::query()->lockForUpdate()->find($item->product_id);
            if ($product !== null && $product->restaurant_id === $order->restaurant_id) {
                $product->quantity += $item->quantity;
                if (! $product->save()) {
                    throw new RuntimeException('A atualização do stock foi interrompida.');
                }
            }
        }

        $order->payment_status = OrderStatus::CANCELLED->value;
        if (! $order->save()) {
            throw new RuntimeException('A atualização do pedido foi interrompida.');
        }

        return $order->refresh()->load(['restaurant', 'items.product']);
    }
}
