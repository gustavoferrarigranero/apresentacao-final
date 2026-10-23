import type { ApiUser } from '@/domains/users/types';
import { isTeam } from '@/domains/users/permissions';
import type { CartItem, CheckoutPayload, OrderStatus } from './types';
export const statusLabels: Record<OrderStatus, string> = {
    pending: 'Pendente',
    in_progress: 'Em preparação',
    completed: 'Concluído',
    cancelled: 'Cancelado',
};
export function transitions(
    status: OrderStatus,
    user: ApiUser | null,
): OrderStatus[] {
    if (isTeam(user))
        return status === 'pending'
            ? ['in_progress', 'cancelled']
            : status === 'in_progress'
              ? ['completed', 'cancelled']
              : [];
    return user?.role === 'customer' && status === 'pending'
        ? ['cancelled']
        : [];
}
export function checkoutPayload(
    items: CartItem[],
    address: string,
    deliveryDate: string,
): CheckoutPayload {
    if (!items.length || items.length > 50)
        throw new Error('Adicione entre 1 e 50 produtos ao carrinho.');
    const restaurantId = items[0].product.restaurant_id;
    const ids = new Set<number>();
    for (const item of items) {
        if (
            item.product.restaurant_id !== restaurantId ||
            ids.has(item.product.id)
        )
            throw new Error(
                'O pedido deve conter produtos distintos de um único restaurante.',
            );
        if (
            item.product.status !== 'active' ||
            !Number.isInteger(item.quantity) ||
            item.quantity < 1 ||
            item.quantity > 100 ||
            item.quantity > item.product.quantity
        )
            throw new Error(
                'Reveja a disponibilidade e as quantidades do carrinho.',
            );
        ids.add(item.product.id);
    }
    return {
        restaurant_id: restaurantId,
        address: address.trim(),
        ...(deliveryDate ? { delivery_date: deliveryDate } : {}),
        items: items.map((item) => ({
            product_id: item.product.id,
            quantity: item.quantity,
        })),
    };
}

export function orderChannel(user: ApiUser): string | null {
    if (user.role === 'customer') return `orders.${user.id}`;
    if (isTeam(user)) return `restaurant.${user.restaurant_id}`;
    return null;
}
export function isStatusEvent(
    value: unknown,
    user: ApiUser,
): value is { order_id: number; restaurant_id: number; status: OrderStatus } {
    if (!value || typeof value !== 'object') return false;
    const event = value as Record<string, unknown>;
    return (
        Number.isSafeInteger(event.order_id) &&
        Number.isSafeInteger(event.restaurant_id) &&
        typeof event.status === 'string' &&
        Object.hasOwn(statusLabels, event.status) &&
        (user.role === 'customer' ||
            (isTeam(user) && event.restaurant_id === user.restaurant_id))
    );
}
