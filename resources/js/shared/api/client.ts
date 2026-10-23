const tokenKey = 'deliveryhub.api.token';
let memoryToken: string | null | undefined;
export function getToken(): string | null {
    if (memoryToken !== undefined) return memoryToken;
    try {
        memoryToken = sessionStorage.getItem(tokenKey);
    } catch {
        memoryToken = null;
    }
    return memoryToken;
}
export function setToken(token: string | null) {
    memoryToken = token;
    try {
        if (token) sessionStorage.setItem(tokenKey, token);
        else sessionStorage.removeItem(tokenKey);
    } catch {
        /* A sessão continua em memória se o navegador bloquear o armazenamento. */
    }
}
export class ApiError extends Error {
    constructor(
        message: string,
        public status: number,
        public fields: Record<string, string> = {},
        public requestId: string | null = null,
    ) {
        super(message);
    }
}
export function localUrl(input: string) {
    const parsed = new URL(input, window.location.origin);
    if (parsed.origin !== window.location.origin)
        throw new Error('A API deve pertencer à origem da aplicação.');
    return parsed.pathname + parsed.search;
}
export function xsrf() {
    return decodeURIComponent(
        document.cookie
            .split('; ')
            .find((item) => item.startsWith('XSRF-TOKEN='))
            ?.slice('XSRF-TOKEN='.length) ?? '',
    );
}
export async function api<T>(
    path: string,
    options: {
        method?: string;
        data?: unknown;
        signal?: AbortSignal;
        public?: boolean;
    } = {},
): Promise<T> {
    if (/^(?:https?:)?\/\//i.test(path)) path = localUrl(path);
    const url = localUrl(
        path.startsWith('/api/') ||
            path.startsWith('/broadcasting/') ||
            path.startsWith('/sanctum/')
            ? path
            : `/api/v1${path}`,
    );
    const token = options.public ? null : getToken();
    const response = await fetch(url, {
        method: options.method ?? 'GET',
        signal: options.signal,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Request-Id': crypto.randomUUID(),
            ...(options.data === undefined
                ? {}
                : { 'Content-Type': 'application/json' }),
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...(xsrf() ? { 'X-XSRF-TOKEN': xsrf() } : {}),
        },
        body:
            options.data === undefined
                ? undefined
                : JSON.stringify(options.data),
    });
    const body = await response.json().catch(() => null);
    if (!response.ok) {
        const fields: Record<string, string> = {};
        if (body?.errors && typeof body.errors === 'object')
            for (const [key, value] of Object.entries(body.errors)) {
                const first = Array.isArray(value) ? value[0] : value;
                if (typeof first === 'string') fields[key] = first;
            }
        if (response.status === 401 && token) {
            setToken(null);
            window.dispatchEvent(new Event('deliveryhub:unauthorized'));
        }
        throw new ApiError(
            typeof body?.message === 'string'
                ? body.message
                : response.status === 419
                  ? 'A sessão expirou. Atualize a página e tente novamente.'
                  : `O pedido falhou (${response.status}).`,
            response.status,
            fields,
            response.headers.get('X-Request-Id'),
        );
    }
    if (response.status === 204) return undefined as T;
    if (body === null)
        throw new ApiError(
            'O servidor devolveu uma resposta inesperada.',
            response.status,
        );
    return body as T;
}
export const errorText = (error: unknown) =>
    error instanceof Error && !(error instanceof TypeError)
        ? error.message
        : 'Não foi possível contactar o servidor. Verifique a ligação e tente novamente.';
