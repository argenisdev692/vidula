<script setup lang="ts">
/**
 * Studio run detail — refined CV, job matches, outreach drafts (GET /resume-studio/runs/{uuid}).
 */
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import SelectField from '@/common/form/SelectField.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import Message from '@/volt/Message.vue';
import { formatDate } from '@/modules/resume-studio/helpers/buildStudioQueryParams';
import {
    modeLabel,
    statusLabel,
    statusTone,
    stepLabel,
} from '@/modules/resume-studio/helpers/labels';
import { APPLICATION_STATUS_OPTIONS } from '@/modules/resume-studio/helpers/options';
import { RESUME_LANGUAGE_OPTIONS } from '@/modules/resume-studio/helpers/locationScopes';
import type {
    ApplicationStatus,
    OutreachDraft,
    RefinedCv,
    RefinedCvFeedback,
    ResumeLanguage,
    StudioRun,
} from '@/modules/resume-studio/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    run: StudioRun;
}>();

const toast = useToast();

const markingSent = ref<string | null>(null);
const updatingMatch = ref<string | null>(null);
const pollTimer = ref<ReturnType<typeof setInterval> | null>(null);

const title = computed<string>(() => {
    const cvTitle = props.run.cv?.title;
    return cvTitle ? `${cvTitle} — Studio run` : 'Studio run';
});

const isSuspended = computed<boolean>(() => props.run.deleted_at !== null);

const isActive = computed<boolean>(
    () => props.run.status === 'pending' || props.run.status === 'running',
);

const latestRefined = computed<RefinedCv | null>(() => {
    const list = props.run.refined_cvs ?? [];
    if (list.length === 0) {
        return null;
    }
    return list.reduce((best, item) => (item.version > best.version ? item : best), list[0]);
});

const feedback = computed<RefinedCvFeedback | null>(() => latestRefined.value?.feedback ?? null);

const jobMatches = computed(() => props.run.job_matches ?? []);

const outreachDrafts = computed(() => props.run.outreach_drafts ?? []);

function resumeLanguageLabel(code: ResumeLanguage | string): string {
    const match = RESUME_LANGUAGE_OPTIONS.find((option) => option.value === code);
    return match?.label ?? code;
}

function stopPolling(): void {
    if (pollTimer.value !== null) {
        clearInterval(pollTimer.value);
        pollTimer.value = null;
    }
}

function startPolling(): void {
    stopPolling();
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const intervalMs = reducedMotion ? 8000 : 4000;
    pollTimer.value = setInterval(() => {
        router.reload({ only: ['run'], preserveScroll: true });
    }, intervalMs);
}

watch(isActive, (active) => {
    if (active) {
        startPolling();
    } else {
        stopPolling();
    }
});

onMounted(() => {
    if (isActive.value) {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
});

function onApplicationStatusChange(uuid: string, status: ApplicationStatus): void {
    updatingMatch.value = uuid;
    router.patch(
        `/resume-studio/matches/${uuid}`,
        { application_status: status },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['run'],
            onFinish: () => {
                updatingMatch.value = null;
            },
        },
    );
}

async function copyBody(draft: OutreachDraft): Promise<void> {
    const text = draft.body ?? '';
    if (!text) {
        return;
    }
    try {
        await navigator.clipboard.writeText(text);
        toast.add({ severity: 'success', summary: 'Body copied', life: 3000 });
    } catch {
        toast.add({ severity: 'warn', summary: 'Could not copy to clipboard', life: 4000 });
    }
}

function markSent(draft: OutreachDraft): void {
    markingSent.value = draft.uuid;
    router.post(
        `/resume-studio/drafts/${draft.uuid}/mark-sent`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['run'],
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Marked as sent manually', life: 4000 });
            },
            onFinish: () => {
                markingSent.value = null;
            },
        },
    );
}

function draftKindLabel(kind: OutreachDraft['kind']): string {
    return kind === 'cover' ? 'Cover letter' : 'Digest';
}
</script>

