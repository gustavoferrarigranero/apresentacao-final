import { api } from '@/shared/api/client';
import { payloadFields, type FormValues } from '@/shared/api/payload';
import type { Resource } from '@/shared/api/types';
import type { Product } from './types';
export const productEndpoints = {
    catalog: (restaurantId: number) => `/restaurants/${restaurantId}/products`,
    detail: (id: number) => `/products/${id}`,
};
export const productApi = {
    save: (id: number | null, values: FormValues) =>
        api<Resource<Product>>(
            id === null ? '/products' : productEndpoints.detail(id),
            {
                method: id === null ? 'POST' : 'PATCH',
                data: payloadFields(values, [
                    'name',
                    'type',
                    'price',
                    'quantity',
                    'category_id',
                    'status',
                ]),
            },
        ),
    deactivate: (id: number) =>
        api<Resource<Product>>(productEndpoints.detail(id), {
            method: 'DELETE',
        }),
};
