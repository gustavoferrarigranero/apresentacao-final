import { afterEach, beforeEach, expect, it, vi } from 'vite-plus/test';
import { orderApi, orderEndpoint } from '@/domains/orders/services';
import {
    restaurantApi,
    restaurantEndpoints,
} from '@/domains/restaurants/services';
import { productApi } from '@/domains/products/services';
import { categoryApi } from '@/domains/categories/services';
import { userApi } from '@/domains/users/services';
import type { ApiUser } from '@/domains/users/types';
import type { Order } from '@/domains/orders/types';
import { listUrl } from '@/shared/api/pagination';
import { setToken } from '@/shared/api/client';

const customer: ApiUser = {
    id: 1,
    name: 'Cliente',
    email: 'client@example.test',
    role: 'customer',
    restaurant_id: null,
};
const admin: ApiUser = {
    ...customer,
    id: 2,
    role: 'restaurant_admin',
    restaurant_id: 4,
};
const pending: Order = {
    id: 9,
    restaurant_id: 4,
    status: 'pending',
    total: '5.00',
    address: 'Rua',
    delivery_date: '2026-12-01',
    items: [],
    created_at: '',
    updated_at: '',
};
let fetcher = vi.fn();
beforeEach(() => {
    vi.stubGlobal('window', {
        location: { origin: 'http://localhost' },
        dispatchEvent: vi.fn(),
    });
    vi.stubGlobal('document', { cookie: '' });
    vi.stubGlobal('sessionStorage', {
        getItem: () => null,
        setItem: vi.fn(),
        removeItem: vi.fn(),
    });
    setToken('test-token');
    fetcher = vi
        .fn()
        .mockImplementation(
            async () => new Response(JSON.stringify({ data: pending })),
        );
    vi.stubGlobal('fetch', fetcher);
});
afterEach(() => vi.unstubAllGlobals());

it('seleciona só os endpoints de pedidos autorizados pelo backend', () => {
    expect(orderEndpoint(customer)).toBe('/orders');
    expect(orderEndpoint(admin)).toBe('/restaurant/orders');
    expect(orderEndpoint({ ...admin, role: 'staff' })).toBe(
        '/restaurant/orders',
    );
    expect(orderEndpoint({ ...admin, restaurant_id: null })).toBeNull();
    expect(orderEndpoint({ ...admin, role: 'super_admin' })).toBeNull();
    expect(orderEndpoint(null)).toBeNull();
});
it('restringe a consulta administrativa ao restaurante associado', () => {
    expect(restaurantEndpoints.management(admin)).toBe('/restaurants?id=4');
    expect(
        restaurantEndpoints.management({ ...admin, role: 'super_admin' }),
    ).toBe('/restaurants');
    expect(restaurantEndpoints.management(customer)).toBeNull();
    expect(
        restaurantEndpoints.management({ ...admin, restaurant_id: null }),
    ).toBeNull();
});
it('preserva filtros de restaurante ao pesquisar e paginar', () => {
    const url = new URL(
        listUrl('/restaurants?id=4', 2, 12, 'Casa & Mesa'),
        'http://localhost',
    );
    expect(url.searchParams.get('id')).toBe('4');
    expect(url.searchParams.get('page')).toBe('2');
    expect(url.searchParams.get('per_page')).toBe('12');
    expect(url.searchParams.get('name')).toBe('Casa & Mesa');
});
it('criação de restaurante inclui administrador inicial, edição não reenvia credenciais', async () => {
    const values = {
        name: 'Restaurante',
        address: 'Rua',
        phone_number: '210000000',
        nif: '500000001',
        admin_name: 'Admin',
        admin_email: 'admin@example.test',
        admin_password: 'test-only-password',
        role: 'super_admin',
        restaurant_id: 50,
        status: 'active',
    };
    await restaurantApi.save(null, values);
    const create = fetcher.mock.calls[0];
    expect(create[0]).toBe('/api/v1/restaurants');
    expect(create[1].method).toBe('POST');
    const body = JSON.parse(create[1].body);
    expect(body.admin_email).toBe(values.admin_email);
    expect(body).not.toHaveProperty('role');
    expect(body).not.toHaveProperty('restaurant_id');
    await restaurantApi.save(4, values);
    const update = fetcher.mock.calls[1];
    expect(update[0]).toBe('/api/v1/restaurants/4');
    expect(update[1].method).toBe('PATCH');
    expect(JSON.parse(update[1].body)).not.toHaveProperty('admin_password');
});
it('produto envia preço, quantidade e categoria sem campos de outra integração', async () => {
    await productApi.save(3, {
        name: 'Prato',
        type: 'Comida',
        price: '9.50',
        quantity: 2,
        category_id: 1,
        status: 'active',
        image_url: 'inventada',
        restaurant_id: 8,
    });
    expect(fetcher.mock.calls[0][0]).toBe('/api/v1/products/3');
    expect(JSON.parse(fetcher.mock.calls[0][1].body)).toEqual({
        name: 'Prato',
        type: 'Comida',
        price: '9.50',
        quantity: 2,
        category_id: 1,
        status: 'active',
    });
});
it('login não envia papel escolhido no cliente', async () => {
    await userApi.authenticate('login', {
        email: customer.email,
        password: 'test-only-password',
        role: 'super_admin',
    });
    expect(JSON.parse(fetcher.mock.calls[0][1].body)).toEqual({
        email: customer.email,
        password: 'test-only-password',
    });
    expect(fetcher.mock.calls[0][1].headers).not.toHaveProperty(
        'Authorization',
    );
});
it('cancelamento de cliente usa POST e preparação usa PATCH com estado', async () => {
    await orderApi.transition(customer, pending, 'cancelled');
    expect(fetcher.mock.calls[0][0]).toBe('/api/v1/orders/9/cancel');
    expect(fetcher.mock.calls[0][1].method).toBe('POST');
    expect(fetcher.mock.calls[0][1].body).toBeUndefined();
    await orderApi.transition(admin, pending, 'in_progress');
    expect(fetcher.mock.calls[1][0]).toBe('/api/v1/restaurant/orders/9/status');
    expect(fetcher.mock.calls[1][1].method).toBe('PATCH');
    expect(JSON.parse(fetcher.mock.calls[1][1].body)).toEqual({
        status: 'in_progress',
    });
});
it('não envia transição terminal nem ação de outro restaurante', async () => {
    await expect(
        orderApi.transition(
            admin,
            { ...pending, status: 'completed' },
            'cancelled',
        ),
    ).rejects.toThrow();
    await expect(
        orderApi.transition(
            admin,
            { ...pending, restaurant_id: 5 },
            'in_progress',
        ),
    ).rejects.toThrow();
    await expect(
        orderApi.transition(customer, pending, 'in_progress'),
    ).rejects.toThrow();
    expect(fetcher).not.toHaveBeenCalled();
});
it('carrega opções de categorias respeitando todas as páginas', async () => {
    fetcher
        .mockImplementationOnce(
            async () =>
                new Response(
                    JSON.stringify({
                        data: [{ id: 1, name: 'Pratos' }],
                        meta: { last_page: 2 },
                    }),
                ),
        )
        .mockImplementationOnce(
            async () =>
                new Response(
                    JSON.stringify({
                        data: [{ id: 2, name: 'Bebidas' }],
                        meta: { last_page: 2 },
                    }),
                ),
        );
    const rows = await categoryApi.options(new AbortController().signal);
    expect(rows.map((row) => row.id)).toEqual([1, 2]);
    expect(fetcher.mock.calls[1][0]).toBe(
        '/api/v1/categories?per_page=100&page=2',
    );
});
