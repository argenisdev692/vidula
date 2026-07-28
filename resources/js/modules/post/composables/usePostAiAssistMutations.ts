import { useMutation } from '@pinia/colada';
import { apiFetch } from '@/lib/http';
import type { AiProvider, GeneratedPostContent, PostTopicIdea, ReelPackage, SocialCopy } from '../types';

export interface SuggestPostTopicsInput {
    provider: AiProvider;
    topic: string | null;
}

export interface GeneratePostContentInput {
    topic: string;
    provider: AiProvider;
    angle: string | null;
    key_trend: string | null;
    generate_cover_image: boolean;
}

export interface GenerateContentVariantInput {
    topic: string;
    provider: AiProvider;
    angle: string | null;
    key_trend: string | null;
}

/**
 * Non-Inertia JSON AI assist calls for the Post editor.
 * Pinia Colada owns the request lifecycle — `apiFetch` stays inside these
 * composables (FRONTEND §6), never in Vue SFCs.
 */
export function useSuggestPostTopicsMutation() {
    return useMutation({
        mutation: (input: SuggestPostTopicsInput) =>
            apiFetch<{ data: PostTopicIdea[] }>('POST', '/posts/ai/suggest-topics', input),
    });
}

export function useGeneratePostContentMutation() {
    return useMutation({
        mutation: (input: GeneratePostContentInput) =>
            apiFetch<{ data: GeneratedPostContent }>('POST', '/posts/ai/generate-content', input),
    });
}

export function useGenerateSocialCopyMutation() {
    return useMutation({
        mutation: (input: GenerateContentVariantInput) =>
            apiFetch<{ data: SocialCopy }>('POST', '/posts/ai/generate-social-copy', input),
    });
}

export function useGenerateReelMutation() {
    return useMutation({
        mutation: (input: GenerateContentVariantInput) =>
            apiFetch<{ data: ReelPackage }>('POST', '/posts/ai/generate-reel', input),
    });
}
