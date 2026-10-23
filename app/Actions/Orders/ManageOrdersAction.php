<?php

namespace App\Actions\Orders;

use App\Domains\Orders\Data\CreateOrderData;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Exceptions\OrderException;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Executa o caso de uso `ManageOrdersAction` no domínio de pedidos, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class ManageOrdersAction
{
    public function __construct(private OrderRepository $orderRepository)
    {
    }

    public function forCustomer(int $customerId, int $perPage): LengthAwarePaginator
    {
        return $this->orderRepository->forCustomer($customerId, $perPage);
    }

    public function forRestaurant(int $restaurantId, int $perPage): LengthAwarePaginator
    {
        return $this->orderRepository->forRestaurant($restaurantId, $perPage);
    }

    public function findForCustomer(int $customerId, int $orderId): Order
    {
        try {
            return $this->orderRepository->findForCustomerOrFail($customerId, $orderId);
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw OrderException::updateFailed($exception);
        }
    }

    public function findForRestaurant(int $restaurantId, int $orderId): Order
    {
        return $this->orderRepository->findForRestaurantOrFail($restaurantId, $orderId);
    }

    public function create(User $customer, CreateOrderData $data): Order
    {
        try {
            return DB::transaction(fn (): Order => $this->orderRepository->create($customer, $data));
        } catch (OrderException|ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw OrderException::creationFailed($exception);
        }
    }

    public function updateStatus(int $restaurantId, int $orderId, OrderStatus $newStatus): Order
    {
        try {
            return DB::transaction(function () use ($restaurantId, $orderId, $newStatus): Order {
                $order = $this->orderRepository->findForRestaurantOrFail($restaurantId, $orderId);
                $current = OrderStatus::from($order->payment_status);

                $validTransitions = match ($current) {
                    OrderStatus::PENDING => [OrderStatus::IN_PROGRESS, OrderStatus::CANCELLED],
                    OrderStatus::IN_PROGRESS => [OrderStatus::COMPLETED, OrderStatus::CANCELLED],
                    OrderStatus::COMPLETED, OrderStatus::CANCELLED => [],
                };

                if (! in_array($newStatus, $validTransitions, true)) {
                    throw OrderException::invalidTransition();
                }

                return $this->orderRepository->updateStatus($order, $newStatus);
            });
        } catch (OrderException|ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw OrderException::updateFailed($exception);
        }
    }

    public function cancelByCustomer(int $customerId, int $orderId): Order
    {
        try {
            return DB::transaction(fn (): Order => $this->orderRepository->cancelByCustomer($customerId, $orderId));
        } catch (OrderException|ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw OrderException::cancellationFailed($exception);
        }
    }
}
