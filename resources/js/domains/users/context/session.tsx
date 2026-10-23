import { userApi } from '../services';
import { createContext, useContext, useEffect, useState } from 'react';
import type { PropsWithChildren } from 'react';
import { getToken, setToken, errorText } from '@/shared/api/client';
import type { ApiUser } from '@/domains/users/types';

const SessionContext = createContext<{
    user: ApiUser | null;
    loading: boolean;
    error: string;
    authenticate: (
        mode: 'login' | 'register',
        data: Record<string, unknown>,
    ) => Promise<void>;
    logout: () => Promise<void>;
    retry: () => void;
} | null>(null);
export function ApiSessionProvider({ children }: PropsWithChildren) {
    const [user, setUser] = useState<ApiUser | null>(null);
    const [loading, setLoading] = useState(() => !!getToken());
    const [error, setError] = useState('');
    const [revision, setRevision] = useState(0);
    useEffect(() => {
        const controller = new AbortController();
        const unauthorized = () => {
            setUser(null);
            setError('A sessão API expirou. Entre novamente.');
        };
        window.addEventListener('deliveryhub:unauthorized', unauthorized);
        if (getToken()) {
            void userApi
                .me(controller.signal)
                .then((result) => {
                    if (controller.signal.aborted) return;
                    setUser(result.data);
                    setError('');
                })
                .catch((failure) => {
                    if (!controller.signal.aborted)
                        setError(errorText(failure));
                })
                .finally(() => {
                    if (!controller.signal.aborted) setLoading(false);
                });
        }
        return () => {
            controller.abort();
            window.removeEventListener(
                'deliveryhub:unauthorized',
                unauthorized,
            );
        };
    }, [revision]);
    async function authenticate(
        mode: 'login' | 'register',
        data: Record<string, unknown>,
    ) {
        const result = await userApi.authenticate(mode, data);
        if (!result.token || !result.user)
            throw new Error('Resposta de autenticação inválida.');
        setToken(result.token);
        setUser(result.user);
        setError('');
    }
    async function logout() {
        await userApi.logout();
        setToken(null);
        setUser(null);
        setError('');
    }
    return (
        <SessionContext.Provider
            value={{
                user,
                loading,
                error,
                authenticate,
                logout,
                retry: () => {
                    setLoading(!!getToken());
                    setRevision((value) => value + 1);
                },
            }}
        >
            {children}
        </SessionContext.Provider>
    );
}
export function useSession() {
    const value = useContext(SessionContext);
    if (!value) throw new Error('ApiSessionProvider em falta.');
    return value;
}
