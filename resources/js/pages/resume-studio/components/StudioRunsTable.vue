<script setup lang="ts">
/**
 * Studio runs server-side DataTable. View-only actions — no bulk selection
 * (runs are pipeline artifacts; soft-delete bulk targets job matches on Show).
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import ActionButton from '@/common/data-table/ActionButton.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDateShort } from '@/modules/cvs/helpers/formatDate';
import {
    modeLabel,
    modeTone,
    statusLabel,
    statusTone,
    stepLabel,
} from '@/modules/resume-studio/helpers/labels';
import type { StudioRun } from '@/modules/resume-studio/types';

const props = defineProps<{
    data: StudioRun[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
}>();

const emit = defineEmits<{
    page: [event: DataTablePageEvent];
}>();

function rowClass(row: StudioRun): string | undefined {
    return row.deleted_at ? 'deleted-row' : undefined;
}

function startedAt(row: StudioRun): string {
    return formatDateShort(row.started_at ?? row.created_at);
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
            paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            current-page-report-template="{first}–{last} of {totalRecords}"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No studio runs match your filters.</span>
                </div>
            </template>

            <Column header="Mode">
                <template #body="{ data }">
                    <StatusBadge
                        :tone="modeTone((data as StudioRun).mode)"
                        :label="modeLabel((data as StudioRun).mode)"
                    />
                </template>
            </Column>

            <Column header="CV">
                <template #body="{ data }">
                    <span class="cv-title">{{ (data as StudioRun).cv?.title ?? '—' }}</span>
                </template>
            </Column>

            <Column header="Status">
                <template #body="{ data }">
                    <StatusBadge
                        :tone="statusTone((data as StudioRun).status)"
                        :label="statusLabel((data as StudioRun).status)"
                    />
                </template>
            </Column>

            <Column header="Step">
                <template #body="{ data }">
                    <span class="muted">{{ stepLabel((data as StudioRun).step, true) }}</span>
                </template>
            </Column>

            <Column header="Started">
                <template #body="{ data }">
                    <span class="mono">{{ startedAt(data as StudioRun) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-20' }">
                <template #body="{ data }">
                    <div class="row-actions">
                        <PermissionGuard permission="VIEW_RESUME_STUDIOS">
                            <ActionButton
                                icon="pi pi-eye"
                                tone="view"
                                label="View studio run"
                                :href="`/resume-studio/runs/${(data as StudioRun).uuid}`"
                            />
                        </PermissionGuard>
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<style scoped>
.cv-title {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.muted {
    color: var(--text-muted);
    font-size: var(--text-sm);
}

.row-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    white-space: nowrap;
}

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

@media (prefers-reduced-motion: reduce) {
    .crud-table-wrap :deep(tbody tr) {
        transition: none;
    }
}
</style>
