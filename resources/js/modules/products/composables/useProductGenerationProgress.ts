import { onUnmounted, ref, type Ref } from 'vue';
import { useCurrentUser } from '@/modules/auth/composables/useCurrentUser';
import type { ProductGenerationProgressEvent } from '@/modules/products/types';

interface UseProductGenerationProgress {
    progress: Ref<ProductGenerationProgressEvent | null>;
    reset: () => void;
}

/**
 * Subscribes to the authenticated user's private channel for real-time ticks
 * from GenerateProductContentJob (`products.generation.progress`).
 * No-op when Reverb/Echo is unset — Show still works via status polling.
 */
export function useProductGenerationProgress(): UseProductGenerationProgress {
    const { user } = useCurrentUser();

    const progress = ref<ProductGenerationProgressEvent | null>(null);

    const channelName = user.value && window.Echo ? `App.Models.User.${user.value.id}` : null;

    if (channelName && window.Echo) {
        window.Echo.private(channelName).listen(
            '.products.generation.progress',
            (event: ProductGenerationProgressEvent) => {
                progress.value = event;
            },
        );
    }

    onUnmounted(() => {
        if (channelName) {
            window.Echo?.leave(channelName);
        }
    });

    function reset(): void {
        progress.value = null;
    }

    return { progress, reset };
}
