import { useEffect, useRef, useState } from 'react';
import { openOrderConnection } from '../realtime/connection';
import { useSession } from '@/domains/users/context/session';
const labels: Record<string, string> = {
    unconfigured: 'Atualização periódica',
    live: 'Pedidos em tempo real',
    connecting: 'A ligar ao tempo real',
    reconnecting: 'A restabelecer ligação',
    denied: 'Canal privado indisponível',
    offline: 'Sem ligação',
    unavailable: 'Ligação indisponível',
    disconnected: 'Ligação interrompida',
    failed: 'Ligação indisponível',
};
export default function Connection({ changed }: { changed: () => void }) {
    const { user } = useSession();
    const callback = useRef(changed);
    const [state, setState] = useState('unconfigured');
    const reconnect = useRef<() => void>(() => undefined);
    useEffect(() => {
        callback.current = changed;
    }, [changed]);
    useEffect(() => {
        if (!user) return;
        let closed = false;
        let wasLive = false;
        const connection = openOrderConnection(
            user,
            () => callback.current(),
            (value) => {
                if (!closed) {
                    setState(value);
                    if (value === 'live') {
                        if (wasLive) callback.current();
                        wasLive = true;
                    }
                }
            },
        );
        reconnect.current = () => connection?.reconnect();
        // Inclui alterações que o backend não publica, como cancelamento pelo cliente.
        const timer = window.setInterval(() => {
            if (!document.hidden && navigator.onLine) callback.current();
        }, 30000);
        return () => {
            closed = true;
            clearInterval(timer);
            connection?.close();
            reconnect.current = () => undefined;
        };
    }, [user]);
    if (!user || user.role === 'super_admin') return null;
    return (
        <div
            role="status"
            className="flex items-center gap-2 text-[10px] text-slate-400"
        >
            <span
                className={`size-1.5 rounded-full ${state === 'live' ? 'bg-emerald-500' : 'bg-slate-300'}`}
                aria-hidden="true"
            />
            {labels[state] ?? 'Atualização periódica'}
            {['denied', 'failed', 'unavailable'].includes(state) && (
                <button
                    className="underline"
                    onClick={() => reconnect.current()}
                >
                    Tentar novamente
                </button>
            )}
        </div>
    );
}
