import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('success', (event) => {
        const flash = event.detail.page.props.flash as { toast?: FlashToast } | undefined;
        const data = flash?.toast;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });
}