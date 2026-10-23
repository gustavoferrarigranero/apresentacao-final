import { Plus } from 'lucide-react';
import type { Product } from '@/domains/products/types';
import { buttonClass } from '@/shared/components/api-ui';

export default function AddDishButton({
    product,
    quantity,
    onAdd,
}: {
    product: Product;
    quantity: number;
    onAdd: (product: Product) => void;
}) {
    const unavailable = product.status !== 'active' || product.quantity <= 0;
    const atLimit = quantity >= Math.min(product.quantity, 100);
    return (
        <button
            type="button"
            className={`${buttonClass} min-h-9 rounded-full text-xs`}
            disabled={unavailable || atLimit}
            onClick={() => onAdd(product)}
            aria-label={`${unavailable ? 'Indisponível' : 'Adicionar'} ${product.name}. ${quantity} no carrinho`}
            title={
                atLimit && !unavailable
                    ? 'Limite disponível atingido. Pode ajustar a quantidade no carrinho.'
                    : undefined
            }
        >
            <Plus size={15} aria-hidden="true" />
            {unavailable ? 'Indisponível' : 'Adicionar'}
            <span
                aria-hidden="true"
                className="ml-1 inline-flex min-w-6 items-center justify-center rounded-full bg-white/20 px-1.5 py-0.5 tabular-nums"
            >
                {quantity}
            </span>
        </button>
    );
}
