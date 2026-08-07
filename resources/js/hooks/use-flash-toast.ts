import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

type FlashProps = { success?: string | null; error?: string | null };

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('success', (event) => {
            const flash = ((event as CustomEvent).detail?.page?.props?.flash ??
                {}) as FlashProps;

            if (flash.success) {
                toast.success(flash.success);
            }

            if (flash.error) {
                toast.error(flash.error);
            }
        });
    }, []);
}
