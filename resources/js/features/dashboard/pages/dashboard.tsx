import { orderEndpoint } from '@/domains/orders/services';
import { restaurantEndpoints } from '@/domains/restaurants/services';
import {
    ClipboardList,
    Store,
    Truck,
    Wallet,
    ArrowUpRight,
} from 'lucide-react';
import { useList } from '@/shared/hooks/use-api';
import { useSession } from '@/domains/users/context/session';
import { decimalToCents, money } from '@/shared/utils/money';

import { statusLabels } from '@/domains/orders/rules';
import type { Order, OrderStatus } from '@/domains/orders/types';
import type { Restaurant } from '@/domains/restaurants/types';
import { Title, LoadState, secondaryClass } from '@/shared/components/api-ui';
import { OrderBadge } from '@/domains/orders/components/order-badge';
export default function Dashboard({ revision }: { revision: number }) {
    const { user } = useSession();
    const orders = useList<Order>(orderEndpoint(user), revision, 100);
    const restaurants = useList<Restaurant>(
        user?.role === 'super_admin' ? restaurantEndpoints.list : null,
        revision,
        100,
    );
    if (!user)
        return (
            <>
                <Title
                    title="A sua área"
                    description="Entre para acompanhar a atividade da sua conta."
                />
                <a href="#login" className={secondaryClass}>
                    Entrar na conta
                </a>
            </>
        );
    const rows = orders.value?.data ?? [];
    const counts = (
        ['pending', 'in_progress', 'completed', 'cancelled'] as OrderStatus[]
    ).map((status) => ({
        status,
        count: rows.filter((order) => order.status === status).length,
    }));
    const total = rows.reduce(
        (sum, order) => sum + decimalToCents(order.total),
        0,
    );
    const cards =
        user.role === 'super_admin'
            ? [
                  {
                      label: 'Restaurantes encontrados',
                      value: restaurants.value?.meta.total ?? '—',
                      icon: Store,
                  },
                  {
                      label: 'Ativos nesta página',
                      value:
                          restaurants.value?.data.filter(
                              (restaurant) => restaurant.status === 'active',
                          ).length ?? '—',
                      icon: Store,
                  },
              ]
            : [
                  {
                      label: 'Total de pedidos',
                      value: orders.value?.meta.total ?? '—',
                      icon: ClipboardList,
                  },
                  {
                      label: 'Valor dos pedidos carregados',
                      value: orders.value ? money(total) : '—',
                      icon: Wallet,
                  },
                  {
                      label: 'Em preparação nesta página',
                      value: rows.filter(
                          (order) => order.status === 'in_progress',
                      ).length,
                      icon: Truck,
                  },
                  {
                      label: 'Concluídos nesta página',
                      value: rows.filter(
                          (order) => order.status === 'completed',
                      ).length,
                      icon: ClipboardList,
                  },
              ];
    const chart = Array.from({ length: 7 }, (_, index) => {
        const day = new Date();
        day.setDate(day.getDate() - 6 + index);
        return {
            day,
            count: rows.filter(
                (order) =>
                    new Date(order.created_at).toDateString() ===
                    day.toDateString(),
            ).length,
        };
    });
    const max = Math.max(1, ...chart.map((point) => point.count));
    return (
        <>
            <Title
                title={
                    user.role === 'customer'
                        ? 'A sua atividade'
                        : 'Visão geral do negócio'
                }
                description={`Olá, ${user.name}. Acompanhe os dados disponíveis na sua conta.`}
                action={
                    <a
                        href={
                            user.role === 'super_admin'
                                ? '#restaurants'
                                : '#orders'
                        }
                        className={secondaryClass}
                    >
                        Ver todos
                        <ArrowUpRight size={14} />
                    </a>
                }
            />
            <LoadState
                loading={orders.loading || restaurants.loading}
                error={orders.error || restaurants.error}
                retry={
                    user.role === 'super_admin'
                        ? restaurants.retry
                        : orders.retry
                }
            />
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {cards.map(({ label, value, icon: Icon }, index) => (
                    <section
                        key={label}
                        className={`rounded-2xl border p-5 shadow-sm ${index === 0 ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white'}`}
                    >
                        <div className="mb-6 flex items-center justify-between gap-3">
                            <h2
                                className={`text-xs ${index === 0 ? 'text-white/70' : 'text-slate-500'}`}
                            >
                                {label}
                            </h2>
                            <Icon
                                size={18}
                                className={
                                    index === 0
                                        ? 'text-orange-300'
                                        : 'text-slate-400'
                                }
                            />
                        </div>
                        <p className="text-3xl font-semibold tracking-tight">
                            {value}
                        </p>
                    </section>
                ))}
            </div>
            {user.role === 'super_admin' ? (
                <section className="mt-6 rounded-2xl border bg-white p-6">
                    <h2 className="font-semibold">Gestão da plataforma</h2>
                    <p className="mt-3 text-sm leading-7 text-slate-500">
                        Registe restaurantes e os respetivos administradores. A
                        API atual não disponibiliza uma listagem global de
                        pedidos ou indicadores financeiros da plataforma.
                    </p>
                    <a href="#restaurants" className={`${secondaryClass} mt-5`}>
                        Gerir restaurantes
                    </a>
                </section>
            ) : (
                <>
                    <p className="mt-4 text-xs leading-6 text-slate-400">
                        Indicadores calculados sobre {rows.length} pedidos
                        carregados
                        {orders.value ? ` de ${orders.value.meta.total}` : ''}.
                        O valor dos pedidos não representa pagamentos recebidos.
                    </p>
                    <div className="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
                        <section className="rounded-2xl border bg-white p-6">
                            <h2 className="font-semibold">
                                Pedidos nos últimos sete dias
                            </h2>
                            <p className="mt-2 text-xs text-slate-400">
                                Distribuição dos pedidos carregados, pela data
                                de criação
                            </p>
                            <div className="mt-7 flex h-40 items-end gap-3">
                                {chart.map((point) => (
                                    <div
                                        key={point.day.toISOString()}
                                        className="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2"
                                    >
                                        <span className="text-[10px] text-slate-400">
                                            {point.count}
                                        </span>
                                        <div
                                            role="img"
                                            aria-label={`${point.day.toLocaleDateString('pt-PT')}: ${point.count} pedidos`}
                                            className="w-full rounded-t-lg bg-orange-200"
                                            style={{
                                                height: `${Math.max(2, (point.count / max) * 75)}%`,
                                            }}
                                        />
                                        <span className="text-[10px] text-slate-400">
                                            {point.day.toLocaleDateString(
                                                'pt-PT',
                                                {
                                                    day: '2-digit',
                                                    month: '2-digit',
                                                },
                                            )}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </section>
                        <section className="rounded-2xl border bg-white p-6">
                            <h2 className="font-semibold">
                                Estado dos pedidos
                            </h2>
                            <ul className="mt-6 space-y-5">
                                {counts.map((entry) => (
                                    <li
                                        key={entry.status}
                                        className="flex items-center justify-between text-sm"
                                    >
                                        <OrderBadge status={entry.status} />
                                        <strong>{entry.count}</strong>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    </div>
                    <section className="mt-6 overflow-hidden rounded-2xl border bg-white">
                        <div className="flex items-center justify-between border-b p-5">
                            <h2 className="font-semibold">Pedidos recentes</h2>
                            <a
                                href="#orders"
                                className="text-xs font-semibold text-orange-700"
                            >
                                Ver pedidos
                            </a>
                        </div>
                        <ul className="divide-y divide-slate-100 px-5">
                            {rows.slice(0, 5).map((order) => (
                                <li
                                    key={order.id}
                                    className="flex flex-wrap items-center justify-between gap-4 py-4"
                                >
                                    <a
                                        href={`#order/${order.id}`}
                                        className="text-sm font-semibold"
                                    >
                                        #{order.id}
                                        <span className="ml-3 text-xs font-normal text-slate-400">
                                            {order.restaurant?.name ??
                                                order.customer?.name}
                                        </span>
                                    </a>
                                    <div className="flex items-center gap-4">
                                        <span className="sr-only">
                                            {statusLabels[order.status]}
                                        </span>
                                        <OrderBadge status={order.status} />
                                        <strong className="text-sm">
                                            {money(decimalToCents(order.total))}
                                        </strong>
                                    </div>
                                </li>
                            ))}
                        </ul>
                        {!rows.length && (
                            <p className="p-10 text-center text-sm text-slate-400">
                                Ainda não existem pedidos para apresentar.
                            </p>
                        )}
                    </section>
                </>
            )}
        </>
    );
}
