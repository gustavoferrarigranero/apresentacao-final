import { useEffect, useReducer } from 'react';
import type { ApiUser } from '@/domains/users/types';
import { getToken } from '@/shared/api/client';
import { persistentCartReducer, restoreCart, saveCart } from '../cart-storage';

export function useCart(user: ApiUser | null, sessionLoading: boolean) {
    const [cart, dispatch] = useReducer(
        persistentCartReducer,
        undefined,
        restoreCart,
    );
    useEffect(() => {
        // Do not mistake a pending/temporarily unavailable session for logout.
        if (sessionLoading || (!user && getToken())) return;
        dispatch({ type: 'owner', ownerId: user?.id ?? null });
    }, [user, sessionLoading]);
    useEffect(() => {
        if (!sessionLoading) saveCart(cart);
    }, [cart, sessionLoading]);
    return [cart, dispatch] as const;
}
