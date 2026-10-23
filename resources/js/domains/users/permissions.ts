import type { ApiUser } from './types';
export const roleLabels = {
    super_admin: 'Administração global',
    restaurant_admin: 'Administrador do restaurante',
    staff: 'Equipa do restaurante',
    customer: 'Cliente',
};
export const isTeam = (user: ApiUser | null) =>
    !!user &&
    ['restaurant_admin', 'staff'].includes(user.role) &&
    user.restaurant_id !== null;
