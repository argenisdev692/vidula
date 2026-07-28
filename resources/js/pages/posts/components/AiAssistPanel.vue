<script setup lang="ts">
/**
 * AI assist panel for the post editor. Two-step flow mirroring the backend
 * ports: (1) suggest up to 10 topic ideas from the company profile + Tavily
 * trends, or type a topic directly; (2) generate a full SEO/EEAT-scored draft
 * for the chosen topic, optionally alongside a social copy (LinkedIn + IG/FB)
 * and/or a Reel/TikTok package (script + AI voiceover via ElevenLabs) —
 * toggled independently, so "none/1/2" variants map directly to which
 * switches are on. Nothing here touches the Post aggregate — it only emits
 * `apply` with the generated draft; the parent PostForm decides whether to
 * accept it into the editable fields (the user can still edit before
 * saving). JSON round-trips go through Pinia Colada mutations in
 * {@see usePostAiAssistMutations} (FRONTEND §6 — no `apiFetch` in SFCs).
 */
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { HttpError } from '@/lib/http';
import SelectField from '@/common/form/SelectField.vue';
import TextField from '@/common/form/TextField.vue';
import GradientButton from '@/common/form/GradientButton.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import { useAiGenerationProgress } from '@/modules/post/composables/useAiGenerationProgress';
import {
    useGeneratePostContentMutation,
    useGenerateReelMutation,
    useGenerateSocialCopyMutation,
    useSuggestPostTopicsMutation,
} from '@/modules/post/composables/usePostAiAssistMutations';
import type { AiProvider, GeneratedPostContent, PostTopicIdea, ReelPackage, SocialCopy } from '@/modules/post/types';
import AiProgressBar from './AiProgressBar.vue';

const emit = defineEmits<{ apply: [draft: GeneratedPostContent] }>();

const toast = useToast();
const { progress, reset: resetProgress } = useAiGenerationProgress();

const { mutateAsync: suggestTopicsAsync, asyncStatus: ideasAsyncStatus } = useSuggestPostTopicsMutation();
const { mutateAsync: generateContentAsync, asyncStatus: draftAsyncStatus } = useGeneratePostContentMutation();
const { mutateAsync: generateSocialAsync, asyncStatus: socialAsyncStatus } = useGenerateSocialCopyMutation();
const { mutateAsync: generateReelAsync, asyncStatus: reelAsyncStatus } = useGenerateReelMutation();

const PROVIDERS = [
    { label: 'OpenAI', value: 'openai' },
    { label: 'Anthropic', value: 'anthropic' },
    { label: 'Gemini', value: 'gemini' },
];

const provider = ref<AiProvider>('openai');
const topicSteer = ref<string>('');
const generateCoverImage = ref<boolean>(true);
const generateSocialCopy = ref<boolean>(false);
const generateReel = ref<boolean>(false);

const ideasError = ref<string | null>(null);
const ideas = ref<PostTopicIdea[]>([]);
const selectedIdea = ref<PostTopicIdea | null>(null);

const draftError = ref<string | null>(null);
const draft = ref<GeneratedPostContent | null>(null);

const socialError = ref<string | null>(null);
const socialCopy = ref<SocialCopy | null>(null);

const reelError = ref<string | null>(null);
const reel = ref<ReelPackage | null>(null);

const ideasLoading = computed<boolean>(() => ideasAsyncStatus.value === 'loading');
const draftLoading = computed<boolean>(() => draftAsyncStatus.value === 'loading');
const socialLoading = computed<boolean>(() => socialAsyncStatus.value === 'loading');
const reelLoading = computed<boolean>(() => reelAsyncStatus.value === 'loading');

function errorMessage(e: unknown): string {
    if (e instanceof HttpError) {
        const body = e.body as { message?: string; errors?: Record<string, string[]> } | undefined;
        const firstFieldError = body?.errors ? Object.values(body.errors)[0]?.[0] : undefined;
        return firstFieldError ?? body?.message ?? `Request failed (HTTP ${e.status}).`;
    }
    return 'Something went wrong. Please try again.';
}

async function suggestTopics(): Promise<void> {
    ideasError.value = null;
    selectedIdea.value = null;
    resetProgress('topics');
    try {
        const response = await suggestTopicsAsync({
            provider: provider.value,
            topic: topicSteer.value || null,
        });
        ideas.value = response.data;
    } catch (e) {
        ideasError.value = errorMessage(e);
    }
}

