<script setup lang="ts">
/**
 * New content — the AI wizard itself (GET /social-media/create, CREATE_SOCIAL_MEDIA).
 * There is no manual "create" form: content is always AI-born. Step 1 asks
 * the topic-ideation agent for up to 10 candidate topics (or the user types
 * one directly); Step 2 kicks off the async quality-loop job
 * (`GenerateSocialMediaContentJob`, up to 5 iterations) and returns
 * immediately with a `generating` row — this page then waits on the
 * real-time progress channel (with a manual status refresh fallback when
 * Reverb isn't reachable) and hands off to the Edit/review screen once the
 * job finishes.
 */
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import TextField from '@/common/form/TextField.vue';
import SelectField from '@/common/form/SelectField.vue';
import GradientButton from '@/common/form/GradientButton.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import AiProgressBar from './components/AiProgressBar.vue';
import { apiFetch, HttpError } from '@/lib/http';
import { useAiGenerationProgress } from '@/modules/social-media/composables/useAiGenerationProgress';
import type {
    AiProvider,
    BrandVoice,
    BusinessGoal,
    ContentLanguage,
    FunnelStage,
    GenerateSocialMediaContentPayload,
    SocialMediaContentDetail,
    SocialMediaTopicIdea,
} from '@/modules/social-media/types';

defineOptions({ layout: AppLayout });

const PROVIDERS: { label: string; value: AiProvider }[] = [
    { label: 'OpenAI', value: 'openai' },
    { label: 'Anthropic', value: 'anthropic' },
    { label: 'Gemini', value: 'gemini' },
];

const LANGUAGES: { label: string; value: ContentLanguage }[] = [
    { label: 'Español (LatAm neutro)', value: 'es' },
    { label: 'English', value: 'en' },
];

const BUSINESS_GOALS: { label: string; value: BusinessGoal }[] = [
    { label: 'Awareness', value: 'awareness' },
    { label: 'Engagement', value: 'engagement' },
    { label: 'Viral', value: 'viral' },
    { label: 'Leads', value: 'leads' },
    { label: 'Sales', value: 'sales' },
    { label: 'Community', value: 'community' },
];

const BRAND_VOICES: { label: string; value: BrandVoice }[] = [
    { label: 'Professional', value: 'professional' },
    { label: 'Conversational', value: 'conversational' },
    { label: 'Trendy', value: 'trendy' },
    { label: 'Inspirational', value: 'inspirational' },
    { label: 'Humorous', value: 'humorous' },
];

const FUNNEL_STAGES: { label: string; value: FunnelStage }[] = [
    { label: 'TOFU — awareness', value: 'tofu' },
    { label: 'MOFU — consideration', value: 'mofu' },
    { label: 'BOFU — decision', value: 'bofu' },
];

/* ── Step 1 — topic ideation ──────────────────────────────────────────── */
const provider = ref<AiProvider>('openai');
const language = ref<ContentLanguage>('es');
const niche = ref<string>('');
const audience = ref<string>('');
const businessGoal = ref<BusinessGoal | null>(null);
const topicSteer = ref<string>('');

const ideasLoading = ref<boolean>(false);
const ideasError = ref<string | null>(null);
const ideas = ref<SocialMediaTopicIdea[]>([]);
const selectedIdea = ref<SocialMediaTopicIdea | null>(null);

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
        const response = await apiFetch<{ data: SocialMediaTopicIdea[] }>('POST', '/social-media/ai/suggest-topics', {
            provider: provider.value,
            language: language.value,
            niche: niche.value || null,
            audience: audience.value || null,
            business_goal: businessGoal.value,
        });
        ideas.value = response.data;
    } catch (e) {
        ideasError.value = errorMessage(e);
    } finally {
        ideasLoading.value = false;
    }
}

function pickIdea(idea: SocialMediaTopicIdea): void {
    selectedIdea.value = idea;
    topicSteer.value = '';
    if (idea.funnel_stage) {
        funnelStage.value = idea.funnel_stage;
    }
}

const chosenTopic = computed<string>(() => selectedIdea.value?.title ?? topicSteer.value.trim());

function difficultyTone(difficulty: string): string {
    const value = difficulty.toLowerCase();
    if (value.includes('easy') || value.includes('low')) {
        return 'idea-card__difficulty--easy';
    }
    return value.includes('hard') || value.includes('high') ? 'idea-card__difficulty--hard' : 'idea-card__difficulty--medium';
}

/* ── Step 2 — generation config + kickoff ─────────────────────────────── */
const brandVoice = ref<BrandVoice>('professional');
const funnelStage = ref<FunnelStage>('tofu');
const generateImages = ref<boolean>(true);
const generateVoiceover = ref<boolean>(true);

const generateLoading = ref<boolean>(false);
const generateError = ref<string | null>(null);
const generatedUuid = ref<string | null>(null);

const { progress, reset: resetProgress } = useAiGenerationProgress();

const activeProgress = computed(() =>
    generatedUuid.value && progress.value?.content_uuid === generatedUuid.value ? progress.value : null,
);

