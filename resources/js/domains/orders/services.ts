import { api } from '@/shared/api/client';
import type { Resource } from '@/shared/api/types';
import { isTeam } from '@/domains/users/permissions';
import type { ApiUser } from '@/domains/users/types';
import { transitions } from './rules';
import type { CheckoutPayload, Order, OrderStatus } from './types';
export function orderEndpoint(user: ApiUser | null): string | null {
    return user?.role === 'customer'
        ? '/orders'
        : isTeam(user)
          ? '/restaurant/orders'
          : null;
}
export const orderApi = {
    create: (payload: CheckoutPayload) =>
        api<Resource<Order>>('/orders', { method: 'POST', data: payload }),
    transition: (user: ApiUser | null, order: Order, status: OrderStatus) => {
        if (
            !transitions(order.status, user).includes(status) ||
            (isTeam(user) && order.restaurant_id !== user?.restaurant_id)
        )
            return Promise.reject(
                new Error('Esta ação não está disponível para este pedido.'),
            );
        return user?.role === 'customer'
            ? api<Resource<Order>>(`/orders/${order.id}/cancel`, {
                  method: 'POST',
              })
            : api<Resource<Order>>(`/restaurant/orders/${order.id}/status`, {
                  method: 'PATCH',
                  data: { status },
              });
    },
};
