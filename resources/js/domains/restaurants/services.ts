import { api } from '@/shared/api/client';
import { payloadFields, type FormValues } from '@/shared/api/payload';
import type { Resource } from '@/shared/api/types';
import type { ApiUser } from '@/domains/users/types';
import type { Restaurant } from './types';
export const restaurantEndpoints = {
    list: '/restaurants',
    detail: (id: number) => `/restaurants/${id}`,
    management: (user: ApiUser | null) =>
        user?.role === 'super_admin'
            ? '/restaurants'
            : user?.role === 'restaurant_admin' && user.restaurant_id !== null
              ? `/restaurants?id=${user.restaurant_id}`
              : null,
};
export const restaurantApi = {
    save: (id: number | null, values: FormValues) =>
        api<Resource<Restaurant>>(
            id === null
                ? restaurantEndpoints.list
                : restaurantEndpoints.detail(id),
            {
                method: id === null ? 'POST' : 'PATCH',
                data: payloadFields(
                    values,
                    id === null
                        ? [
                              'name',
                              'address',
                              'phone_number',
                              'nif',
                              'admin_name',
                              'admin_email',
                              'admin_password',
                          ]
                        : ['name', 'address', 'phone_number', 'nif', 'status'],
                ),
            },
        ),
    deactivate: (id: number) =>
        api<Resource<Restaurant>>(restaurantEndpoints.detail(id), {
            method: 'DELETE',
        }),
};