async function generateContent(): Promise<void> {
    if (!chosenTopic.value) {
        generateError.value = 'Pick a suggested idea or type a topic first.';
        return;
    }

    generateError.value = null;
    generateLoading.value = true;
    resetProgress();

    const payload: GenerateSocialMediaContentPayload = {
        topic: chosenTopic.value,
        provider: provider.value,
        language: language.value,
        business_goal: businessGoal.value ?? 'awareness',
        brand_voice: brandVoice.value,
        funnel_stage: funnelStage.value,
        angle: selectedIdea.value?.angle ?? null,
        hook: selectedIdea.value?.hook ?? null,
        key_trend: selectedIdea.value?.key_trend ?? null,
        niche: niche.value || null,
        audience: audience.value || null,
        generate_images: generateImages.value,
        generate_voiceover: generateVoiceover.value,
    };

    try {
        const response = await apiFetch<{ data: SocialMediaContentDetail }>('POST', '/social-media/ai/generate-content', payload);
        generatedUuid.value = response.data.uuid;
    } catch (e) {
        generateError.value = errorMessage(e);
    } finally {
        generateLoading.value = false;
    }
}

/* ── Terminal state — hand off to the review screen ───────────────────── */
const statusChecking = ref<boolean>(false);

async function checkStatus(): Promise<void> {
    if (!generatedUuid.value) {
        return;
    }
    statusChecking.value = true;
    try {
        const response = await apiFetch<{ data: SocialMediaContentDetail }>('GET', `/social-media/ai/${generatedUuid.value}/status`);
        if (response.data.status !== 'generating') {
            goToReview();
        }
    } catch (e) {
        generateError.value = errorMessage(e);
    } finally {
        statusChecking.value = false;
    }
}

function goToReview(): void {
    if (generatedUuid.value) {
        router.visit(`/social-media/${generatedUuid.value}/edit`);
    }
}

const isDone = computed<boolean>(() => activeProgress.value?.stage === 'completed');
</script>

<template>
    <Head title="New content" />

    <AppHeader title="New content" subtitle="Pick a topic, then let the quality-loop agent build a scored, multi-platform package" />

    <PermissionGuard permission="CREATE_SOCIAL_MEDIA">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to create social media content.</p>
            </div>
        </template>

        <div class="form-page">
            <BackLink href="/social-media" label="Back to social media" />

            <article class="card">
                <!-- ══ Generation in progress / done ══ -->
                <section v-if="generatedUuid" class="wizard-step">
                    <header class="wizard-step__head">
                        <i class="pi pi-sparkles" aria-hidden="true" />
                        <div>
                            <h2>Generating your content package</h2>
                            <p>The agent runs up to 5 quality-loop iterations — this can take a couple of minutes.</p>
                        </div>
                    </header>

                    <AiProgressBar
                        v-if="!isDone"
                        :message="activeProgress?.message ?? 'Starting the quality-loop…'"
                        :percent="activeProgress?.progress ?? 5"
                    />

                    <p v-if="isDone" class="wizard-step__done">
                        <i class="pi pi-check-circle" aria-hidden="true" /> Content ready — review it now.
                    </p>

                    <div class="wizard-step__actions">
                        <SecondaryButton
                            type="button"
                            label="Refresh status"
                            icon="pi pi-refresh"
                            :loading="statusChecking"
                            @click="checkStatus"
                        />
                        <GradientButton type="button" icon="pi pi-arrow-right" label="Go to review" @click="goToReview" />
                    </div>
                </section>

                <!-- ══ Step 1 — topic ══ -->
                <template v-else>
                    <section class="wizard-step">
                        <header class="wizard-step__head">
                            <span class="wizard-step__index">1</span>
                            <div>
                                <h2>Choose a topic</h2>
                                <p>Get ideas from your company profile and current trends, or type your own topic.</p>
                            </div>
                        </header>

                        <div class="wizard-step__grid">
                            <SelectField v-model="provider" name="provider" label="AI provider" :options="PROVIDERS" />
                            <SelectField v-model="language" name="language" label="Language" :options="LANGUAGES" />
                            <SelectField
                                v-model="businessGoal"
                                name="business_goal"
                                label="Business goal (optional)"
                                placeholder="No preference"
                                :options="BUSINESS_GOALS"
                                show-clear
                            />
                        </div>

                        <div class="wizard-step__grid">
                            <TextField v-model="niche" name="niche" label="Niche (optional)" placeholder="e.g. B2B SaaS onboarding" :maxlength="255" />
                            <TextField v-model="audience" name="audience" label="Audience (optional)" placeholder="e.g. Ops managers at mid-market companies" :maxlength="255" />
                        </div>

                        <div class="wizard-step__row">
                            <SecondaryButton
                                type="button"
                                label="Suggest 10 topic ideas"
                                icon="pi pi-compass"
                                :loading="ideasLoading"
                                @click="suggestTopics"
                            />
                        </div>

                        <p v-if="ideasError" class="wizard-step__error">{{ ideasError }}</p>

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
                                    <span class="idea-card__difficulty" :class="difficultyTone(idea.difficulty)">{{ idea.difficulty }}</span>
                                </div>
                                <p class="idea-card__hook">“{{ idea.hook }}”</p>
                                <div class="idea-card__meta">
                                    <span><i class="pi pi-bolt" aria-hidden="true" /> Virality {{ idea.estimated_virality }}</span>
                                    <span><i class="pi pi-chart-line" aria-hidden="true" /> ROI {{ idea.estimated_roi }}</span>
                                    <span><i class="pi pi-filter" aria-hidden="true" /> {{ idea.funnel_stage.toUpperCase() }}</span>
                                    <span><i class="pi pi-share-alt" aria-hidden="true" /> {{ idea.platform }}</span>
                                </div>
                            </li>
                        </ul>

                        <TextField
                            v-model="topicSteer"
                            name="topic_steer"
                            label="Or type your own topic"
                            placeholder="e.g. 5 onboarding mistakes killing your activation rate"
                            :maxlength="255"
                            @update:model-value="selectedIdea = null"
                        />
                    </section>

                    <!-- ══ Step 2 — generation config ══ -->
                    <section v-if="chosenTopic" class="wizard-step">
                        <header class="wizard-step__head">
                            <span class="wizard-step__index">2</span>
                            <div>
                                <h2>Generate content</h2>
                                <p>Topic: <strong>{{ chosenTopic }}</strong></p>
                            </div>
                        </header>

                        <div class="wizard-step__grid">
                            <SelectField v-model="brandVoice" name="brand_voice" label="Brand voice" :options="BRAND_VOICES" />
                            <SelectField v-model="funnelStage" name="funnel_stage" label="Funnel stage" :options="FUNNEL_STAGES" />
                        </div>

                        <div class="wizard-step__toggles">
                            <div class="wizard-step__toggle">
                                <span><i class="pi pi-image" aria-hidden="true" /> Generate cover images</span>
                                <ToggleSwitch v-model="generateImages" input-id="generate_images" aria-label="Generate cover images" />
                            </div>
                            <div class="wizard-step__toggle">
                                <span><i class="pi pi-volume-up" aria-hidden="true" /> Generate voiceover (TikTok)</span>
                                <ToggleSwitch v-model="generateVoiceover" input-id="generate_voiceover" aria-label="Generate voiceover" />
                            </div>
                        </div>

                        <p v-if="generateError" class="wizard-step__error">{{ generateError }}</p>

                        <div class="wizard-step__actions">
                            <GradientButton
                                type="button"
                                icon="pi pi-sparkles"
                                label="Generate content"
                                :loading="generateLoading"
                                @click="generateContent"
                            />
                        </div>
                    </section>
                </template>
            </article>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.form-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    width: 100%;
    max-width: 56rem;
    margin-inline: auto;
}