function pickIdea(idea: PostTopicIdea): void {
    selectedIdea.value = idea;
}

function variantPayload(topic: string): { topic: string; provider: AiProvider; angle: string | null; key_trend: string | null } {
    return {
        topic,
        provider: provider.value,
        angle: selectedIdea.value?.angle ?? null,
        key_trend: selectedIdea.value?.key_trend ?? null,
    };
}

async function generateBlogDraft(topic: string): Promise<void> {
    draftError.value = null;
    resetProgress('content');
    try {
        const response = await generateContentAsync({
            ...variantPayload(topic),
            generate_cover_image: generateCoverImage.value,
        });
        draft.value = response.data;
    } catch (e) {
        draftError.value = errorMessage(e);
    }
}

async function generateSocialCopyVariant(topic: string): Promise<void> {
    socialError.value = null;
    resetProgress('social');
    try {
        const response = await generateSocialAsync(variantPayload(topic));
        socialCopy.value = response.data;
    } catch (e) {
        socialError.value = errorMessage(e);
    }
}

async function generateReelVariant(topic: string): Promise<void> {
    reelError.value = null;
    resetProgress('reel');
    try {
        const response = await generateReelAsync(variantPayload(topic));
        reel.value = response.data;
    } catch (e) {
        reelError.value = errorMessage(e);
    }
}

async function generateDraft(): Promise<void> {
    const topic = selectedIdea.value?.title || topicSteer.value;
    if (!topic.trim()) {
        draftError.value = 'Pick a suggested idea or type a topic first.';
        return;
    }

    socialCopy.value = null;
    reel.value = null;

    const requests = [generateBlogDraft(topic)];
    if (generateSocialCopy.value) {
        requests.push(generateSocialCopyVariant(topic));
    }
    if (generateReel.value) {
        requests.push(generateReelVariant(topic));
    }

    await Promise.all(requests);
}

function applyDraft(): void {
    if (draft.value) {
        emit('apply', draft.value);
    }
}

async function copyToClipboard(text: string, label: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(text);
        toast.add({ severity: 'success', summary: `${label} copied`, life: 2000 });
    } catch {
        toast.add({ severity: 'error', summary: 'Could not copy to clipboard', life: 3000 });
    }
}

function scoreTone(score: number): string {
    if (score >= 70) {
        return 'score-badge--good';
    }
    return score >= 50 ? 'score-badge--warn' : 'score-badge--bad';
}
</script>

