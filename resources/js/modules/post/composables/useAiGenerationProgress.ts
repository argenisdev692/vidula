import { onUnmounted, reactive } from 'vue';
import { useCurrentUser } from '@/modules/auth/composables/useCurrentUser';
import type { PostAiProgressEvent, PostAiProgressFlow } from '@/modules/post/types';

type ProgressState = Record<PostAiProgressFlow, PostAiProgressEvent | null>;

interface UseAiGenerationProgress {
    progress: ProgressState;
    reset: (flow: PostAiProgressFlow) => void;
}

/**
 * Subscribes to the authenticated user's own private channel for real-time
 * ticks from the Post AI-assist generators (`Modules\Post\Infrastructure\Broadcasting\PostAiGenerationProgress`).
 * A no-op when Reverb isn't configured (`window.Echo` unset) — the panel
 * still works via the plain HTTP response, just without the live progress bar.
 */
export function useAiGenerationProgress(): UseAiGenerationProgress {
    const { user } = useCurrentUser();

    const progress = reactive<ProgressState>({
        topics: null,
        content: null,
        social: null,
        reel: null,
    });

    const channelName = user.value && window.Echo ? `App.Models.User.${user.value.id}` : null;

    if (channelName && window.Echo) {
        window.Echo.private(channelName).listen('.post.ai.progress', (event: PostAiProgressEvent) => {
            progress[event.flow] = event;
        });
    }

    onUnmounted(() => {
        if (channelName) {
            window.Echo?.leave(channelName);
        }
    });

    function reset(flow: PostAiProgressFlow): void {
        progress[flow] = null;
    }

    return { progress, reset };
}
