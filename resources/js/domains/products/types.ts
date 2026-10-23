import type { Status } from '@/shared/api/types';
export interface Product {
    id: number;
    restaurant_id: number;
    category_id: number;
    category?: { id: number; name: string };
    name: string;
    type: string;
    price: string;
    quantity: number;
    status: Status;
    created_at: string;
    updated_at: string;
}
