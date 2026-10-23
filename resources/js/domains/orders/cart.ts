import type { Product } from '@/domains/products/types';
import type { CartItem } from './types';
export interface CartState {
    items: CartItem[];
    error: string;
}
export type CartAction =
    | { type: 'add'; product: Product }
    | { type: 'quantity'; id: number; quantity: number }
    | { type: 'remove'; id: number }
    | { type: 'clear' };
export function cartReducer(state: CartState, action: CartAction): CartState {
    if (action.type === 'clear') return { items: [], error: '' };
    if (action.type === 'remove')
        return {
            items: state.items.filter((item) => item.product.id !== action.id),
            error: '',
        };
    const product =
        action.type === 'add'
            ? action.product
            : state.items.find((item) => item.product.id === action.id)
                  ?.product;
    if (!product) return state;
    if (action.type === 'quantity' && action.quantity === 0)
        return {
            items: state.items.filter((item) => item.product.id !== action.id),
            error: '',
        };
    if (
        state.items.some(
            (item) => item.product.restaurant_id !== product.restaurant_id,
        )
    )
        return {
            ...state,
            error: 'Cada pedido pertence a um restaurante. Termine ou limpe o carrinho antes de escolher outro.',
        };
    const existing = state.items.find((item) => item.product.id === product.id);
    const quantity =
        action.type === 'add' ? (existing?.quantity ?? 0) + 1 : action.quantity;
    if (product.status !== 'active')
        return { ...state, error: 'Este produto está indisponível.' };
    if (
        !Number.isSafeInteger(quantity) ||
        quantity < 1 ||
        quantity > 100 ||
        quantity > product.quantity
    )
        return {
            ...state,
            error: `Stock insuficiente ou quantidade inválida para ${product.name}. Máximo por item: ${Math.min(100, product.quantity)}.`,
        };
    if (!existing && state.items.length >= 50)
        return {
            ...state,
            error: 'O pedido permite no máximo 50 produtos distintos.',
        };
    return {
        items: existing
            ? state.items.map((item) =>
                  item.product.id === product.id ? { product, quantity } : item,
              )
            : [...state.items, { product, quantity }],
        error: '',
    };
}