<template>
    <aside class="ai-panel">
        <header class="ai-panel__head">
            <i class="pi pi-sparkles" aria-hidden="true" />
            <div>
                <h3>AI assist</h3>
                <p>Get topic ideas from your company profile and current trends, or generate a scored draft.</p>
            </div>
        </header>

        <SelectField
            v-model="provider"
            name="ai_provider"
            label="AI provider"
            :options="PROVIDERS"
        />

        <TextField
            v-model="topicSteer"
            name="ai_topic"
            label="Topic (optional)"
            placeholder="Leave blank for AI-suggested ideas, or type your own topic"
            :maxlength="255"
        />

        <div class="ai-panel__row">
            <SecondaryButton
                type="button"
                label="Suggest 10 topic ideas"
                icon="pi pi-compass"
                :loading="ideasLoading"
                @click="suggestTopics"
            />
        </div>

        <AiProgressBar
            v-if="ideasLoading"
            :message="progress.topics?.message ?? 'Researching current trends…'"
            :percent="progress.topics?.progress ?? 0"
        />
        <p v-if="ideasError" class="ai-panel__error">{{ ideasError }}</p>

        <ul v-if="ideas.length > 0" class="idea-list">
            <li
                v-for="(idea, index) in ideas"
                :key="idea.title"
                class="idea-card"
                :class="{ 'idea-card--selected': selectedIdea?.title === idea.title }"
                @click="pickIdea(idea)"
            >
                <div class="idea-card__head">
                    <span class="idea-card__index">#{{ index + 1 }}</span>
                    <span class="idea-card__title">{{ idea.title }}</span>
                </div>
                <p class="idea-card__hook">“{{ idea.hook }}”</p>
                <div class="idea-card__meta">
                    <span>Virality {{ idea.estimated_virality }}</span>
                    <span>ROI {{ idea.estimated_roi }}</span>
                    <span>EEAT {{ idea.eeat_potential }}</span>
                </div>
            </li>
        </ul>

        <div class="ai-panel__variants">
            <p class="ai-panel__variants-label">Also generate</p>

            <div class="ai-panel__toggle">
                <span><i class="pi pi-image" aria-hidden="true" /> Cover image</span>
                <ToggleSwitch v-model="generateCoverImage" input-id="generate_cover_image" aria-label="Generate cover image" />
            </div>

            <div class="ai-panel__toggle">
                <span><i class="pi pi-share-alt" aria-hidden="true" /> Social copy (LinkedIn + Instagram/Facebook)</span>
                <ToggleSwitch v-model="generateSocialCopy" input-id="generate_social_copy" aria-label="Generate social copy" />
            </div>

            <div class="ai-panel__toggle">
                <span><i class="pi pi-video" aria-hidden="true" /> Reel/TikTok (script + AI voiceover)</span>
                <ToggleSwitch v-model="generateReel" input-id="generate_reel" aria-label="Generate Reel/TikTok package" />
            </div>
        </div>

        <GradientButton
            type="button"
            icon="pi pi-sparkles"
            label="Generate draft"
            :loading="draftLoading"
            @click="generateDraft"
        />

        <AiProgressBar
            v-if="draftLoading"
            :message="progress.content?.message ?? 'Generating the draft…'"
            :percent="progress.content?.progress ?? 0"
        />
        <p v-if="draftError" class="ai-panel__error">{{ draftError }}</p>

        <div v-if="draft" class="draft-result">
            <p v-if="draft.quality_warning" class="draft-result__warning">
                {{ draft.quality_warning_message ?? 'Best attempt after quality loop — review before publishing.' }}
                <span class="draft-result__iters">({{ draft.iterations_required }} iterations)</span>
            </p>

            <div class="draft-result__scores">
                <span class="score-badge" :class="scoreTone(draft.human_writing_index)">
                    Human {{ draft.human_writing_index }}
                </span>
                <span class="score-badge" :class="scoreTone(draft.seo_score)">SEO {{ draft.seo_score }}</span>
                <span class="score-badge" :class="scoreTone(draft.eeat_score)">EEAT {{ draft.eeat_score }}</span>
                <span class="score-badge" :class="scoreTone(draft.virality_score)">Viral {{ draft.virality_score }}</span>
                <span class="score-badge" :class="scoreTone(draft.roi_score)">ROI {{ draft.roi_score }}</span>
                <span class="score-badge" :class="scoreTone(100 - draft.ai_detection_risk)">
                    AI risk {{ draft.ai_detection_risk }}
                </span>
            </div>

            <img v-if="draft.cover_image_url" :src="draft.cover_image_url" alt="Generated cover" class="draft-result__cover" />

            <p class="draft-result__title">{{ draft.title }}</p>
            <p class="draft-result__excerpt">{{ draft.excerpt }}</p>

            <div class="draft-result__prompts">
                <p class="draft-result__tips-label">Image prompts (brand palette)</p>

                <div class="variant-result__field">
                    <div class="variant-result__field-head">
                        <span>1) Background</span>
                        <button
                            type="button"
                            class="copy-btn"
                            @click="copyToClipboard(draft.image_prompts.background, 'Background prompt')"
                        >
                            <i class="pi pi-copy" aria-hidden="true" />
                        </button>
                    </div>
                    <p class="variant-result__text draft-result__prompt-text">{{ draft.image_prompts.background }}</p>
                </div>

                <div class="variant-result__field">
                    <div class="variant-result__field-head">
                        <span>2) Content</span>
                        <button
                            type="button"
                            class="copy-btn"
                            @click="copyToClipboard(draft.image_prompts.content, 'Content prompt')"
                        >
                            <i class="pi pi-copy" aria-hidden="true" />
                        </button>
                    </div>
                    <p class="variant-result__text draft-result__prompt-text">{{ draft.image_prompts.content }}</p>
                </div>
            </div>

            <div v-if="draft.optimization_suggestions.length" class="draft-result__tips">
                <p class="draft-result__tips-label">Optimization tips</p>
                <ul>
                    <li v-for="tip in draft.optimization_suggestions" :key="tip">{{ tip }}</li>
                </ul>
            </div>

            <GradientButton type="button" icon="pi pi-check" label="Apply to editor" @click="applyDraft" />
        </div>

        <AiProgressBar
            v-if="socialLoading"
            :message="progress.social?.message ?? 'Writing social copy…'"
            :percent="progress.social?.progress ?? 0"
        />
        <p v-if="socialError" class="ai-panel__error">{{ socialError }}</p>

        <div v-if="socialCopy" class="variant-result">
            <p class="variant-result__label"><i class="pi pi-share-alt" aria-hidden="true" /> Social copy</p>

            <div class="variant-result__field">
                <div class="variant-result__field-head">
                    <span>LinkedIn</span>
                    <button type="button" class="copy-btn" @click="copyToClipboard(socialCopy.linkedin_post, 'LinkedIn post')">
                        <i class="pi pi-copy" aria-hidden="true" />
                    </button>
                </div>
                <p class="variant-result__text">{{ socialCopy.linkedin_post }}</p>
            </div>

            <div class="variant-result__field">
                <div class="variant-result__field-head">
                    <span>Instagram / Facebook</span>
                    <button type="button" class="copy-btn" @click="copyToClipboard(socialCopy.social_caption, 'Caption')">
                        <i class="pi pi-copy" aria-hidden="true" />
                    </button>
                </div>
                <p class="variant-result__text">{{ socialCopy.social_caption }}</p>
            </div>

            <div class="hashtag-list">
                <span v-for="tag in socialCopy.hashtags" :key="tag" class="hashtag-chip">{{ tag }}</span>
            </div>
        </div>

        <AiProgressBar
            v-if="reelLoading"
            :message="progress.reel?.message ?? 'Building the Reel/TikTok package…'"
            :percent="progress.reel?.progress ?? 0"
        />
        <p v-if="reelError" class="ai-panel__error">{{ reelError }}</p>

        <div v-if="reel" class="variant-result">
            <p class="variant-result__label"><i class="pi pi-video" aria-hidden="true" /> Reel / TikTok</p>

            <div class="reel-timeline">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>On-screen text</th>
                            <th>Voice-over</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(scene, index) in reel.scenes" :key="index">
                            <td>{{ scene.time_range }}</td>
                            <td>{{ scene.action }}</td>
                            <td>{{ scene.on_screen_text }}</td>
                            <td>{{ scene.voiceover_line }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="variant-result__field">
                <div class="variant-result__field-head">
                    <span>Clean script</span>
                    <button type="button" class="copy-btn" @click="copyToClipboard(reel.clean_script, 'Script')">
                        <i class="pi pi-copy" aria-hidden="true" />
                    </button>
                </div>
                <p class="variant-result__text">{{ reel.clean_script }}</p>
            </div>

            <p class="variant-result__sound"><i class="pi pi-volume-up" aria-hidden="true" /> {{ reel.sound_suggestion }}</p>

            <div v-if="reel.voiceover_audio_url" class="reel-audio">
                <audio :src="reel.voiceover_audio_url" controls />
                <a :href="reel.voiceover_audio_url" download class="copy-btn" aria-label="Download voiceover">
                    <i class="pi pi-download" aria-hidden="true" />
                </a>
            </div>
            <p v-else class="ai-panel__status">Voiceover unavailable — script and timeline are still ready to use.</p>

            <div class="variant-result__field">
                <div class="variant-result__field-head">
                    <span>TikTok caption</span>
                    <button type="button" class="copy-btn" @click="copyToClipboard(reel.tiktok_caption, 'Caption')">
                        <i class="pi pi-copy" aria-hidden="true" />
                    </button>
                </div>
                <p class="variant-result__text">{{ reel.tiktok_caption }}</p>
            </div>

            <div class="hashtag-list">
                <span v-for="tag in reel.tiktok_hashtags" :key="tag" class="hashtag-chip">{{ tag }}</span>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.ai-panel {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    padding: var(--space-5);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-xl);
    background: color-mix(in srgb, var(--bg-elevated) 45%, transparent);
    height: fit-content;
    position: sticky;
    top: var(--space-4);
}

