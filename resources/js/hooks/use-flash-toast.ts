import { friendlyMessage } from '@/shared/errors/messages';
import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            toast[data.type](
                data.type === 'error'
                    ? friendlyMessage(data.message)
                    : data.message,
            );
        });
    }, []);
}
