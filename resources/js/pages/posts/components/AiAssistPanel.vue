<script setup lang="ts">
/**
 * AI assist panel for the post editor. Two-step flow mirroring the backend
 * ports: (1) suggest up to 10 topic ideas from the company profile + Tavily
 * trends, or type a topic directly; (2) generate a full SEO/EEAT-scored draft
 * for the chosen topic. Nothing here touches the Post aggregate — it only
 * emits `apply` with the generated draft; the parent PostForm decides whether
 * to accept it into the editable fields (the user can still edit before
 * saving). Uses `apiFetch` (native fetch + XSRF header) rather than an Inertia
 * visit, since this is a JSON round-trip that must not navigate the page.
 */
import { ref } from 'vue';
import { apiFetch, HttpError } from '@/lib/http';
import SelectField from '@/common/form/SelectField.vue';
import TextField from '@/common/form/TextField.vue';
import GradientButton from '@/common/form/GradientButton.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import type { AiProvider, GeneratedPostContent, PostTopicIdea } from '@/modules/post/types';

const emit = defineEmits<{ apply: [draft: GeneratedPostContent] }>();

const PROVIDERS = [
    { label: 'OpenAI', value: 'openai' },
    { label: 'Anthropic', value: 'anthropic' },
    { label: 'Gemini', value: 'gemini' },
];

const provider = ref<AiProvider>('openai');
const topicSteer = ref<string>('');
const generateCoverImage = ref<boolean>(true);

const ideasLoading = ref<boolean>(false);
const ideasError = ref<string | null>(null);
const ideas = ref<PostTopicIdea[]>([]);
const selectedIdea = ref<PostTopicIdea | null>(null);

const draftLoading = ref<boolean>(false);
const draftError = ref<string | null>(null);
const draft = ref<GeneratedPostContent | null>(null);

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
    ideasLoading.value = true;
    selectedIdea.value = null;
    try {
        const response = await apiFetch<{ data: PostTopicIdea[] }>('POST', '/posts/ai/suggest-topics', {
            provider: provider.value,
            topic: topicSteer.value || null,
        });
        ideas.value = response.data;
    } catch (e) {
        ideasError.value = errorMessage(e);
    } finally {
        ideasLoading.value = false;
    }
}

function pickIdea(idea: PostTopicIdea): void {
    selectedIdea.value = idea;
}

async function generateDraft(): Promise<void> {
    const topic = selectedIdea.value?.title || topicSteer.value;
    if (!topic.trim()) {
        draftError.value = 'Pick a suggested idea or type a topic first.';
        return;
    }

    draftError.value = null;
    draftLoading.value = true;
    try {
        const response = await apiFetch<{ data: GeneratedPostContent }>('POST', '/posts/ai/generate-content', {
            topic,
            provider: provider.value,
            angle: selectedIdea.value?.angle ?? null,
            key_trend: selectedIdea.value?.key_trend ?? null,
            generate_cover_image: generateCoverImage.value,
        });
        draft.value = response.data;
    } catch (e) {
        draftError.value = errorMessage(e);
    } finally {
        draftLoading.value = false;
    }
}

function applyDraft(): void {
    if (draft.value) {
        emit('apply', draft.value);
    }
}

function scoreTone(score: number): string {
    if (score >= 75) {
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

        <div class="ai-panel__toggle">
            <span>Generate cover image</span>
            <ToggleSwitch v-model="generateCoverImage" input-id="generate_cover_image" aria-label="Generate cover image" />
        </div>

        <GradientButton
            type="button"
            icon="pi pi-sparkles"
            label="Generate draft"
            :loading="draftLoading"
            @click="generateDraft"
        />

        <p v-if="draftError" class="ai-panel__error">{{ draftError }}</p>

        <div v-if="draft" class="draft-result">
            <div class="draft-result__scores">
                <span class="score-badge" :class="scoreTone(draft.human_writing_index)">
                    Human {{ draft.human_writing_index }}
                </span>
                <span class="score-badge" :class="scoreTone(draft.seo_score)">SEO {{ draft.seo_score }}</span>
                <span class="score-badge" :class="scoreTone(draft.eeat_score)">EEAT {{ draft.eeat_score }}</span>
                <span class="score-badge" :class="scoreTone(100 - draft.ai_detection_risk)">
                    AI risk {{ draft.ai_detection_risk }}
                </span>
            </div>

            <img v-if="draft.cover_image_url" :src="draft.cover_image_url" alt="Generated cover" class="draft-result__cover" />

            <p class="draft-result__title">{{ draft.title }}</p>
            <p class="draft-result__excerpt">{{ draft.excerpt }}</p>

            <div v-if="draft.optimization_suggestions.length" class="draft-result__tips">
                <p class="draft-result__tips-label">Optimization tips</p>
                <ul>
                    <li v-for="tip in draft.optimization_suggestions" :key="tip">{{ tip }}</li>
                </ul>
            </div>

            <GradientButton type="button" icon="pi pi-check" label="Apply to editor" @click="applyDraft" />
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

.ai-panel__toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: var(--text-sm);
    color: var(--text-secondary);
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
</style>
