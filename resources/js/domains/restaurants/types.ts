import type { Status } from '@/shared/api/types';
export interface Restaurant {
    id: number;
    name: string;
    address: string;
    phone_number: string;
    nif: string;
    status: Status;
    created_at: string;
    updated_at: string;
}
