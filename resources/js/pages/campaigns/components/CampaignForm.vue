<script setup lang="ts">
/**
 * Review/edit form for an AI-generated Meta Ads campaign package. There is no
 * create variant (a campaign is always AI-born — see Create.vue's wizard), so
 * this component only ever backs the Edit page. Submits via Inertia `useForm`
 * PUT, mirroring SocialMediaContentForm's edit path. The score dashboard,
 * per-platform preview and research/optimization panels are read-only —
 * regenerating a campaign happens by starting a new one from the wizard, not
 * by re-running AI in place here.
 */
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import SelectField from '@/common/form/SelectField.vue';
import DateField from '@/common/form/DateField.vue';
import TimeField from '@/common/form/TimeField.vue';
import SubmitButton from '@/common/form/SubmitButton.vue';
import GradientButton from '@/common/form/GradientButton.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import { useConfirmAction } from '@/common/data-table/useConfirmAction';
import { campaignFormSchema } from '@/modules/campaigns/schemas/campaignFormSchema';
import type { CampaignDetail, CampaignStatus, PlatformContent } from '@/modules/campaigns/types';
import { toLocalIsoDate } from '@/lib/date';

const props = defineProps<{
    campaign: CampaignDetail;
}>();

function splitScheduledAt(value: string | null): { date: string | null; time: string | null } {
    if (!value) {
        return { date: null, time: null };
    }
    const [date, time] = value.split('T');
    return { date: date ?? null, time: time ? time.slice(0, 5) : null };
}

const initialSchedule = splitScheduledAt(props.campaign.scheduled_at);

interface CampaignFormFields {
    headline: string;
    primary_text: string;
    description: string;
    call_to_action: string;
    hashtags_text: string;
    lead_form_questions_text: string;
    status: CampaignStatus;
    scheduled_at: string | null;
    scheduled_date: string | null;
    scheduled_time: string | null;
}

const form = useForm<CampaignFormFields>({
    headline: props.campaign.headline ?? '',
    primary_text: props.campaign.primary_text ?? '',
    description: props.campaign.description ?? '',
    call_to_action: props.campaign.call_to_action ?? '',
    hashtags_text: (props.campaign.hashtags ?? []).join(', '),
    lead_form_questions_text: (props.campaign.lead_form_questions ?? []).join('\n'),
    status: (props.campaign.status === 'generating' ? 'draft' : props.campaign.status) as CampaignStatus,
    scheduled_at: props.campaign.scheduled_at,
    scheduled_date: initialSchedule.date,
    scheduled_time: initialSchedule.time,
});

const STATUS_OPTIONS = [
    { label: 'Draft', value: 'draft' },
    { label: 'Ready', value: 'ready' },
    { label: 'Needs review', value: 'needs_review' },
    { label: 'Published', value: 'published' },
    { label: 'Scheduled', value: 'scheduled' },
];

const showScheduling = computed<boolean>(() => form.status === 'scheduled');

/** Scheduling never allows a past date — today onward only. */
const minScheduledDate = toLocalIsoDate(new Date());

watch([() => form.scheduled_date, () => form.scheduled_time], ([date, time]) => {
    form.scheduled_at = date ? `${date}T${time ?? '09:00'}:00` : null;
});

function parseHashtags(text: string): string[] {
    return text
        .split(/[,\s]+/)
        .map((tag) => tag.trim().replace(/^#/, ''))
        .filter(Boolean);
}

function parseLines(text: string): string[] {
    return text
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);
}

function submit(): void {
    const parsed = campaignFormSchema.safeParse({
        headline: form.headline,
        primary_text: form.primary_text,
        description: form.description || null,
        call_to_action: form.call_to_action,
        hashtags: parseHashtags(form.hashtags_text),
        lead_form_questions: parseLines(form.lead_form_questions_text),
        status: form.status,
        scheduled_at: form.scheduled_at,
    });
    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const key = issue.path[0];
            if (key === 'hashtags') {
                form.setError('hashtags_text', issue.message);
            } else if (key === 'lead_form_questions') {
                form.setError('lead_form_questions_text', issue.message);
            } else if (typeof key === 'string') {
                form.setError(key as keyof CampaignFormFields, issue.message);
            }
        }
        return;
    }

    form
        .transform((data) => ({
            headline: data.headline,
            primary_text: data.primary_text,
            description: data.description || null,
            call_to_action: data.call_to_action,
            hashtags: parseHashtags(data.hashtags_text),
            lead_form_questions: parseLines(data.lead_form_questions_text),
            status: data.status,
            scheduled_at: data.status === 'scheduled' ? data.scheduled_at : null,
            _method: 'put',
        }))
        .post(`/campaigns/${props.campaign.uuid}`, { preserveScroll: true });
}

