<?php

namespace App\Http\Controllers;

use App\Domains\Orders\Data\CreateOrderData;
use App\Domains\Orders\Services\OrderService;
use App\Events\OrderStatusUpdated;
use App\Http\Requests\Api\Orders\IndexOrdersRequest;
use App\Http\Requests\Api\Orders\StoreOrderRequest;
use App\Http\Requests\Api\Orders\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Recebe pedidos HTTP em `OrderController`, delega o processamento à camada de domínio e devolve respostas para a interface ou para o cliente da API.
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function customerIndex(IndexOrdersRequest $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orderService->customerOrders(
            (int) $request->user()->id,
            (int) $request->validated('per_page', 10),
        ));
    }

    public function restaurantIndex(IndexOrdersRequest $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orderService->restaurantOrders(
            (int) $request->user()->restaurant_id,
            (int) $request->validated('per_page', 10),
        ));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->create($request->user(), CreateOrderData::fromValidated($request->validated()));

        return (new OrderResource($order))
            ->additional(['message' => 'Pedido criado com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    public function customerShow(Request $request, int $order): OrderResource
    {
        return new OrderResource($this->orderService->customerOrder((int) $request->user()->id, $order));
    }

    public function restaurantShow(Request $request, int $order): OrderResource
    {
        return new OrderResource($this->orderService->restaurantOrder(
            (int) $request->user()->restaurant_id,
            $order,
        ));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $order): OrderResource
    {
        $updatedOrder = $this->orderService->updateStatus(
            (int) $request->user()->restaurant_id,
            $order,
            $request->status(),
        );

        // A notificação só é enfileirada depois de o serviço confirmar a transição de estado.
        OrderStatusUpdated::dispatch(
            (int) $updatedOrder->id,
            (int) $updatedOrder->restaurant_id,
            (int) $updatedOrder->customer_id,
            (string) $updatedOrder->payment_status,
        );

        return (new OrderResource($updatedOrder))
            ->additional(['message' => 'Estado do pedido atualizado.']);
    }

    public function cancel(Request $request, int $order): OrderResource
    {
        return (new OrderResource($this->orderService->cancel((int) $request->user()->id, $order)))
            ->additional(['message' => 'Pedido cancelado.']);
    }
}
