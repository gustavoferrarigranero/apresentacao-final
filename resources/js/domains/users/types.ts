export type Role = 'super_admin' | 'restaurant_admin' | 'staff' | 'customer';
export interface ApiUser {
    id: number;
    name: string;
    email: string;
    role: Role;
    restaurant_id: number | null;
}
export interface AuthResult {
    user: ApiUser;
    token: string;
    message?: string;
}
