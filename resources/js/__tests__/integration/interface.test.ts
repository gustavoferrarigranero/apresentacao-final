import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { expect, it } from 'vite-plus/test';
import { ApiForm, Fields } from '@/shared/components/api-ui';
import AddDishButton from '@/features/portal/components/add-dish-button';
import { cartReducer } from '@/domains/orders/cart';
import type { Product } from '@/domains/products/types';

const product: Product = {
    id: 4,
    restaurant_id: 1,
    category_id: 1,
    name: 'Prato do dia',
    type: 'Prato',
    price: '1500.00',
    quantity: 3,
    status: 'active',
    created_at: '',
    updated_at: '',
};
it('formulários não bloqueiam validação que pertence ao servidor', () => {
    const html = renderToStaticMarkup(
        createElement(ApiForm, {
            fields: [
                {
                    name: 'email',
                    label: 'Email',
                    type: 'email',
                    required: true,
                },
            ],
            submit: async () => undefined,
            done: () => undefined,
        }),
    );
    expect(html).toMatch(/novalidate=""/i);
    expect(html).not.toMatch(
        /\s(?:required|minlength|maxlength|pattern|min|max|step)=/i,
    );
    expect(html).toContain('aria-required="true"');
});
it('continua a apresentar erros de campo enviados pelo backend', () => {
    const html = renderToStaticMarkup(
        createElement(Fields, {
            fields: [{ name: 'email', label: 'Email', type: 'email' }],
            errors: { email: 'O email é obrigatório.' },
        }),
    );
    expect(html).toContain('O email é obrigatório.');
    expect(html).toContain('aria-invalid="true"');
    expect(html).toContain('aria-describedby="error-email"');
});
it('o contador do prato usa a quantidade real do carrinho', () => {
    let cart = cartReducer({ items: [], error: '' }, { type: 'add', product });
    cart = cartReducer(cart, { type: 'add', product });
    const html = renderToStaticMarkup(
        createElement(AddDishButton, {
            product,
            quantity: cart.items[0].quantity,
            onAdd: () => undefined,
        }),
    );
    expect(html).toContain('2 no carrinho');
    expect(html).toContain('>2</span>');
    expect(html).not.toContain('disabled=""');
});
it('o botão respeita stock e apresenta zero para um prato ainda não adicionado', () => {
    const initial = renderToStaticMarkup(
        createElement(AddDishButton, {
            product,
            quantity: 0,
            onAdd: () => undefined,
        }),
    );
    expect(initial).toContain('0 no carrinho');
    const limited = renderToStaticMarkup(
        createElement(AddDishButton, {
            product,
            quantity: 3,
            onAdd: () => undefined,
        }),
    );
    expect(limited).toContain('disabled=""');
});
