import { useMutation } from '@pinia/colada';
import { apiFetch } from '@/lib/http';
import type {
    CampaignDetail,
    GenerateCampaignPayload,
    CampaignTopicIdea,
    SuggestCampaignTopicsPayload,
} from '../types';

/**
 * Non-Inertia JSON AI assist calls for the Campaigns wizard.
 * Pinia Colada owns the request lifecycle — `apiFetch` stays inside these
 * composables (FRONTEND §6), never in Vue SFCs. Mirrors
 * {@see useSocialMediaAiAssistMutations}.
 */
export function useSuggestCampaignTopicsMutation() {
    return useMutation({
        mutation: (input: SuggestCampaignTopicsPayload) =>
            apiFetch<{ data: CampaignTopicIdea[] }>('POST', '/campaigns/ai/suggest-topics', input),
    });
}

export function useGenerateCampaignMutation() {
    return useMutation({
        mutation: (input: GenerateCampaignPayload) =>
            apiFetch<{ data: CampaignDetail }>('POST', '/campaigns/ai/generate-campaign', input),
    });
}

export function useCampaignGenerationStatusMutation() {
    return useMutation({
        mutation: (uuid: string) =>
            apiFetch<{ data: CampaignDetail }>('GET', `/campaigns/ai/${uuid}/status`),
    });
}
