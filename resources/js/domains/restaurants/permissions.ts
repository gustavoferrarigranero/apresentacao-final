import type { ApiUser } from '@/domains/users/types';
import type { Restaurant } from './types';
export const canManageRestaurant = (
    user: ApiUser | null,
    restaurant: Restaurant,
) =>
    !!user &&
    (user.role === 'super_admin' ||
        (user.role === 'restaurant_admin' &&
            user.restaurant_id === restaurant.id));