.card {
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6) var(--space-8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.wizard-step {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.wizard-step__head {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
}

.wizard-step__head .pi {
    font-size: var(--text-xl);
    color: var(--accent-primary);
    margin-top: 2px;
}

.wizard-step__index {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--grad-primary, var(--accent-primary));
    color: var(--on-accent);
    font-size: var(--text-sm);
    font-weight: var(--font-bold);
}

.wizard-step__head h2 {
    margin: 0 0 2px;
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.wizard-step__head p {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
    line-height: 1.4;
}

.wizard-step__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
    gap: var(--space-3);
}

.wizard-step__row {
    display: flex;
}

.wizard-step__toggles {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding: var(--space-3);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md);
}

.wizard-step__toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.wizard-step__toggle span {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.wizard-step__toggle .pi {
    color: var(--accent-primary);
}

.wizard-step__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-3);
}

.wizard-step__error {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--accent-error);
}

.wizard-step__done {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--accent-success);
}

.idea-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    max-height: 26rem;
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
    flex-wrap: wrap;
}

.idea-card__index {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.idea-card__title {
    flex: 1;
    min-width: 0;
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.idea-card__difficulty {
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    padding: 1px 8px;
    border-radius: var(--radius-full, 99px);
    text-transform: capitalize;
}

.idea-card__difficulty--easy {
    color: var(--accent-success);
    background: color-mix(in srgb, var(--accent-success) 14%, transparent);
}

.idea-card__difficulty--medium {
    color: var(--accent-warning);
    background: color-mix(in srgb, var(--accent-warning) 14%, transparent);
}

.idea-card__difficulty--hard {
    color: var(--accent-error);
    background: color-mix(in srgb, var(--accent-error) 14%, transparent);
}

.idea-card__hook {
    margin: var(--space-1) 0;
    font-size: var(--text-xs);
    font-style: italic;
    color: var(--text-muted);
}

.idea-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    font-size: var(--text-xs);
    color: var(--text-secondary);
}

.idea-card__meta span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-16) var(--space-6);
    color: var(--text-muted);
}

.empty .pi {
    font-size: var(--text-3xl);
}

@media (max-width: 640px) {
    .card {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
