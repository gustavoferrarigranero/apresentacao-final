import { cartReducer, type CartAction, type CartState } from './cart';
import type { CartItem } from './types';
import { decimalToCents } from '@/shared/utils/money';

const storageKey = 'deliveryhub.cart.v1';
export interface StoredCart extends CartState {
    ownerId: number | null;
}
export const emptyCart = (): StoredCart => ({
    items: [],
    error: '',
    ownerId: null,
});
export function persistentCartReducer(
    state: StoredCart,
    action: CartAction | { type: 'owner'; ownerId: number | null },
): StoredCart {
    if (action.type === 'owner') {
        if (state.ownerId === action.ownerId) return state;
        return state.ownerId === null
            ? { ...state, ownerId: action.ownerId }
            : { items: [], error: '', ownerId: action.ownerId };
    }
    return { ...state, ...cartReducer(state, action) };
}
/** Treat browser storage as untrusted; never restore arbitrary objects or mixed restaurants. */
export function decodeCart(raw: string | null): StoredCart {
    if (!raw) return emptyCart();
    try {
        const value = JSON.parse(raw);
        if (
            value.version !== 1 ||
            !Array.isArray(value.items) ||
            value.items.length > 50 ||
            !(
                value.ownerId === null ||
                (Number.isSafeInteger(value.ownerId) && value.ownerId > 0)
            )
        )
            return emptyCart();
        let state: CartState = { items: [], error: '' };
        for (const item of value.items) {
            const product = item?.product;
            if (
                !product ||
                !Number.isSafeInteger(product.id) ||
                product.id <= 0 ||
                !Number.isSafeInteger(product.restaurant_id) ||
                product.restaurant_id <= 0 ||
                !Number.isSafeInteger(product.category_id) ||
                product.category_id <= 0 ||
                typeof product.name !== 'string' ||
                typeof product.type !== 'string' ||
                typeof product.price !== 'string' ||
                product.status !== 'active' ||
                !Number.isSafeInteger(product.quantity) ||
                product.quantity < 0 ||
                !Number.isSafeInteger(item.quantity) ||
                item.quantity < 1 ||
                state.items.some((row) => row.product.id === product.id)
            )
                return emptyCart();
            decimalToCents(product.price);
            const restored: CartItem['product'] = {
                id: product.id,
                restaurant_id: product.restaurant_id,
                category_id: product.category_id,
                name: product.name,
                type: product.type,
                price: product.price,
                quantity: product.quantity,
                status: 'active',
                created_at: '',
                updated_at: '',
            };
            state = cartReducer(state, { type: 'add', product: restored });
            if (state.error) return emptyCart();
            state = cartReducer(state, {
                type: 'quantity',
                id: restored.id,
                quantity: item.quantity,
            });
            if (state.error) return emptyCart();
        }
        return { ...state, ownerId: value.ownerId };
    } catch {
        return emptyCart();
    }
}
export function restoreCart(): StoredCart {
    try {
        return decodeCart(sessionStorage.getItem(storageKey));
    } catch {
        return emptyCart();
    }
}
export function saveCart(state: StoredCart) {
    try {
        if (!state.items.length) sessionStorage.removeItem(storageKey);
        else
            sessionStorage.setItem(
                storageKey,
                JSON.stringify({
                    version: 1,
                    ownerId: state.ownerId,
                    items: state.items,
                }),
            );
    } catch {
        /* Keep the cart usable in memory when storage is blocked. */
    }
}