<template>
    <Head :title="title" />

    <DetailCard
        header-title="Resume Studio"
        header-subtitle="Run progress, refined CV, matches, and drafts"
        permission="VIEW_RESUME_STUDIOS"
        fallback-text="You don't have permission to view this studio run."
        back-href="/resume-studio"
        back-label="Back to Resume Studio"
        :title="run.cv?.title ?? 'Studio run'"
        max-width="72rem"
    >
        <template #badges>
            <StatusBadge
                :tone="run.mode === 'career' ? 'primary' : 'muted'"
                :label="modeLabel(run.mode)"
            />
            <StatusBadge :tone="statusTone(run.status)" :label="statusLabel(run.status)" />
            <StatusBadge tone="muted" :label="stepLabel(run.step)" />
            <StatusBadge
                v-if="isSuspended"
                tone="danger"
                label="Suspended"
            />
        </template>

        <div class="run-meta">
            <p class="run-meta__item">
                <span class="run-meta__label">Started</span>
                <span class="mono">{{ formatDate(run.started_at ?? run.created_at) }}</span>
            </p>
            <p v-if="run.finished_at" class="run-meta__item">
                <span class="run-meta__label">Finished</span>
                <span class="mono">{{ formatDate(run.finished_at) }}</span>
            </p>
            <p v-if="isActive" class="run-meta__item run-meta__item--live">
                <i class="pi pi-sync" aria-hidden="true" />
                <span>Live updates while the run is in progress</span>
            </p>
        </div>

        <Message v-if="run.error_summary" severity="error" :closable="false" class="run-error">
            {{ run.error_summary }}
        </Message>

        <div class="dual-pane">
            <section class="pane pane--cv" aria-label="Refined CV">
                <header class="pane__head">
                    <h3 class="pane__title">Refined CV</h3>
                    <div class="pane__actions">
                        <StatusBadge
                            v-if="latestRefined?.ats_score !== null && latestRefined?.ats_score !== undefined"
                            tone="primary"
                            :label="`Heuristic ATS ${latestRefined.ats_score}/100`"
                        />
                        <PermissionGuard permission="EXPORT_RESUME_STUDIOS">
                            <a
                                v-if="latestRefined?.uuid"
                                class="pdf-link"
                                :href="`/resume-studio/refined/${latestRefined.uuid}/pdf`"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <i class="pi pi-file-pdf" aria-hidden="true" />
                                Download PDF
                            </a>
                        </PermissionGuard>
                    </div>
                </header>

                <p v-if="latestRefined?.target_job_title" class="pane__subtitle">
                    Target: {{ latestRefined.target_job_title }}
                    <template v-if="latestRefined.resume_language">
                        · {{ resumeLanguageLabel(latestRefined.resume_language) }}
                    </template>
                </p>
                <p
                    v-else-if="latestRefined?.resume_language"
                    class="pane__subtitle"
                >
                    CV language: {{ resumeLanguageLabel(latestRefined.resume_language) }}
                </p>

                <pre v-if="latestRefined?.refined_md" class="md-preview">{{ latestRefined.refined_md }}</pre>
                <p v-else class="muted">Refined markdown will appear when the refine step completes.</p>

                <div v-if="feedback" class="feedback">
                    <h4 class="feedback__title">Heuristic feedback</h4>
                    <div v-if="feedback.strengths?.length" class="feedback__block">
                        <p class="feedback__label">Strengths</p>
                        <ul>
                            <li v-for="(item, index) in feedback.strengths" :key="`s-${index}`">{{ item }}</li>
                        </ul>
                    </div>
                    <div v-if="feedback.improvements?.length" class="feedback__block">
                        <p class="feedback__label">Improvements</p>
                        <ul>
                            <li v-for="(item, index) in feedback.improvements" :key="`i-${index}`">{{ item }}</li>
                        </ul>
                    </div>
                    <div v-if="feedback.keyword_gaps?.length" class="feedback__block">
                        <p class="feedback__label">Keyword gaps</p>
                        <ul>
                            <li v-for="(item, index) in feedback.keyword_gaps" :key="`k-${index}`">{{ item }}</li>
                        </ul>
                    </div>
                    <div v-if="feedback.weak_lines?.length" class="feedback__block">
                        <p class="feedback__label">10s-scan weak lines</p>
                        <ul>
                            <li v-for="(item, index) in feedback.weak_lines" :key="`w-${index}`">{{ item }}</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="pane pane--matches" aria-label="Job matches and drafts">
                <header class="pane__head">
                    <h3 class="pane__title">Job matches</h3>
                    <span class="muted">{{ jobMatches.length }} matches</span>
                </header>

                <ul v-if="jobMatches.length > 0" class="match-list">
                    <li
                        v-for="match in jobMatches"
                        :key="match.uuid"
                        class="match-item"
                        :class="{ 'match-item--deleted': match.deleted_at }"
                    >
                        <div class="match-item__head">
                            <div class="match-item__titles">
                                <span class="match-score">{{ match.match_score ?? '—' }}</span>
                                <span class="match-title">{{ match.job_title ?? 'Untitled role' }}</span>
                                <span v-if="match.company_name" class="match-company">{{ match.company_name }}</span>
                            </div>
                            <a
                                v-if="match.job_url"
                                :href="match.job_url"
                                class="match-link"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <i class="pi pi-external-link" aria-hidden="true" />
                                Open
                            </a>
                        </div>

                        <PermissionGuard permission="UPDATE_RESUME_STUDIOS">
                            <SelectField
                                :model-value="match.application_status"
                                name="application_status"
                                label="Application status"
                                :options="APPLICATION_STATUS_OPTIONS"
                                :disabled="updatingMatch === match.uuid || match.deleted_at !== null"
                                @update:model-value="
                                    (value) =>
                                        onApplicationStatusChange(
                                            match.uuid,
                                            value as ApplicationStatus,
                                        )
                                "
                            />
                        </PermissionGuard>
                    </li>
                </ul>
                <p v-else class="muted">Matches appear after the search and scoring steps.</p>

                <header class="pane__head pane__head--drafts">
                    <h3 class="pane__title">Outreach drafts</h3>
                    <span class="muted">{{ outreachDrafts.length }} drafts</span>
                </header>

                <ul v-if="outreachDrafts.length > 0" class="draft-list">
                    <li v-for="draft in outreachDrafts" :key="draft.uuid" class="draft-item">
                        <div class="draft-item__head">
                            <StatusBadge tone="muted" :label="draftKindLabel(draft.kind)" />
                            <span class="draft-subject">{{ draft.subject ?? 'No subject' }}</span>
                            <StatusBadge tone="muted" :label="draft.status.replace('_', ' ')" />
                        </div>
                        <pre v-if="draft.body" class="draft-body">{{ draft.body }}</pre>
                        <div class="draft-actions">
                            <SecondaryButton
                                type="button"
                                label="Copy body"
                                icon="pi pi-copy"
                                size="small"
                                :disabled="!draft.body"
                                @click="copyBody(draft)"
                            />
                            <PermissionGuard permission="UPDATE_RESUME_STUDIOS">
                                <SecondaryButton
                                    v-if="draft.status !== 'sent_manually' && draft.status !== 'sent_automated'"
                                    type="button"
                                    label="Mark sent"
                                    icon="pi pi-check"
                                    size="small"
                                    :loading="markingSent === draft.uuid"
                                    @click="markSent(draft)"
                                />
                            </PermissionGuard>
                        </div>
                    </li>
                </ul>
                <p v-else class="muted">Drafts appear after the drafting step completes.</p>
            </section>
        </div>
    </DetailCard>
