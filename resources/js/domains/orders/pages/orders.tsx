import { orderApi, orderEndpoint } from '../services';
import { useState } from 'react';
import type { Order, OrderStatus } from '@/domains/orders/types';
import type { Resource } from '@/shared/api/types';
import { useSession } from '@/domains/users/context/session';
import { useList, useResource } from '@/shared/hooks/use-api';
import { date } from '@/shared/utils/date';
import { decimalToCents, money } from '@/shared/utils/money';

import { transitions } from '@/domains/orders/rules';
import {
    Title,
    LoadState,
    Pagination,
    Empty,
    ConfirmAction,
    secondaryClass,
} from '@/shared/components/api-ui';
import { OrderBadge } from '../components/order-badge';
export default function Orders({
    id,
    revision,
    changed,
}: {
    id?: number;
    revision: number;
    changed: () => void;
}) {
    const { user } = useSession();
    const endpoint = orderEndpoint(user);
    const list = useList<Order>(!id ? endpoint : null, revision);
    const detail = useResource<Resource<Order>>(
        id && endpoint ? `${endpoint}/${id}` : null,
        revision,
    );
    const [confirm, setConfirm] = useState<{
        order: Order;
        status: OrderStatus;
    } | null>(null);
    if (!endpoint)
        return (
            <>
                <Title title="Pedidos" />
                <Empty text="Entre como cliente ou como membro de um restaurante para consultar os seus pedidos." />
            </>
        );
    const resource = id ? detail : list;
    const actions = (order: Order) =>
        transitions(order.status, user).map((status) => (
            <button
                key={status}
                className={secondaryClass}
                onClick={() => setConfirm({ order, status })}
            >
                {status === 'cancelled'
                    ? 'Cancelar pedido'
                    : status === 'in_progress'
                      ? 'Iniciar preparação'
                      : 'Concluir pedido'}
            </button>
        ));
    return (
        <>
            <Title
                title={
                    id
                        ? `Pedido #${id}`
                        : user?.role === 'customer'
                          ? 'Os meus pedidos'
                          : 'Pedidos do restaurante'
                }
                description="Acompanhe os estados e consulte os detalhes de cada pedido."
                action={
                    id ? (
                        <a href="#orders" className={secondaryClass}>
                            Voltar à listagem
                        </a>
                    ) : (
                        <button
                            className={secondaryClass}
                            disabled={resource.loading}
                            onClick={changed}
                        >
                            Atualizar
                        </button>
                    )
                }
            />
            <LoadState
                loading={resource.loading}
                error={resource.error}
                retry={resource.retry}
            />
            {!id && list.value && !list.loading && (
                <>
                    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[580px] text-left text-xs">
                                <caption className="sr-only">
                                    Pedidos da conta autenticada
                                </caption>
                                <thead className="bg-slate-50 text-[10px] text-slate-400 uppercase">
                                    <tr>
                                        {[
                                            'Pedido',
                                            user?.role === 'customer'
                                                ? 'Restaurante'
                                                : 'Cliente',
                                            'Data',
                                            'Estado',
                                            'Total',
                                            '',
                                        ].map((label, index) => (
                                            <th
                                                key={index}
                                                scope="col"
                                                className="px-5 py-4 font-medium"
                                            >
                                                {label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {list.value.data.map((order) => (
                                        <tr key={order.id}>
                                            <td className="px-5 py-5 font-semibold">
                                                #{order.id}
                                            </td>
                                            <td className="px-5 py-5">
                                                {user?.role === 'customer'
                                                    ? order.restaurant?.name
                                                    : order.customer?.name}
                                            </td>
                                            <td className="px-5 py-5 whitespace-nowrap">
                                                {date(order.created_at)}
                                            </td>
                                            <td className="px-5 py-5">
                                                <OrderBadge
                                                    status={order.status}
                                                />
                                            </td>
                                            <td className="px-5 py-5 font-medium whitespace-nowrap">
                                                {money(
                                                    decimalToCents(order.total),
                                                )}
                                            </td>
                                            <td className="px-5 py-5">
                                                <button
                                                    type="button"
                                                    className={secondaryClass}
                                                    aria-label={`Ver detalhes do pedido #${order.id}`}
                                                    onClick={() => {
                                                        window.location.hash = `order/${order.id}`;
                                                    }}
                                                >
                                                    Ver detalhes
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {!list.value.data.length && (
                            <Empty text="Ainda não existem pedidos nesta conta." />
                        )}
                    </div>
                    <Pagination data={list.value} go={list.go} />
                </>
            )}
            {id && detail.value && !detail.loading && (
                <section className="rounded-2xl border border-slate-200 bg-white p-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 className="text-lg font-semibold">
                                {detail.value.data.restaurant?.name ??
                                    detail.value.data.customer?.name ??
                                    `Pedido #${id}`}
                            </h2>
                            <p className="mt-2 text-xs text-slate-400">
                                Criado em {date(detail.value.data.created_at)}
                            </p>
                        </div>
                        <OrderBadge status={detail.value.data.status} />
                    </div>
                    <dl className="mt-7 grid gap-5 border-y border-slate-100 py-5 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="mb-1 text-xs text-slate-400">
                                Morada de entrega
                            </dt>
                            <dd>{detail.value.data.address}</dd>
                        </div>
                        <div>
                            <dt className="mb-1 text-xs text-slate-400">
                                Data de entrega
                            </dt>
                            <dd>{date(detail.value.data.delivery_date)}</dd>
                        </div>
                    </dl>
                    <ul className="mt-4 divide-y divide-slate-100">
                        {detail.value.data.items.map((item) => (
                            <li
                                key={item.id}
                                className="flex justify-between gap-4 py-4 text-sm"
                            >
                                <span>
                                    {item.quantity} ×{' '}
                                    {item.product_name ??
                                        `Produto #${item.product_id}`}
                                </span>
                                <span className="whitespace-nowrap">
                                    {money(decimalToCents(item.line_total))}
                                </span>
                            </li>
                        ))}
                    </ul>
                    <div className="mt-3 flex justify-between border-t pt-4 font-semibold">
                        <span>Total confirmado</span>
                        <span>
                            {money(decimalToCents(detail.value.data.total))}
                        </span>
                    </div>
                    <div className="mt-7 flex flex-wrap gap-3">
                        {actions(detail.value.data)}
                    </div>
                </section>
            )}
            {confirm && (
                <ConfirmAction
                    title={
                        confirm.status === 'cancelled'
                            ? `Cancelar pedido #${confirm.order.id}?`
                            : `Atualizar pedido #${confirm.order.id}?`
                    }
                    action={() =>
                        orderApi.transition(user, confirm.order, confirm.status)
                    }
                    close={() => setConfirm(null)}
                    done={() => {
                        setConfirm(null);
                        changed();
                    }}
                />
            )}
        </>
    );
}
