import { orderApi } from '../services';
import { useRef, useState } from 'react';
import type { CartItem, Order } from '@/domains/orders/types';

import { ApiError, errorText } from '@/shared/api/client';
import { checkoutPayload } from '@/domains/orders/rules';
import { decimalToCents, money } from '@/shared/utils/money';
import { useSession } from '@/domains/users/context/session';
import {
    Title,
    Fields,
    Feedback,
    buttonClass,
    secondaryClass,
} from '@/shared/components/api-ui';
import type { FormEvent } from 'react';
export default function Checkout({
    items,
    clear,
}: {
    items: CartItem[];
    clear: () => void;
}) {
    const { user } = useSession();
    const [busy, setBusy] = useState(false);
    const lock = useRef(false);
    const [error, setError] = useState('');
    const [fields, setFields] = useState<Record<string, string>>({});
    const [created, setCreated] = useState<Order | null>(null);
    const [uncertain, setUncertain] = useState(false);
    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (lock.current || user?.role !== 'customer') return;
        const data = new FormData(event.currentTarget);
        setError('');
        setFields({});
        setUncertain(false);
        let payload;
        try {
            payload = checkoutPayload(
                items,
                typeof data.get('address') === 'string'
                    ? (data.get('address') as string)
                    : '',
                typeof data.get('delivery_date') === 'string'
                    ? (data.get('delivery_date') as string)
                    : '',
            );
        } catch (failure) {
            setError(errorText(failure));
            return;
        }
        lock.current = true;
        setBusy(true);
        try {
            const response = await orderApi.create(payload);
            setCreated(response.data);
            clear();
        } catch (failure) {
            setFields(failure instanceof ApiError ? failure.fields : {});
            setError(
                errorText(failure) +
                    (failure instanceof ApiError && failure.requestId
                        ? ` Referência: ${failure.requestId}`
                        : ''),
            );
            if (!(failure instanceof ApiError) || failure.status >= 500)
                setUncertain(true);
        } finally {
            lock.current = false;
            setBusy(false);
        }
    }
    if (created)
        return (
            <section className="mx-auto max-w-xl rounded-2xl border border-emerald-100 bg-white p-8 text-center">
                <p className="text-xs font-semibold tracking-widest text-emerald-600 uppercase">
                    Pedido criado
                </p>
                <h1 className="mt-4 text-2xl font-semibold">
                    Obrigado pelo seu pedido!
                </h1>
                <p role="status" className="mt-4 text-sm text-slate-500">
                    Pedido #{created.id} · Total confirmado:{' '}
                    {money(decimalToCents(created.total))}
                </p>
                <a
                    href={`#order/${created.id}`}
                    className={`${buttonClass} mt-6`}
                >
                    Acompanhar pedido
                </a>
            </section>
        );
    return (
        <>
            <Title
                title="Finalizar pedido"
                description="Confirme a morada. O restaurante irá receber o seu pedido."
            />
            {!user ? (
                <section className="rounded-xl border bg-white p-8">
                    <p className="text-sm text-slate-500">
                        Entre com uma conta de cliente para concluir o pedido. O
                        carrinho é mantido enquanto navega nesta aplicação.
                    </p>
                    <a href="#login" className={`${buttonClass} mt-5`}>
                        Entrar
                    </a>
                </section>
            ) : user.role !== 'customer' ? (
                <Feedback message="A criação de pedidos é exclusiva das contas de cliente." />
            ) : !items.length ? (
                <Feedback message="Adicione produtos ao carrinho antes de continuar." />
            ) : (
                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
                    <form
                        noValidate
                        onSubmit={(event) => void submit(event)}
                        aria-busy={busy}
                        className="rounded-2xl border bg-white p-6"
                    >
                        <Feedback message={error} />
                        <fieldset disabled={busy} className="grid gap-5">
                            <Fields
                                fields={[
                                    {
                                        name: 'address',
                                        label: 'Morada de entrega',
                                        type: 'textarea',
                                        required: true,
                                    },
                                    {
                                        name: 'delivery_date',
                                        label: 'Data de entrega',
                                        type: 'date',
                                        hint: 'Opcional. Se não indicar uma data, o pedido fica para hoje.',
                                    },
                                ]}
                                errors={fields}
                            />
                            {Object.entries(fields)
                                .filter(
                                    ([key]) =>
                                        !['address', 'delivery_date'].includes(
                                            key,
                                        ),
                                )
                                .map(([key, value]) => (
                                    <p
                                        key={key}
                                        className="text-sm text-red-700"
                                    >
                                        {key.startsWith('items.')
                                            ? `Produto ${Number(key.split('.')[1]) + 1}: `
                                            : ''}
                                        {value}
                                    </p>
                                ))}
                        </fieldset>
                        {uncertain ? (
                            <div
                                role="alert"
                                className="mt-5 rounded-lg bg-amber-50 p-4 text-sm leading-6 text-amber-800"
                            >
                                O resultado do envio não foi confirmado.
                                Consulte os pedidos antes de repetir para evitar
                                duplicação.
                                <a
                                    href="#orders"
                                    className="mt-2 block font-semibold underline"
                                >
                                    Consultar os meus pedidos
                                </a>
                                <button
                                    type="button"
                                    className="mt-3 text-xs underline"
                                    onClick={() => setUncertain(false)}
                                >
                                    Já verifiquei os pedidos; permitir nova
                                    tentativa
                                </button>
                            </div>
                        ) : (
                            <button
                                disabled={busy}
                                className={`${buttonClass} mt-6 w-full`}
                            >
                                {busy ? 'A enviar pedido…' : 'Confirmar pedido'}
                            </button>
                        )}
                        <a
                            href="#cart"
                            className={`${secondaryClass} mt-4 w-full`}
                        >
                            Voltar ao carrinho
                        </a>
                    </form>
                    <aside className="rounded-2xl border bg-white p-6">
                        <h2 className="font-semibold">O seu pedido</h2>
                        <ul className="mt-4 divide-y">
                            {items.map((item) => (
                                <li
                                    key={item.product.id}
                                    className="flex justify-between gap-3 py-3 text-xs"
                                >
                                    <span>
                                        {item.quantity} × {item.product.name}
                                    </span>
                                    <span>
                                        {money(
                                            decimalToCents(item.product.price) *
                                                item.quantity,
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <div className="mt-3 flex justify-between border-t pt-4 text-sm font-semibold">
                            <span>Subtotal</span>
                            <span>
                                {money(
                                    items.reduce(
                                        (sum, item) =>
                                            sum +
                                            decimalToCents(item.product.price) *
                                                item.quantity,
                                        0,
                                    ),
                                )}
                            </span>
                        </div>
                        <p className="mt-4 text-xs leading-6 text-slate-400">
                            O fluxo atual da API cria um pedido pendente. Não
                            existe cobrança por cartão nesta operação.
                        </p>
                    </aside>
                </div>
            )}
        </>
    );
}
