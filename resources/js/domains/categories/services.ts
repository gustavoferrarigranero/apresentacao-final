import { api } from '@/shared/api/client';
import { payloadFields, type FormValues } from '@/shared/api/payload';
import type { Page, Resource } from '@/shared/api/types';
import type { Category } from './types';
export const categoryEndpoints = {
    list: '/categories',
    detail: (id: number) => `/categories/${id}`,
};
export const categoryApi = {
    save: (id: number | null, values: FormValues) =>
        api<Resource<Category>>(
            id === null ? categoryEndpoints.list : categoryEndpoints.detail(id),
            {
                method: id === null ? 'POST' : 'PATCH',
                data: payloadFields(values, ['name', 'status']),
            },
        ),
    deactivate: (id: number) =>
        api<Resource<Category>>(categoryEndpoints.detail(id), {
            method: 'DELETE',
        }),
    options: async (signal: AbortSignal) => {
        const rows: Category[] = [];
        for (let page = 1; !signal.aborted; page++) {
            const result = await api<Page<Category>>(
                `${categoryEndpoints.list}?per_page=100&page=${page}`,
                { signal },
            );
            rows.push(...result.data);
            if (page >= result.meta.last_page) break;
        }
        return rows;
    },
};
