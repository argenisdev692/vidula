<script setup lang="ts">
/**
 * Social media server-side DataTable. Fed by Inertia partial reloads (the
 * parent owns the reactive query + `router.get`), so it never sorts / filters
 * / paginates in the browser. An ACTIVE row (deleted_at === null) shows
 * Edit · Delete; a SUSPENDED row shows Restore (never Edit). Every action is
 * permission-gated. Styling mirrors the Posts table (project's DataTable
 * reference for AI-generated content modules).
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDate } from '@/modules/social-media/helpers/formatDate';
import type { SocialMediaContent } from '@/modules/social-media/types';

const props = defineProps<{
    data: SocialMediaContent[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: SocialMediaContent[];
}>();

const emit = defineEmits<{
    edit: [item: SocialMediaContent];
    delete: [item: SocialMediaContent];
    restore: [item: SocialMediaContent];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: SocialMediaContent[]];
}>();

function creatorName(row: SocialMediaContent): string {
    const name = [row.creator?.first_name, row.creator?.last_name].filter(Boolean).join(' ').trim();
    return name || 'System';
}

const STATUS_TONE: Record<string, string> = {
    draft: 'status-pill--draft',
    generating: 'status-pill--generating',
    ready: 'status-pill--ready',
    needs_review: 'status-pill--review',
    published: 'status-pill--published',
    scheduled: 'status-pill--scheduled',
};

function statusLabel(status: string): string {
    return status.replace('_', ' ');
}

function scoreTone(score: number | null): string {
    if (score === null) {
        return 'score--muted';
    }
    if (score >= 75) {
        return 'score--good';
    }
    return score >= 50 ? 'score--warn' : 'score--bad';
}

function rowClass(row: SocialMediaContent): string | undefined {
    return row.deleted_at ? 'deleted-row' : undefined;
}
</script>

<template>
    <div class="crud-table-wrap">
        <DataTable
            :value="props.data"
            data-key="uuid"
            lazy
            paginator
            :rows="props.perPage"
            :total-records="props.total"
            :first="props.first"
            :loading="props.loading"
            :row-class="rowClass"
            :selection="props.selection"
            paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            current-page-report-template="{first}–{last} of {totalRecords}"
            @update:selection="(rows: SocialMediaContent[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No content packages match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column field="topic" header="Topic">
                <template #body="{ data }">
                    <div class="title-cell">
                        <span class="topic-title">{{ (data as SocialMediaContent).topic }}</span>
                        <i
                            v-if="(data as SocialMediaContent).quality_warning"
                            class="pi pi-exclamation-triangle warning-badge"
                            v-tooltip.top="'Quality warning — review before publishing'"
                        />
                    </div>
                </template>
            </Column>

            <Column header="Provider">
                <template #body="{ data }">
                    <span class="muted">{{ (data as SocialMediaContent).provider }}</span>
                </template>
            </Column>

            <Column header="Funnel">
                <template #body="{ data }">
                    <span class="funnel-chip">{{ (data as SocialMediaContent).funnel_stage.toUpperCase() }}</span>
                </template>
            </Column>

            <Column header="Author">
                <template #body="{ data }">
                    <span class="muted">{{ creatorName(data as SocialMediaContent) }}</span>
                </template>
            </Column>

            <Column header="Status">
                <template #body="{ data }">
                    <span class="status-pill" :class="STATUS_TONE[(data as SocialMediaContent).status]">
                        {{ statusLabel((data as SocialMediaContent).status) }}
                    </span>
                </template>
            </Column>

            <Column header="Score">
                <template #body="{ data }">
                    <span class="score" :class="scoreTone((data as SocialMediaContent).overall_score_avg)">
                        {{ (data as SocialMediaContent).overall_score_avg ?? '—' }}
                    </span>
                </template>
            </Column>

            <Column field="created_at" header="Created">
                <template #body="{ data }">
                    <span class="mono">{{ formatDate((data as SocialMediaContent).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-32' }">
                <template #body="{ data }">
                    <div class="actions-cell">
                        <template v-if="(data as SocialMediaContent).deleted_at">
                            <PermissionGuard permission="RESTORE_SOCIAL_MEDIA">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-restore"
                                    aria-label="Restore content"
                                    v-tooltip.top="'Restore'"
                                    @click="emit('restore', data as SocialMediaContent)"
                                >
                                    <i class="pi pi-check-circle" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard permission="VIEW_SOCIAL_MEDIA">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-edit"
                                    aria-label="Edit content"
                                    v-tooltip.top="'Edit'"
                                    @click="emit('edit', data as SocialMediaContent)"
                                >
                                    <i class="pi pi-pencil" aria-hidden="true" />
                                </button>
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_SOCIAL_MEDIA">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-delete"
                                    aria-label="Suspend content"
                                    v-tooltip.top="'Suspend'"
                                    @click="emit('delete', data as SocialMediaContent)"
                                >
                                    <i class="pi pi-trash" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                        </template>
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<style scoped>
.title-cell {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
}

.topic-title {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.warning-badge {
    color: var(--accent-warning);
    font-size: 0.85rem;
}

.mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.muted {
    color: var(--text-muted);
    font-size: var(--text-sm);
    text-transform: capitalize;
}

.funnel-chip {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    color: var(--accent-secondary);
    background: color-mix(in srgb, var(--accent-secondary) 12%, transparent);
    padding: 2px 8px;
    border-radius: var(--radius-sm);
}

.status-pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: var(--radius-full, 99px);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: capitalize;
}

.status-pill--draft {
    color: var(--text-muted);
    background: color-mix(in srgb, var(--text-muted) 14%, transparent);
}

.status-pill--generating {
    color: var(--accent-info);
    background: color-mix(in srgb, var(--accent-info) 14%, transparent);
}

.status-pill--ready {
    color: var(--accent-success);
    background: color-mix(in srgb, var(--accent-success) 14%, transparent);
}

.status-pill--review {
    color: var(--accent-warning);
    background: color-mix(in srgb, var(--accent-warning) 14%, transparent);
}

.status-pill--published {
    color: var(--accent-primary);
    background: color-mix(in srgb, var(--accent-primary) 14%, transparent);
}

.status-pill--scheduled {
    color: var(--accent-info);
    background: color-mix(in srgb, var(--accent-info) 14%, transparent);
}

.score {
    font-family: var(--font-mono, monospace);
    font-weight: var(--font-semibold);
}

.score--good {
    color: var(--accent-success);
}

.score--warn {
    color: var(--accent-warning);
}

.score--bad {
    color: var(--accent-error);
}

.score--muted {
    color: var(--text-muted);
}

/* ── Minimalist transparent CRUD table (matches the Posts/Activity Log reference) ── */
.crud-table-wrap :deep(table) {
    background: transparent;
}

