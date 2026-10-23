import { listUrl } from '../api/pagination';
import { useCallback, useEffect, useRef, useState } from 'react';
import { api, errorText } from '@/shared/api/client';
import type { Page } from '@/shared/api/types';
export function useResource<T>(path: string | null, revision = 0) {
    const [value, setValue] = useState<T | null>(null);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(!!path);
    const [attempt, setAttempt] = useState(0);
    const [resolvedPath, setResolvedPath] = useState<string | null>(null);
    useEffect(() => {
        if (!path) return;
        const controller = new AbortController();
        setLoading(true);
        setError('');
        void api<T>(path, { signal: controller.signal })
            .then((result) => {
                if (!controller.signal.aborted) {
                    setValue(result);
                    setResolvedPath(path);
                }
            })
            .catch((failure) => {
                if (!controller.signal.aborted) {
                    setError(errorText(failure));
                    setResolvedPath(path);
                    setValue(null);
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [path, revision, attempt]);
    return {
        value: path && resolvedPath === path ? value : null,
        error: path && resolvedPath === path ? error : '',
        loading: !!path && (loading || resolvedPath !== path),
        retry: () => setAttempt((current) => current + 1),
    };
}
export function useList<T>(
    endpoint: string | null,
    revision = 0,
    perPage = 12,
    search = '',
) {
    const [page, setPage] = useState(1);
    const key = `${endpoint}|${search}`;
    const [pageKey, setPageKey] = useState(key);
    const effectivePage = pageKey === key ? page : 1;
    const resource = useResource<Page<T>>(
        endpoint ? listUrl(endpoint, effectivePage, perPage, search) : null,
        revision,
    );
    return {
        ...resource,
        page: effectivePage,
        go: (next: number) => {
            setPageKey(key);
            setPage(next);
        },
        refresh: resource.retry,
    };
}
export function useMutation() {
    const lock = useRef(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const run = useCallback(
        async <T>(work: () => Promise<T>, success: (value: T) => void) => {
            if (lock.current) return;
            lock.current = true;
            setBusy(true);
            setError('');
            try {
                success(await work());
            } catch (failure) {
                setError(errorText(failure));
            } finally {
                lock.current = false;
                setBusy(false);
            }
        },
        [],
    );
    return { busy, error, run };
}
