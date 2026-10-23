import type { Field } from './api-ui';
export const stateField: Field = {
    name: 'status',
    label: 'Estado',
    type: 'select',
    required: true,
    defaultValue: 'active',
    options: [
        { value: 'active', label: 'Ativo' },
        { value: 'inactive', label: 'Inativo' },
    ],
};
export function ActiveBadge({ status }: { status: string }) {
    return (
        <span
            className={`rounded-full px-2.5 py-1 text-[10px] font-medium ${status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}
        >
            {status === 'active' ? 'Ativo' : 'Inativo'}
        </span>
    );
}
