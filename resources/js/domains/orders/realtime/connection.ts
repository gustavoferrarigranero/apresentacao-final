import { isStatusEvent, orderChannel } from '@/domains/orders/rules';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { api, getToken } from '@/shared/api/client';
import type { ApiUser } from '@/domains/users/types';
import type { OrderStatus } from '@/domains/orders/types';
export interface StatusEvent {
    order_id: number;
    restaurant_id: number;
    status: OrderStatus;
}
export function openOrderConnection(
    user: ApiUser,
    onUpdate: (event: StatusEvent) => void,
    onConnection: (state: string) => void,
) {
    const key = import.meta.env.VITE_REVERB_APP_KEY;
    if (
        !key ||
        !getToken() ||
        !['customer', 'restaurant_admin', 'staff'].includes(user.role)
    )
        return null;
    const channelName = orderChannel(user);
    if (!channelName) return null;
    const echo = new Echo({
        broadcaster: 'reverb',
        Pusher,
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                void (async () => {
                    // A rota de broadcasting usa middleware web e requer CSRF além do token Sanctum.
                    const csrfResponse = await fetch('/sanctum/csrf-cookie', {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    if (!csrfResponse.ok)
                        throw new Error(
                            'Não foi possível preparar a sessão do canal privado.',
                        );
                    return api<{ auth: string; channel_data?: string }>(
                        '/broadcasting/auth',
                        {
                            method: 'POST',
                            data: {
                                socket_id: socketId,
                                channel_name: channel.name,
                            },
                        },
                    );
                })()
                    .then((response) => callback(null, response))
                    .catch(() => {
                        onConnection('denied');
                        callback(
                            new Error(
                                'Não foi possível autorizar o canal privado.',
                            ),
                            null,
                        );
                    });
            },
        }),
    });
    const client = echo.connector.pusher;
    const change = ({ current }: { current: string }) =>
        onConnection(
            current === 'connected'
                ? 'connecting'
                : current === 'connecting'
                  ? 'reconnecting'
                  : current,
        );
    client.connection.bind('state_change', change);
    const channel = echo.private(channelName);
    channel.subscribed(() => onConnection('live'));
    channel.error(() => onConnection('denied'));
    channel.listen('.order.status.updated', (value: unknown) => {
        if (isStatusEvent(value, user)) onUpdate(value);
    });
    const offline = () => {
        onConnection('offline');
        client.disconnect();
    };
    const online = () => {
        onConnection('reconnecting');
        client.connect();
    };
    window.addEventListener('offline', offline);
    window.addEventListener('online', online);
    if (!navigator.onLine) offline();
    return {
        reconnect: () => {
            client.disconnect();
            client.connect();
        },
        close: () => {
            window.removeEventListener('offline', offline);
            window.removeEventListener('online', online);
            client.connection.unbind('state_change', change);
            channel.stopListening('.order.status.updated');
            echo.leave(channelName);
            echo.disconnect();
        },
    };
}
