import { api } from '@/shared/api/client';
import { payloadFields, type FormValues } from '@/shared/api/payload';
import type { Resource } from '@/shared/api/types';
import type { ApiUser, AuthResult } from './types';
export const userApi = {
    me: (signal?: AbortSignal) =>
        api<Resource<ApiUser>>('/auth/me', { signal }),
    authenticate: (mode: 'login' | 'register', values: FormValues) =>
        api<AuthResult>(`/auth/${mode}`, {
            method: 'POST',
            public: true,
            data: payloadFields(
                values,
                mode === 'register'
                    ? ['name', 'email', 'password', 'password_confirmation']
                    : ['email', 'password'],
            ),
        }),
    logout: () => api<{ message: string }>('/auth/logout', { method: 'POST' }),
    forgotPassword: (values: FormValues) =>
        api<{ message: string }>('/auth/forgot-password', {
            method: 'POST',
            public: true,
            data: payloadFields(values, ['email']),
        }),
    createMember: (values: FormValues) =>
        api<Resource<ApiUser>>('/restaurant/users', {
            method: 'POST',
            data: payloadFields(values, [
                'name',
                'email',
                'password',
                'password_confirmation',
            ]),
        }),
};
