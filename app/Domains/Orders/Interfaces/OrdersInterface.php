<?php

namespace App\Domains\Orders\Interfaces;

use App\Domains\Orders\Data\CreateOrderData;
use App\Domains\Orders\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrdersInterface
{
    /** @return LengthAwarePaginator<int, Order> */
    public function forCustomer(int $customerId, int $perPage): LengthAwarePaginator;

    /** @return LengthAwarePaginator<int, Order> */
    public function forRestaurant(int $restaurantId, int $perPage): LengthAwarePaginator;

    public function findForCustomerOrFail(int $customerId, int $orderId): Order;

    public function findForRestaurantOrFail(int $restaurantId, int $orderId): Order;

    public function create(User $customer, CreateOrderData $data): Order;

    public function updateStatus(Order $order, OrderStatus $status): Order;

    public function transitionForRestaurant(int $restaurantId, int $orderId, OrderStatus $status): Order;

    public function cancelByCustomer(int $customerId, int $orderId): Order;
}