/* ── Publish (separate lifecycle action, gated by its own permission) ──── */
const canPublish = computed<boolean>(() => props.campaign.status === 'ready' || props.campaign.status === 'needs_review');

const {
    visible: publishVisible,
    loading: publishLoading,
    confirm: publishConfirm,
    ask: askPublish,
    run: runPublish,
} = useConfirmAction<true>(() => ({
    title: 'Publish campaign',
    message: `Mark “${props.campaign.topic}” as published? Launching it in Meta Ads Manager is still a manual step outside this app.`,
    confirmLabel: 'Publish',
    confirmIcon: 'pi pi-send',
    tone: 'success',
}));

function confirmPublish(): void {
    runPublish((_action, finish) => {
        router.post(`/campaigns/${props.campaign.uuid}/publish`, {}, { preserveScroll: true, onFinish: finish });
    });
}

function downloadReport(): void {
    window.open(`/campaigns/${props.campaign.uuid}/report`, '_blank');
}

/* ── Score dashboard ──────────────────────────────────────────────────── */
type ScoreResultKey = 'audience_fit_score' | 'virality_score' | 'roi_potential_score' | 'lead_quality_score' | 'trend_relevance_score';

const SCORE_ROWS: { key: ScoreResultKey; label: string }[] = [
    { key: 'audience_fit_score', label: 'Audience fit' },
    { key: 'virality_score', label: 'Virality' },
    { key: 'roi_potential_score', label: 'ROI potential' },
    { key: 'lead_quality_score', label: 'Lead quality' },
    { key: 'trend_relevance_score', label: 'Trend relevance' },
];

function scoreTone(value: number): string {
    if (value >= 75) {
        return 'score-badge--good';
    }
    return value >= 50 ? 'score-badge--warn' : 'score-badge--bad';
}

/* ── Per-platform preview (lightweight custom tabs — no Volt Tabs installed) ── */
const platformKeys = computed<string[]>(() => Object.keys(props.campaign.platforms ?? {}));
const activePlatform = ref<string | null>(platformKeys.value[0] ?? null);
const activePlatformContent = computed<PlatformContent | null>(() => {
    const key = activePlatform.value;
    return key ? (props.campaign.platforms?.[key] ?? null) : null;
});

function platformIcon(platform: string): string {
    const icons: Record<string, string> = {
        facebook: 'pi pi-facebook',
        instagram: 'pi pi-instagram',
    };
    return icons[platform] ?? 'pi pi-share-alt';
}

const detailsOpen = ref<boolean>(false);
</script>