.ai-panel__head {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
}

.ai-panel__head .pi {
    font-size: var(--text-xl);
    color: var(--accent-primary);
    margin-top: 2px;
}

.ai-panel__head h3 {
    margin: 0 0 2px;
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.ai-panel__head p {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
    line-height: 1.4;
}

.ai-panel__row {
    display: flex;
}

.ai-panel__variants {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding: var(--space-3);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
}

.ai-panel__variants-label {
    margin: 0;
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

.ai-panel__toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.ai-panel__toggle span {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.ai-panel__toggle .pi {
    color: var(--accent-primary);
}

.ai-panel__status {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.ai-panel__error {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--accent-error);
}

.idea-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    max-height: 20rem;
    overflow-y: auto;
    list-style: none;
    margin: 0;
    padding: 0;
}

.idea-card {
    padding: var(--space-3);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: border-color var(--transition), background var(--transition);
}

.idea-card:hover {
    border-color: color-mix(in srgb, var(--accent-primary) 45%, transparent);
}

.idea-card--selected {
    border-color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 8%, transparent);
}

.idea-card__head {
    display: flex;
    align-items: baseline;
    gap: var(--space-2);
}

.idea-card__index {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.idea-card__title {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.idea-card__hook {
    margin: var(--space-1) 0;
    font-size: var(--text-xs);
    font-style: italic;
    color: var(--text-muted);
}

.idea-card__meta {
    display: flex;
    gap: var(--space-3);
    font-size: var(--text-xs);
    color: var(--text-secondary);
}

.draft-result {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    padding-top: var(--space-3);
    border-top: 1px solid var(--border-subtle);
}

.draft-result__scores {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.draft-result__warning {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-md);
    font-size: var(--text-xs);
    color: var(--accent-warning);
    background: color-mix(in srgb, var(--accent-warning) 12%, transparent);
    border: 1px solid color-mix(in srgb, var(--accent-warning) 28%, transparent);
}

.draft-result__iters {
    color: var(--text-muted);
}

.draft-result__prompts {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.draft-result__prompt-text {
    max-height: 7rem;
    overflow-y: auto;
    white-space: pre-wrap;
}

.score-badge {
    padding: 2px 10px;
    border-radius: var(--radius-full, 99px);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
}

.score-badge--good {
    color: var(--accent-success);
    background: color-mix(in srgb, var(--accent-success) 14%, transparent);
}

.score-badge--warn {
    color: var(--accent-warning);
    background: color-mix(in srgb, var(--accent-warning) 14%, transparent);
}

.score-badge--bad {
    color: var(--accent-error);
    background: color-mix(in srgb, var(--accent-error) 14%, transparent);
}

.draft-result__cover {
    width: 100%;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-subtle);
    object-fit: cover;
}

.draft-result__title {
    margin: 0;
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.draft-result__excerpt {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.draft-result__tips {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.draft-result__tips-label {
    margin: 0 0 var(--space-1);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.draft-result__tips ul {
    margin: 0;
    padding-left: var(--space-4);
}

.variant-result {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    padding-top: var(--space-3);
    border-top: 1px solid var(--border-subtle);
}

.variant-result__label {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

.variant-result__label .pi {
    color: var(--accent-primary);
}

.variant-result__field {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.variant-result__field-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    color: var(--text-secondary);
}

.variant-result__text {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-primary);
    white-space: pre-line;
}

.variant-result__sound {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    font-size: var(--text-xs);
    font-style: italic;
    color: var(--text-muted);
}

.copy-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-1) var(--space-2);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
    background: transparent;
    color: var(--text-secondary);
    cursor: pointer;
    transition: border-color var(--transition), color var(--transition);
}

.copy-btn:hover {
    border-color: var(--accent-primary);
    color: var(--accent-primary);
}

.hashtag-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.hashtag-chip {
    padding: 2px 10px;
    border-radius: var(--radius-full, 99px);
    font-size: var(--text-xs);
    font-family: var(--font-mono, monospace);
    color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 10%, transparent);
}

.reel-timeline {
    overflow-x: auto;
}

.reel-timeline table {
    width: 100%;
    min-width: 32rem;
    border-collapse: collapse;
    font-size: var(--text-xs);
}

.reel-timeline th,
.reel-timeline td {
    padding: var(--space-2);
    border-bottom: 1px solid var(--border-subtle);
    text-align: left;
    vertical-align: top;
    color: var(--text-secondary);
}

.reel-timeline th {
    color: var(--text-muted);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}

.reel-audio {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.reel-audio audio {
    flex: 1;
    min-width: 0;
    height: 2.25rem;
}
</style>
