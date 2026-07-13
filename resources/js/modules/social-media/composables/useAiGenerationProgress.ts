import { onUnmounted, ref, type Ref } from 'vue';
import { useCurrentUser } from '@/modules/auth/composables/useCurrentUser';
import type { SocialMediaAiProgressEvent } from '@/modules/social-media/types';

interface UseAiGenerationProgress {
    progress: Ref<SocialMediaAiProgressEvent | null>;
    reset: () => void;
}

/**
 * Subscribes to the authenticated user's own private channel for real-time
 * ticks from the quality-loop Job (`Modules\SocialMedia\Infrastructure\Broadcasting\SocialMediaAiGenerationProgress`).
 * Unlike Post's AI-assist (four parallel synchronous flows), generation here
 * is a single async job per wizard session, so `progress` is one ref rather
 * than a flow map — callers compare `progress.value?.content_uuid` against
 * the row they're waiting on. A no-op when Reverb isn't configured
 * (`window.Echo` unset) — the wizard still works via the `ai/{uuid}/status`
 * polling fallback, just without the live progress bar.
 */
export function useAiGenerationProgress(): UseAiGenerationProgress {
    const { user } = useCurrentUser();

    const progress = ref<SocialMediaAiProgressEvent | null>(null);

    const channelName = user.value && window.Echo ? `App.Models.User.${user.value.id}` : null;

    if (channelName && window.Echo) {
        window.Echo.private(channelName).listen('.social-media.ai.progress', (event: SocialMediaAiProgressEvent) => {
            progress.value = event;
        });
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