<template>
    <form class="review-form" @submit.prevent="submit">
        <div class="review-form__grid">
            <div class="review-form__main">
                <section v-if="campaign.quality_warning_message" class="warning-banner">
                    <i class="pi pi-exclamation-triangle" aria-hidden="true" />
                    <p>{{ campaign.quality_warning_message }}</p>
                </section>

                <section class="review-form__section">
                    <TextField
                        v-model="form.headline"
                        name="headline"
                        label="Headline"
                        placeholder="The master headline shown across the review list"
                        required
                        :maxlength="255"
                        :error="form.errors.headline"
                    />

                    <TextareaField
                        v-model="form.primary_text"
                        name="primary_text"
                        label="Primary text"
                        placeholder="Master ad copy the per-platform adaptations were derived from"
                        required
                        :rows="8"
                        :error="form.errors.primary_text"
                    />

                    <TextareaField
                        v-model="form.description"
                        name="description"
                        label="Description (optional)"
                        placeholder="Supporting line shown below the headline in some Meta placements"
                        :rows="2"
                        :maxlength="500"
                        :error="form.errors.description"
                    />

                    <TextField
                        v-model="form.call_to_action"
                        name="call_to_action"
                        label="Call to action"
                        placeholder="e.g. Book a free onboarding audit"
                        required
                        :maxlength="100"
                        :error="form.errors.call_to_action"
                    />

                    <TextField
                        v-model="form.hashtags_text"
                        name="hashtags_text"
                        label="Hashtags"
                        placeholder="comma or space separated, e.g. #saas, #metaads"
                        :error="form.errors.hashtags_text"
                        hint="Up to 20 hashtags, 50 characters each."
                    />

                    <TextareaField
                        v-model="form.lead_form_questions_text"
                        name="lead_form_questions_text"
                        label="Lead form questions"
                        placeholder="One question per line — used when Ad format is Lead form"
                        :rows="4"
                        :error="form.errors.lead_form_questions_text"
                        hint="Up to 10 questions, 255 characters each."
                    />
                </section>

                <section class="review-form__section">
                    <div class="review-form__grid-2">
                        <SelectField
                            v-model="form.status"
                            name="status"
                            label="Status"
                            :options="STATUS_OPTIONS"
                            :error="form.errors.status"
                        />
                    </div>

                    <div v-if="showScheduling" class="review-form__grid-2">
                        <DateField
                            v-model="form.scheduled_date"
                            name="scheduled_date"
                            label="Publish date"
                            placeholder="Select a date"
                            required
                            :min-date="minScheduledDate"
                            :error="form.errors.scheduled_at"
                        />
                        <TimeField
                            v-model="form.scheduled_time"
                            name="scheduled_time"
                            label="Publish time"
                            placeholder="Select a time"
                        />
                    </div>
                </section>

                <div class="review-form__actions">
                    <SecondaryButton
                        type="button"
                        label="Cancel"
                        :disabled="form.processing"
                        @click="router.visit('/campaigns')"
                    />
                    <SecondaryButton type="button" label="Download report" icon="pi pi-file-pdf" @click="downloadReport" />
                    <PermissionGuard permission="PUBLISH_CAMPAIGNS">
                        <SecondaryButton
                            v-if="canPublish"
                            type="button"
                            label="Publish"
                            icon="pi pi-send"
                            :disabled="form.processing"
                            @click="askPublish(true)"
                        />
                    </PermissionGuard>
                    <SubmitButton label="Save changes" icon="pi pi-check" :loading="form.processing" />
                </div>
            </div>

            <aside class="review-panel">
                <div v-if="campaign.cover_image_url" class="review-panel__cover">
                    <img :src="campaign.cover_image_url" :alt="campaign.headline ?? campaign.topic" />
                </div>

                <div class="score-dashboard">
                    <div class="score-dashboard__head">
                        <span>Success probability</span>
                        <span
                            v-if="campaign.overall_score_avg !== null"
                            class="score-badge"
                            :class="scoreTone(campaign.overall_score_avg)"
                        >
                            {{ campaign.success_probability_label?.replace('_', ' ') }} — {{ campaign.overall_score_avg }}
                        </span>
                    </div>
                    <ul v-if="campaign.scores" class="score-dashboard__list">
                        <li v-for="row in SCORE_ROWS" :key="row.key">
                            <span class="score-dashboard__label">{{ row.label }}</span>
                            <span class="score-badge" :class="scoreTone(campaign.scores[row.key].value)">
                                {{ campaign.scores[row.key].value }}
                            </span>
                        </li>
                    </ul>
                    <p v-else class="review-panel__empty">Scores unavailable.</p>
                </div>

                <div v-if="campaign.targeting_suggestions?.length" class="details-block">
                    <p class="details-block__label">Targeting suggestions</p>
                    <ul>
                        <li v-for="tip in campaign.targeting_suggestions" :key="tip">{{ tip }}</li>
                    </ul>
                </div>

                <div v-if="platformKeys.length > 0" class="platform-preview">
                    <div class="platform-preview__tabs" role="tablist">
                        <button
                            v-for="key in platformKeys"
                            :key="key"
                            type="button"
                            role="tab"
                            class="platform-preview__tab"
                            :class="{ 'platform-preview__tab--active': activePlatform === key }"
                            :aria-selected="activePlatform === key"
                            @click="activePlatform = key"
                        >
                            <i :class="platformIcon(key)" aria-hidden="true" /> {{ key }}
                        </button>
                    </div>

                    <div v-if="activePlatformContent" class="platform-preview__panel" role="tabpanel">
                        <p class="platform-preview__headline">{{ activePlatformContent.headline }}</p>
                        <p class="platform-preview__text">{{ activePlatformContent.adapted_primary_text }}</p>
                        <p v-if="activePlatformContent.description" class="platform-preview__description">{{ activePlatformContent.description }}</p>
                        <div v-if="activePlatformContent.hashtags.length" class="hashtag-list">
                            <span v-for="tag in activePlatformContent.hashtags" :key="tag" class="hashtag-chip">{{ tag }}</span>
                        </div>
                        <img
                            v-if="activePlatformContent.image_url"
                            :src="activePlatformContent.image_url"
                            :alt="`${activePlatform} cover`"
                            class="platform-preview__image"
                        />
                    </div>
                </div>

                <div class="review-panel__disclosure">
                    <button type="button" class="review-panel__toggle" @click="detailsOpen = !detailsOpen">
                        <i class="pi" :class="detailsOpen ? 'pi-chevron-down' : 'pi-chevron-right'" aria-hidden="true" />
                        Optimization & research details
                    </button>

                    <div v-if="detailsOpen" class="review-panel__details">
                        <div v-if="campaign.optimization_suggestions?.length" class="details-block">
                            <p class="details-block__label">Optimization tips</p>
                            <ul>
                                <li v-for="tip in campaign.optimization_suggestions" :key="tip">{{ tip }}</li>
                            </ul>
                        </div>

                        <div v-if="campaign.research_sources?.length" class="details-block">
                            <p class="details-block__label">Research sources</p>
                            <ul>
                                <li v-for="source in campaign.research_sources" :key="source.source">
                                    {{ source.source }} — {{ source.key_insight }}
                                </li>
                            </ul>
                        </div>

                        <p v-if="campaign.ai_detection_risk" class="details-block__risk">
                            AI-detection risk: {{ campaign.ai_detection_risk.value }} — {{ campaign.ai_detection_risk.label }}
                        </p>
                    </div>
                </div>
            </aside>
        </div>

        <ConfirmDialog
            v-model:visible="publishVisible"
            :title="publishConfirm.title"
            :message="publishConfirm.message"
            :confirm-label="publishConfirm.confirmLabel"
            :confirm-icon="publishConfirm.confirmIcon"
            :tone="publishConfirm.tone"
            :loading="publishLoading"
            @confirm="confirmPublish"
        />
    </form>
