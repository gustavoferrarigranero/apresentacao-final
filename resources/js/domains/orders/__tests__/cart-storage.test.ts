import { afterEach, beforeEach, expect, it, vi } from 'vite-plus/test';
import {
    decodeCart,
    emptyCart,
    persistentCartReducer,
    restoreCart,
    saveCart,
} from '../cart-storage';
import type { Product } from '@/domains/products/types';
const product: Product = {
    id: 1,
    restaurant_id: 2,
    category_id: 3,
    name: 'Prato',
    type: 'Prato',
    price: '1500.00',
    quantity: 5,
    status: 'active',
    created_at: '',
    updated_at: '',
};
beforeEach(() => {
    const values = new Map<string, string>();
    vi.stubGlobal('sessionStorage', {
        getItem: (key: string) => values.get(key) ?? null,
        setItem: (key: string, value: string) => values.set(key, value),
        removeItem: (key: string) => values.delete(key),
    });
});
afterEach(() => vi.unstubAllGlobals());
it('restaura pratos e quantidades depois de uma nova inicialização', () => {
    let state = persistentCartReducer(emptyCart(), { type: 'add', product });
    state = persistentCartReducer(state, {
        type: 'quantity',
        id: 1,
        quantity: 3,
    });
    saveCart(state);
    expect(restoreCart().items[0].quantity).toBe(3);
    expect(restoreCart().items[0].product.name).toBe('Prato');
});
it('mantém o carrinho após restaurar a mesma conta autenticada', () => {
    const state = persistentCartReducer(
        { items: [{ product, quantity: 2 }], ownerId: 7, error: '' },
        { type: 'owner', ownerId: 7 },
    );
    saveCart(state);
    expect(
        persistentCartReducer(restoreCart(), { type: 'owner', ownerId: 7 })
            .items,
    ).toHaveLength(1);
});
it('adota o carrinho do visitante no login e limpa ao sair ou trocar de conta', () => {
    const visitor = { ...emptyCart(), items: [{ product, quantity: 2 }] };
    const logged = persistentCartReducer(visitor, {
        type: 'owner',
        ownerId: 7,
    });
    expect(logged.items[0].quantity).toBe(2);
    expect(
        persistentCartReducer(logged, { type: 'owner', ownerId: null }).items,
    ).toEqual([]);
    expect(
        persistentCartReducer(logged, { type: 'owner', ownerId: 8 }).items,
    ).toEqual([]);
});
it('limpar o carrinho também remove a cópia persistida', () => {
    const state = persistentCartReducer(emptyCart(), { type: 'add', product });
    saveCart(state);
    saveCart(persistentCartReducer(state, { type: 'clear' }));
    expect(restoreCart().items).toEqual([]);
});
it('ignora conteúdo corrompido ou versões desconhecidas', () => {
    for (const raw of [
        'bad json',
        'null',
        '{}',
        JSON.stringify({ version: 2, ownerId: null, items: [] }),
    ])
        expect(decodeCart(raw)).toEqual(emptyCart());
});
it('não restaura itens duplicados, de outro restaurante ou com quantidade inválida', () => {
    const encode = (items: unknown[]) =>
        JSON.stringify({ version: 1, ownerId: null, items });
    expect(decodeCart(encode([{ product, quantity: 6 }])).items).toEqual([]);
    expect(
        decodeCart(
            encode([
                { product, quantity: 1 },
                { product, quantity: 1 },
            ]),
        ).items,
    ).toEqual([]);
    expect(
        decodeCart(
            encode([
                { product, quantity: 1 },
                {
                    product: { ...product, id: 2, restaurant_id: 9 },
                    quantity: 1,
                },
            ]),
        ).items,
    ).toEqual([]);
});
it('continua utilizável se o navegador bloquear o armazenamento', () => {
    vi.stubGlobal('sessionStorage', {
        getItem: () => {
            throw new Error('blocked');
        },
        setItem: () => {
            throw new Error('blocked');
        },
        removeItem: () => {
            throw new Error('blocked');
        },
    });
    expect(restoreCart()).toEqual(emptyCart());
    expect(() =>
        saveCart({ ...emptyCart(), items: [{ product, quantity: 1 }] }),
    ).not.toThrow();
});
