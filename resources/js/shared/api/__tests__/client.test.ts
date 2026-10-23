import { afterEach, beforeEach, expect, it, vi } from 'vite-plus/test';
import { api, ApiError, setToken } from '@/shared/api/client';
const removeStoredToken = vi.fn();
beforeEach(() => {
    vi.stubGlobal('window', {
        location: { origin: 'http://localhost' },
        dispatchEvent: vi.fn(),
    });
    vi.stubGlobal('document', { cookie: '' });
    vi.stubGlobal('sessionStorage', {
        getItem: () => null,
        setItem: vi.fn(),
        removeItem: removeStoredToken,
    });
    setToken(null);
});
afterEach(() => vi.unstubAllGlobals());
it('usa /api/v1, token Sanctum e corpo JSON real', async () => {
    const fetcher = vi
        .fn()
        .mockResolvedValue(
            new Response(JSON.stringify({ data: { id: 7 } }), { status: 201 }),
        );
    vi.stubGlobal('fetch', fetcher);
    setToken('test-token');
    await api('/orders', {
        method: 'POST',
        data: {
            restaurant_id: 2,
            address: 'Rua',
            items: [{ product_id: 1, quantity: 1 }],
        },
    });
    expect(fetcher.mock.calls[0][0]).toBe('/api/v1/orders');
    expect(fetcher.mock.calls[0][1].headers.Authorization).toBe(
        'Bearer test-token',
    );
    expect(JSON.parse(fetcher.mock.calls[0][1].body)).toHaveProperty(
        'address',
        'Rua',
    );
});
it('mantém erros 422 por campo e referência X-Request-Id', async () => {
    vi.stubGlobal(
        'fetch',
        vi.fn().mockResolvedValue(
            new Response(
                JSON.stringify({
                    message: 'Dados inválidos',
                    errors: { 'items.0.quantity': ['Stock insuficiente'] },
                }),
                { status: 422, headers: { 'X-Request-Id': 'request-1' } },
            ),
        ),
    );
    const failure: unknown = await api('/orders', { method: 'POST' }).catch(
        (error) => error,
    );
    expect(failure).toBeInstanceOf(ApiError);
    if (!(failure instanceof ApiError)) throw failure;
    expect(failure.fields['items.0.quantity']).toBe('Stock insuficiente');
    expect(failure.requestId).toBe('request-1');
});
it('não envia bearer em login público e bloqueia origem externa', async () => {
    const fetcher = vi.fn().mockImplementation(async () => new Response('{}'));
    vi.stubGlobal('fetch', fetcher);
    setToken('secret');
    await api('/auth/login', {
        method: 'POST',
        public: true,
        data: { email: 'test@example.test' },
    });
    expect(fetcher.mock.calls[0][1].headers).not.toHaveProperty(
        'Authorization',
    );
    await expect(api('/api/v1/orders?x=1')).resolves.toEqual({});
    await expect(api('https://external.test/api/v1/orders')).rejects.toThrow(
        'origem',
    );
    expect(fetcher).toHaveBeenCalledTimes(2);
});
it('expiração 401 invalida a sessão API', async () => {
    vi.stubGlobal(
        'fetch',
        vi
            .fn()
            .mockResolvedValue(
                new Response('{"message":"Unauthenticated."}', { status: 401 }),
            ),
    );
    setToken('expired');
    await expect(api('/auth/me')).rejects.toBeInstanceOf(ApiError);
    expect(removeStoredToken).toHaveBeenCalled();
    expect(window.dispatchEvent).toHaveBeenCalled();
});