</template>

<style scoped>
.review-form__grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 22rem;
    gap: var(--space-6);
    align-items: start;
}

.review-form__main {
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
    min-width: 0;
}

.review-form__section {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.review-form__grid-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-3);
}

.review-form__actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: var(--space-3);
    margin-top: var(--space-2);
    flex-wrap: wrap;
}

.warning-banner {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border: 1px solid color-mix(in srgb, var(--accent-warning) 40%, transparent);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--accent-warning) 10%, transparent);
    color: var(--text-secondary);
}

.warning-banner .pi {
    color: var(--accent-warning);
    margin-top: 2px;
}

.warning-banner p {
    margin: 0;
    font-size: var(--text-sm);
}

.review-panel {
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

.review-panel__cover img {
    width: 100%;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-subtle);
    object-fit: cover;
}

.review-panel__empty {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.score-dashboard {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.score-dashboard__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--space-2);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

.score-dashboard__list {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    list-style: none;
    margin: 0;
    padding: 0;
}

.score-dashboard__list li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: var(--text-sm);
}

.score-dashboard__label {
    color: var(--text-secondary);
}

.score-badge {
    padding: 2px 10px;
    border-radius: var(--radius-full, 99px);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: capitalize;
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

.platform-preview {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    padding-top: var(--space-3);
    border-top: 1px solid var(--border-subtle);
}

.platform-preview__tabs {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-1);
}

.platform-preview__tab {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: var(--space-1) var(--space-3);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-full, 99px);
    background: transparent;
    color: var(--text-secondary);
    font-size: var(--text-xs);
    text-transform: capitalize;
    cursor: pointer;
    transition: border-color var(--transition), color var(--transition), background var(--transition);
}

.platform-preview__tab--active {
    border-color: var(--accent-primary);
    color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 10%, transparent);
}

.platform-preview__panel {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.platform-preview__headline {
    margin: 0;
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.platform-preview__text {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-primary);
    white-space: pre-line;
}

.platform-preview__description {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.platform-preview__image {
    width: 100%;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-subtle);
    object-fit: cover;
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

.review-panel__disclosure {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding-top: var(--space-3);
    border-top: 1px solid var(--border-subtle);
}

.review-panel__toggle {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    width: fit-content;
    padding: 0;
    background: none;
    border: none;
    cursor: pointer;
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    color: var(--accent-primary);
}

.review-panel__details {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.details-block {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.details-block__label {
    margin: 0 0 var(--space-1);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.details-block ul {
    margin: 0;
    padding-left: var(--space-4);
}

.details-block__risk {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}

@media (max-width: 960px) {
    .review-form__grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .review-form__grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>
