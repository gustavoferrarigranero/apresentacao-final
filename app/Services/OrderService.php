<?php

namespace App\Services;

use App\Actions\Orders\ManageOrdersAction;
use App\Domains\Orders\Data\CreateOrderData;
use App\Domains\Orders\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Coordena as regras e operações do domínio de pedidos, oferecendo aos controladores uma interface para os casos de uso.
 */
final readonly class OrderService
{
    public function __construct(private ManageOrdersAction $manageOrdersAction) {}

    /** @return LengthAwarePaginator<int, Order> */
    public function customerOrders(int $customerId, int $perPage): LengthAwarePaginator
    {
        return $this->manageOrdersAction->forCustomer($customerId, $perPage);
    }

    /** @return LengthAwarePaginator<int, Order> */
    public function restaurantOrders(int $restaurantId, int $perPage): LengthAwarePaginator
    {
        return $this->manageOrdersAction->forRestaurant($restaurantId, $perPage);
    }

    public function customerOrder(int $customerId, int $orderId): Order
    {
        return $this->manageOrdersAction->findForCustomer($customerId, $orderId);
    }

    public function restaurantOrder(int $restaurantId, int $orderId): Order
    {
        return $this->manageOrdersAction->findForRestaurant($restaurantId, $orderId);
    }

    public function create(User $customer, CreateOrderData $data): Order
    {
        return $this->manageOrdersAction->create($customer, $data);
    }

    public function updateStatus(int $restaurantId, int $orderId, OrderStatus $status): Order
    {
        return $this->manageOrdersAction->updateStatus($restaurantId, $orderId, $status);
    }

    public function cancel(int $customerId, int $orderId): Order
    {
        return $this->manageOrdersAction->cancelByCustomer($customerId, $orderId);
    }
}
