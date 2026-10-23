import { Minus, Plus, Trash2, ArrowRight } from 'lucide-react';
import type { Dispatch } from 'react';
import type { CartAction, CartState } from '@/domains/orders/cart';
import { decimalToCents, money } from '@/shared/utils/money';
import {
    Title,
    Empty,
    Feedback,
    buttonClass,
    secondaryClass,
} from '@/shared/components/api-ui';
export default function Cart({
    cart,
    dispatch,
}: {
    cart: CartState;
    dispatch: Dispatch<CartAction>;
}) {
    const total = cart.items.reduce(
        (sum, item) => sum + decimalToCents(item.product.price) * item.quantity,
        0,
    );
    return (
        <>
            <Title
                title="O seu carrinho"
                description="Reveja os produtos e as quantidades antes de enviar o pedido."
                action={
                    <a href="#portal" className={secondaryClass}>
                        Continuar a escolher
                    </a>
                }
            />
            <Feedback message={cart.error} />
            {!cart.items.length ? (
                <Empty text="O carrinho está vazio. Escolha um restaurante para começar." />
            ) : (
                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
                    <section className="rounded-2xl border border-slate-200 bg-white p-5">
                        <ul className="divide-y divide-slate-100">
                            {cart.items.map(({ product, quantity }) => (
                                <li
                                    key={product.id}
                                    className="flex flex-wrap items-center justify-between gap-4 py-5"
                                >
                                    <div>
                                        <h2 className="text-sm font-semibold">
                                            {product.name}
                                        </h2>
                                        <p className="mt-2 text-xs text-slate-500">
                                            {money(
                                                decimalToCents(product.price),
                                            )}{' '}
                                            / unidade
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <div className="flex items-center rounded-lg border border-slate-200">
                                            <button
                                                className="p-2"
                                                disabled={quantity <= 1}
                                                aria-label={`Diminuir ${product.name}`}
                                                onClick={() =>
                                                    dispatch({
                                                        type: 'quantity',
                                                        id: product.id,
                                                        quantity: quantity - 1,
                                                    })
                                                }
                                            >
                                                <Minus size={14} />
                                            </button>
                                            <span className="min-w-6 text-center text-sm">
                                                {quantity}
                                            </span>
                                            <button
                                                className="p-2 disabled:opacity-30"
                                                disabled={
                                                    quantity >=
                                                    Math.min(
                                                        product.quantity,
                                                        100,
                                                    )
                                                }
                                                aria-label={`Aumentar ${product.name}`}
                                                onClick={() =>
                                                    dispatch({
                                                        type: 'quantity',
                                                        id: product.id,
                                                        quantity: quantity + 1,
                                                    })
                                                }
                                            >
                                                <Plus size={14} />
                                            </button>
                                        </div>
                                        <strong className="min-w-16 text-right text-sm">
                                            {money(
                                                decimalToCents(product.price) *
                                                    quantity,
                                            )}
                                        </strong>
                                        <button
                                            aria-label={`Remover ${product.name}`}
                                            className="p-2 text-slate-400 hover:text-red-600"
                                            onClick={() =>
                                                dispatch({
                                                    type: 'remove',
                                                    id: product.id,
                                                })
                                            }
                                        >
                                            <Trash2 size={16} />
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                        <button
                            onClick={() => dispatch({ type: 'clear' })}
                            className="mt-4 text-xs text-slate-400 underline"
                        >
                            Limpar carrinho
                        </button>
                    </section>
                    <aside className="rounded-2xl border border-slate-200 bg-white p-6">
                        <h2 className="font-semibold">Resumo do pedido</h2>
                        <div className="mt-5 flex justify-between border-t border-slate-100 pt-5">
                            <span className="text-sm text-slate-500">
                                Subtotal estimado
                            </span>
                            <strong>{money(total)}</strong>
                        </div>
                        <p className="mt-4 text-xs leading-6 text-slate-400">
                            O servidor confirma os preços e o stock ao criar o
                            pedido. Cada pedido pertence a um restaurante.
                        </p>
                        <a
                            href="#checkout"
                            className={`${buttonClass} mt-5 w-full`}
                        >
                            Continuar
                            <ArrowRight size={15} />
                        </a>
                    </aside>
                </div>
            )}
        </>
    );
}
