import type { Status } from '@/shared/api/types';
export interface Category {
    id: number;
    restaurant_id: number;
    name: string;
    status: Status;
    created_at: string;
    updated_at: string;
}
