import { useMutation } from '@pinia/colada';
import { apiFetch } from '@/lib/http';
import type {
    GenerateSocialMediaContentPayload,
    SocialMediaContentDetail,
    SocialMediaTopicIdea,
    SuggestSocialMediaTopicsPayload,
} from '../types';

/**
 * Non-Inertia JSON AI assist calls for the Social Media wizard.
 * Pinia Colada owns the request lifecycle — `apiFetch` stays inside these
 * composables (FRONTEND §6), never in Vue SFCs. Mirrors
 * {@see usePostAiAssistMutations}.
 */
export function useSuggestSocialMediaTopicsMutation() {
    return useMutation({
        mutation: (input: SuggestSocialMediaTopicsPayload) =>
            apiFetch<{ data: SocialMediaTopicIdea[] }>('POST', '/social-media/ai/suggest-topics', input),
    });
}

export function useGenerateSocialMediaContentMutation() {
    return useMutation({
        mutation: (input: GenerateSocialMediaContentPayload) =>
            apiFetch<{ data: SocialMediaContentDetail }>('POST', '/social-media/ai/generate-content', input),
    });
}

export function useSocialMediaGenerationStatusMutation() {
    return useMutation({
        mutation: (uuid: string) =>
            apiFetch<{ data: SocialMediaContentDetail }>('GET', `/social-media/ai/${uuid}/status`),
    });
}
