<script setup lang="ts">
/**
 * CV detail — read-only view from GET /cvs/{uuid} (VIEW_CVS).
 * Includes signed download URL and optional raw_text preview (MD).
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDateShort } from '@/modules/cvs/helpers/formatDate';
import type { Cv } from '@/modules/cvs/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    cv: Cv;
}>();

const isSuspended = computed<boolean>(() => props.cv.deleted_at !== null);
const title = computed<string>(() => props.cv.title || 'Untitled CV');

const nicheTone = computed<'success' | 'primary' | 'muted'>(() =>
    props.cv.niche === 'fullstack' ? 'primary' : 'muted',
);

const nicheLabel = computed<string>(() =>
    props.cv.niche === 'fullstack' ? 'Fullstack' : 'Other',
);

const ownerName = computed<string>(() => {
    const label = [props.cv.user?.first_name, props.cv.user?.last_name].filter(Boolean).join(' ').trim();
    return label || 'System';
});
</script>

<template>
    <Head :title="title" />

    <DetailCard
        header-title="CV"
        header-subtitle="Uploaded resume detail"
        permission="VIEW_CVS"
        fallback-text="You don't have permission to view this CV."
        back-href="/cvs"
        back-label="Back to CVs"
        :title="title"
    >
        <template #badges>
            <StatusBadge :tone="nicheTone" :label="nicheLabel" />
            <StatusBadge
                v-if="cv.is_primary"
                tone="primary"
                label="Primary"
            />
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Active'"
            />
        </template>

        <dl class="facts">
            <div class="fact">
                <dt>File type</dt>
                <dd class="mono">{{ cv.file_type.toUpperCase() }}</dd>
            </div>
            <div class="fact">
                <dt>Filename</dt>
                <dd>{{ cv.original_filename }}</dd>
            </div>
            <div class="fact">
                <dt>Owner</dt>
                <dd>{{ ownerName }}</dd>
            </div>
            <div class="fact">
                <dt>Created</dt>
                <dd class="mono">{{ formatDateShort(cv.created_at) }}</dd>
            </div>
            <div class="fact fact--wide">
                <dt>Download</dt>
                <dd>
                    <a
                        v-if="cv.download_url"
                        :href="cv.download_url"
                        class="download-link"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Download signed CV file"
                    >
                        <i class="pi pi-download" aria-hidden="true" />
                        Download signed file
                    </a>
                    <span v-else class="muted">—</span>
                </dd>
            </div>
            <div v-if="cv.raw_text" class="fact fact--wide">
                <dt>Extracted text</dt>
                <dd>
                    <pre class="raw-text">{{ cv.raw_text }}</pre>
                </dd>
            </div>
            <div v-else-if="cv.file_type === 'pdf'" class="fact fact--wide">
                <dt>Extracted text</dt>
                <dd class="muted">PDF text extraction ships in Module 2 (ATS / RAG).</dd>
            </div>
        </dl>
    </DetailCard>
</template>

<style scoped>
.facts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-5);
    margin: 0;
}

.fact {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.fact--wide {
    grid-column: 1 / -1;
}

.fact dt {
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

.fact dd {
    margin: 0;
    color: var(--text-primary);
    font-size: var(--text-sm);
}

.mono {
    font-family: var(--font-mono, monospace);
}

.muted {
    color: var(--text-muted);
}

.download-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-sm);
    border: 1px solid color-mix(in srgb, var(--accent-primary) 35%, transparent);
    background: color-mix(in srgb, var(--accent-primary) 8%, transparent);
    color: var(--accent-primary);
    text-decoration: none;
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    transition: background var(--transition), border-color var(--transition);
}

.download-link:hover {
    background: color-mix(in srgb, var(--accent-primary) 14%, transparent);
    border-color: var(--accent-primary);
    text-decoration: none;
}

.download-link:focus-visible {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
}

.raw-text {
    margin: 0;
    padding: var(--space-4);
    max-height: 24rem;
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

@media (max-width: 640px) {
    .facts {
        grid-template-columns: 1fr;
    }
}
</style>