</template>

<style scoped>
.run-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
}

.run-meta__item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.run-meta__label {
    font-weight: var(--font-medium);
    color: var(--text-muted);
}

.run-meta__item--live {
    color: var(--accent-primary);
}

.run-meta__item--live .pi {
    font-size: 0.85rem;
}

.run-error {
    margin-bottom: var(--space-5);
}

.dual-pane {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: var(--space-6);
}

.pane {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    min-width: 0;
}

.pane__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    flex-wrap: wrap;
}

.pane__actions {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    flex-wrap: wrap;
}

.pdf-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--accent-primary);
    text-decoration: none;
}

.pdf-link:hover {
    text-decoration: underline;
}

.pdf-link:focus-visible {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
}

.pane__head--drafts {
    margin-top: var(--space-4);
    padding-top: var(--space-4);
    border-top: 1px solid var(--border-subtle);
}

.pane__title {
    margin: 0;
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.pane__subtitle {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.md-preview,
.draft-body {
    margin: 0;
    padding: var(--space-4);
    max-height: 28rem;
    overflow: auto;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-subtle);
    background: var(--bg-card);
    color: var(--text-secondary);
    font-family: var(--font-mono, monospace);
    font-size: var(--text-xs);
    white-space: pre-wrap;
    word-break: break-word;
}

.draft-body {
    max-height: 10rem;
}

.muted {
    color: var(--text-muted);
    font-size: var(--text-sm);
}

.mono {
    font-family: var(--font-mono, monospace);
}

.feedback {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    padding: var(--space-4);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-subtle);
    background: color-mix(in srgb, var(--bg-card) 80%, transparent);
}

.feedback__title {
    margin: 0;
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.feedback__label {
    margin: 0 0 var(--space-1);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

.feedback__block ul {
    margin: 0;
    padding-left: var(--space-5);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.match-list,
.draft-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.match-item,
.draft-item {
    padding: var(--space-4);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-subtle);
    background: color-mix(in srgb, var(--bg-surface) 50%, transparent);
}

.match-item--deleted {
    opacity: var(--deleted-row-opacity, 0.7);
    background: var(--deleted-row-bg);
}

.match-item__head,
.draft-item__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-3);
    margin-bottom: var(--space-3);
}

.match-item__titles {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-2);
    min-width: 0;
}

.match-score {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    padding: 2px var(--space-2);
    border-radius: var(--radius-sm);
    background: color-mix(in srgb, var(--accent-primary) 14%, transparent);
    color: var(--accent-primary);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    font-family: var(--font-mono, monospace);
}

.match-title {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.match-company {
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.match-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    font-size: var(--text-sm);
    color: var(--accent-info);
    text-decoration: none;
    white-space: nowrap;
}

.match-link:hover {
    text-decoration: underline;
}

.draft-subject {
    flex: 1;
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.draft-actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
    margin-top: var(--space-3);
}

@media (max-width: 960px) {
    .dual-pane {
        grid-template-columns: 1fr;
    }
}
</style>
