<script setup lang="ts">
/**
 * Campaigns server-side DataTable. Fed by Inertia partial reloads (the
 * parent owns the reactive query + `router.get`), so it never sorts / filters
 * / paginates in the browser. An ACTIVE row (deleted_at === null) shows
 * View · Edit · Delete; a SUSPENDED row shows View · Restore (never Edit).
 * Every action is permission-gated. Action pills use shared
 * {@see ActionButton} (FRONTEND §7 / §11 — no hover scale > 1.04).
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import ActionButton from '@/common/data-table/ActionButton.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDateShort } from '@/modules/campaigns/helpers/formatDate';
import type { Campaign } from '@/modules/campaigns/types';

const props = defineProps<{
    data: Campaign[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: Campaign[];
}>();

const emit = defineEmits<{
    edit: [item: Campaign];
    delete: [item: Campaign];
    restore: [item: Campaign];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: Campaign[]];
}>();

function creatorName(row: Campaign): string {
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

function rowClass(row: Campaign): string | undefined {
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
            @update:selection="(rows: Campaign[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No campaigns match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column field="topic" header="Topic">
                <template #body="{ data }">
                    <div class="title-cell">
                        <span class="topic-title">{{ (data as Campaign).topic }}</span>
                        <i
                            v-if="(data as Campaign).quality_warning"
                            class="pi pi-exclamation-triangle warning-badge"
                            v-tooltip.top="'Quality warning — review before publishing'"
                        />
                    </div>
                </template>
            </Column>

            <Column header="Platform">
                <template #body="{ data }">
                    <span class="muted">{{ (data as Campaign).platform }} · {{ (data as Campaign).ad_format.replace('_', ' ') }}</span>
                </template>
            </Column>

            <Column header="Funnel">
                <template #body="{ data }">
                    <span class="funnel-chip">{{ (data as Campaign).funnel_stage.toUpperCase() }}</span>
                </template>
            </Column>

            <Column header="Author">
                <template #body="{ data }">
                    <span class="muted">{{ creatorName(data as Campaign) }}</span>
                </template>
            </Column>

            <Column header="Status">
                <template #body="{ data }">
                    <span class="status-pill" :class="STATUS_TONE[(data as Campaign).status]">
                        {{ statusLabel((data as Campaign).status) }}
                    </span>
                </template>
            </Column>

            <Column header="Score">
                <template #body="{ data }">
                    <span class="score" :class="scoreTone((data as Campaign).overall_score_avg)">
                        {{ (data as Campaign).overall_score_avg ?? '—' }}
                    </span>
                </template>
            </Column>

            <Column field="created_at" header="Created">
                <template #body="{ data }">
                    <span class="mono">{{ formatDateShort((data as Campaign).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-36' }">
                <template #body="{ data }">
                    <div class="actions-cell">
                        <PermissionGuard permission="VIEW_CAMPAIGNS">
                            <ActionButton
                                icon="pi pi-eye"
                                tone="view"
                                label="View campaign"
                                :href="`/campaigns/${(data as Campaign).uuid}/edit`"
                            />
                        </PermissionGuard>

                        <template v-if="(data as Campaign).deleted_at">
                            <PermissionGuard permission="RESTORE_CAMPAIGNS">
                                <ActionButton
                                    icon="pi pi-check-circle"
                                    tone="restore"
                                    label="Restore campaign"
                                    @click="emit('restore', data as Campaign)"
                                />
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard permission="UPDATE_CAMPAIGNS">
                                <ActionButton
                                    icon="pi pi-pencil"
                                    tone="edit"
                                    label="Edit campaign"
                                    @click="emit('edit', data as Campaign)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_CAMPAIGNS">
                                <ActionButton
                                    icon="pi pi-trash"
                                    tone="delete"
                                    label="Suspend campaign"
                                    @click="emit('delete', data as Campaign)"
                                />
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

/* ── Minimalist transparent CRUD table (matches the Social Media/Posts reference) ── */
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
