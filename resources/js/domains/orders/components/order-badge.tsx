import type { OrderStatus } from '../types';
import { statusLabels } from '../rules';
const badges: Record<OrderStatus, string> = {
    pending: 'bg-amber-50 text-amber-700',
    in_progress: 'bg-orange-50 text-orange-700',
    completed: 'bg-emerald-50 text-emerald-700',
    cancelled: 'bg-slate-100 text-slate-500',
};
export function OrderBadge({ status }: { status: OrderStatus }) {
    return (
        <span
            className={`inline-flex rounded-full px-3 py-1 text-[11px] font-medium ${badges[status]}`}
        >
            {statusLabels[status]}
        </span>
    );
}
