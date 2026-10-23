import type { Product } from '@/domains/products/types';
export type OrderStatus = 'pending' | 'in_progress' | 'completed' | 'cancelled';
export interface OrderItem {
    id: number;
    product_id: number;
    product_name: string | null;
    quantity: number;
    unit_price: string;
    line_total: string;
}
export interface Order {
    id: number;
    restaurant_id: number;
    restaurant?: { id: number; name: string };
    customer?: { id: number; name: string };
    status: OrderStatus;
    total: string;
    address: string;
    delivery_date: string;
    items: OrderItem[];
    created_at: string;
    updated_at: string;
}
export interface CartItem {
    product: Product;
    quantity: number;
}
export interface CheckoutPayload {
    restaurant_id: number;
    address: string;
    delivery_date?: string;
    items: { product_id: number; quantity: number }[];
}