.crud-table-wrap :deep(thead th) {
    background: transparent;
    border-bottom: 1px solid var(--border-default);
    color: var(--text-secondary);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: center;
}

.crud-table-wrap :deep(thead th > div) {
    justify-content: center;
}

.crud-table-wrap :deep(tbody tr) {
    background: transparent;
    transition: background var(--transition);
}

.crud-table-wrap :deep(tbody tr:hover) {
    background: color-mix(in srgb, var(--bg-overlay) 40%, transparent);
}

.crud-table-wrap :deep(tbody td) {
    background: transparent;
    border-bottom: 1px solid var(--border-subtle);
    color: var(--text-primary);
    text-align: center;
    vertical-align: middle;
}

.crud-table-wrap :deep(tbody tr.deleted-row) {
    background: var(--deleted-row-bg);
    opacity: var(--deleted-row-opacity, 0.7);
}

.crud-table-wrap :deep(tbody tr.deleted-row td) {
    border-bottom-color: var(--deleted-row-border);
}

.crud-table-wrap :deep([data-pc-name='paginator']),
.crud-table-wrap :deep([data-pc-name='pcpaginator']) {
    background: transparent;
}

.actions-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    white-space: nowrap;
}

.btn-crud-action {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-subtle);
    background: color-mix(in srgb, var(--bg-elevated) 50%, transparent);
    color: var(--text-secondary);
    cursor: pointer;
    transition: transform var(--transition), border-color var(--transition), box-shadow var(--transition);
    overflow: hidden;
}

.btn-crud-action::after {
    content: '';
    position: absolute;
    inset: 0;
    background: currentColor;
    opacity: 0;
    border-radius: inherit;
    transition: opacity var(--transition);
}

.btn-crud-action:hover {
    transform: scale(1.15);
    border-color: currentColor;
}

.btn-crud-action:hover::after {
    opacity: 0.1;
}

.btn-crud-action:active {
    transform: scale(0.95);
}

.btn-crud-action:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.btn-crud-action .pi {
    position: relative;
    z-index: 1;
    font-size: 0.8rem;
}

.btn-crud-action-edit {
    color: var(--accent-primary);
}

.btn-crud-action-edit:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-primary) 30%, transparent);
}

.btn-crud-action-delete {
    color: var(--accent-error);
}

.btn-crud-action-delete:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-error) 30%, transparent);
}

.btn-crud-action-restore {
    color: var(--accent-success);
}

.btn-crud-action-restore:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-success) 30%, transparent);
}

.table-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-10) var(--space-6);
    color: var(--text-muted);
}

.table-empty .pi {
    font-size: var(--text-2xl);
}
</style>
