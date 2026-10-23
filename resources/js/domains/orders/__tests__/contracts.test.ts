import { describe, expect, it } from 'vite-plus/test';
import { canManageRestaurant } from '@/domains/restaurants/permissions';
import { cartReducer } from '@/domains/orders/cart';
import {
    checkoutPayload,
    isStatusEvent,
    orderChannel,
    transitions,
} from '@/domains/orders/rules';
import { decimalToCents, money } from '@/shared/utils/money';
import type { ApiUser } from '@/domains/users/types';
import type { OrderStatus } from '@/domains/orders/types';
import type { Product } from '@/domains/products/types';
import type { Restaurant } from '@/domains/restaurants/types';
const customer: ApiUser = {
    id: 8,
    name: 'Cliente',
    email: 'customer@example.test',
    restaurant_id: null,
    role: 'customer',
};
const admin: ApiUser = {
    ...customer,
    id: 9,
    role: 'restaurant_admin',
    restaurant_id: 2,
};
const product: Product = {
    id: 1,
    restaurant_id: 2,
    name: 'Prato',
    category_id: 1,
    type: 'comida',
    price: '19.99',
    quantity: 3,
    status: 'active',
    created_at: '',
    updated_at: '',
};
describe('Contrato real de pedidos', () => {
    it('envia exatamente os campos de StoreOrderRequest, sem campos de pagamento ou endereço inventados', () => {
        expect(
            checkoutPayload(
                [{ product, quantity: 2 }],
                ' Morada de teste ',
                '2026-12-01',
            ),
        ).toEqual({
            restaurant_id: 2,
            address: 'Morada de teste',
            delivery_date: '2026-12-01',
            items: [{ product_id: 1, quantity: 2 }],
        });
        expect(
            checkoutPayload([{ product, quantity: 1 }], 'Morada', ''),
        ).not.toHaveProperty('delivery_date');
    });
    it('delega a morada ao backend e impede produtos duplicados ou restaurantes diferentes', () => {
        expect(
            checkoutPayload([{ product, quantity: 1 }], ' ', '').address,
        ).toBe('');
        expect(() =>
            checkoutPayload(
                [
                    { product, quantity: 1 },
                    { product, quantity: 1 },
                ],
                'Morada',
                '',
            ),
        ).toThrow();
        expect(() =>
            checkoutPayload(
                [
                    { product, quantity: 1 },
                    {
                        product: { ...product, id: 2, restaurant_id: 3 },
                        quantity: 1,
                    },
                ],
                'Morada',
                '',
            ),
        ).toThrow();
    });
    it('respeita stock e máximo de 100 unidades por item', () => {
        expect(() =>
            checkoutPayload([{ product, quantity: 4 }], 'Morada', ''),
        ).toThrow();
        expect(() =>
            checkoutPayload(
                [{ product: { ...product, quantity: 200 }, quantity: 101 }],
                'Morada',
                '',
            ),
        ).toThrow();
    });
    it('impede adicionar itens de outro restaurante sem apagar o carrinho', () => {
        const initial = cartReducer(
            { items: [], error: '' },
            { type: 'add', product },
        );
        const changed = cartReducer(initial, {
            type: 'add',
            product: { ...product, id: 2, restaurant_id: 3 },
        });
        expect(changed.items).toEqual(initial.items);
        expect(changed.error).toContain('restaurante');
    });
    it('impede produtos inativos e excesso de stock, permite remover', () => {
        expect(
            cartReducer(
                { items: [], error: '' },
                { type: 'add', product: { ...product, status: 'inactive' } },
            ).items,
        ).toEqual([]);
        const initial = { items: [{ product, quantity: 3 }], error: '' };
        expect(
            cartReducer(initial, { type: 'add', product }).items[0].quantity,
        ).toBe(3);
        expect(cartReducer(initial, { type: 'remove', id: 1 }).items).toEqual(
            [],
        );
    });
});
describe('Preços decimais reais, papéis e estados', () => {
    it('converte strings decimais sem tratar kwanzas como centavos', () => {
        expect(decimalToCents('19.99')).toBe(1999);
        expect(decimalToCents('3.50')).toBe(350);
        expect(decimalToCents(2)).toBe(200);
        expect(decimalToCents('0.01')).toBe(1);
    });
    it('apresenta os valores em AOA sem alterar a escala ou aplicar câmbio', () => {
        expect(money(1850).replace(/\s/g, ' ')).toBe('18,50 AOA');
        expect(money(0).replace(/\s/g, ' ')).toBe('0,00 AOA');
        expect(money(1999).replace(/\s/g, ' ')).toBe('19,99 AOA');
    });
    it('rejeita números inválidos ou precisão monetária inesperada', () => {
        expect(() => decimalToCents('NaN')).toThrow();
        expect(() => decimalToCents('1.999')).toThrow();
        expect(() => decimalToCents('-1.00')).toThrow();
    });
    it('aplica exatamente as transições de ManageOrdersAction', () => {
        expect(transitions('pending', admin)).toEqual([
            'in_progress',
            'cancelled',
        ]);
        expect(transitions('in_progress', { ...admin, role: 'staff' })).toEqual(
            ['completed', 'cancelled'],
        );
        for (const status of ['completed', 'cancelled'] as OrderStatus[])
            expect(transitions(status, admin)).toEqual([]);
    });
    it('cliente só cancela pendentes e super_admin não recebe ações de restaurante', () => {
        expect(transitions('pending', customer)).toEqual(['cancelled']);
        expect(transitions('in_progress', customer)).toEqual([]);
        expect(
            transitions('pending', { ...admin, role: 'super_admin' }),
        ).toEqual([]);
        expect(transitions('pending', null)).toEqual([]);
    });
    it('administrador só gere o seu restaurante; staff não altera restaurantes', () => {
        const restaurant = { id: 2 } as Restaurant;
        expect(canManageRestaurant(admin, restaurant)).toBe(true);
        expect(canManageRestaurant(admin, { id: 3 } as Restaurant)).toBe(false);
        expect(
            canManageRestaurant({ ...admin, role: 'staff' }, restaurant),
        ).toBe(false);
    });
});
describe('Broadcasting do backend recebido', () => {
    it('canal de cliente usa ID do utilizador e não o ID do pedido', () => {
        expect(orderChannel(customer)).toBe('orders.8');
        expect(orderChannel(admin)).toBe('restaurant.2');
        expect(orderChannel({ ...admin, role: 'super_admin' })).toBeNull();
    });
    it('aceita só estados válidos e o restaurante da equipa', () => {
        const event = { order_id: 7, restaurant_id: 2, status: 'in_progress' };
        expect(isStatusEvent(event, admin)).toBe(true);
        expect(isStatusEvent({ ...event, restaurant_id: 5 }, admin)).toBe(
            false,
        );
        expect(isStatusEvent({ ...event, status: 'delivering' }, admin)).toBe(
            false,
        );
        expect(isStatusEvent({ ...event, order_id: '7' }, customer)).toBe(
            false,
        );
    });
});
