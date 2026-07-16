<script setup lang="ts">
/**
 * Meetings server-side DataTable. Fed by Inertia partial reloads (the parent
 * owns the reactive query + `router.get`), mirrors `AppointmentsTable.vue`
 * exactly (transparent-grid + action-pill styling, the project's DataTable
 * reference). A row is ACTIVE when `deleted_at === null` and SUSPENDED
 * otherwise; Cancel is only offered on a still-`Scheduled` active row.
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import Tag from '@/volt/Tag.vue';
import ActionButton from '@/common/data-table/ActionButton.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDateTime } from '@/modules/meeting/helpers/formatDate';
import type { Meeting } from '@/modules/meeting/types';

const props = defineProps<{
    data: Meeting[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: Meeting[];
}>();

const emit = defineEmits<{
    edit: [meeting: Meeting];
    cancel: [meeting: Meeting];
    delete: [meeting: Meeting];
    restore: [meeting: Meeting];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: Meeting[]];
}>();

function organizerName(row: Meeting): string {
    if (!row.organizer) {
        return '—';
    }
    return `${row.organizer.first_name} ${row.organizer.last_name}`.trim() || '—';
}

function statusSeverity(status: Meeting['status']): 'success' | 'danger' {
    return status === 'Cancelled' ? 'danger' : 'success';
}

function rowClass(row: Meeting): string | undefined {
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
            @update:selection="(rows: Meeting[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-calendar-times" aria-hidden="true" />
                    <span>No meetings match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column field="title" header="Title">
                <template #body="{ data }">
                    <span class="mono">{{ (data as Meeting).title }}</span>
                </template>
            </Column>

            <Column header="Organizer">
                <template #body="{ data }">
                    <span class="mono">{{ organizerName(data as Meeting) }}</span>
                </template>
            </Column>

            <Column field="attendees_count" header="Attendees">
                <template #body="{ data }">
                    <span class="mono">{{ (data as Meeting).attendees_count }}</span>
                </template>
            </Column>

            <Column header="Status">
                <template #body="{ data }">
                    <Tag :value="(data as Meeting).status" :severity="statusSeverity((data as Meeting).status)" />
                </template>
            </Column>

            <Column field="starts_at" header="Starts">
                <template #body="{ data }">
                    <span class="mono">{{ formatDateTime((data as Meeting).starts_at) }}</span>
                </template>
            </Column>

            <Column field="ends_at" header="Ends">
                <template #body="{ data }">
                    <span class="mono">{{ formatDateTime((data as Meeting).ends_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-40' }">
                <template #body="{ data }">
                    <div class="actions-cell">
                        <PermissionGuard permission="VIEW_MEETINGS">
                            <ActionButton
                                icon="pi pi-eye"
                                tone="view"
                                label="View meeting"
                                :href="`/meetings/${(data as Meeting).uuid}`"
                            />
                        </PermissionGuard>

                        <template v-if="(data as Meeting).deleted_at">
                            <PermissionGuard permission="RESTORE_MEETINGS">
                                <ActionButton
                                    icon="pi pi-check-circle"
                                    tone="restore"
                                    label="Restore meeting"
                                    @click="emit('restore', data as Meeting)"
                                />
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard v-if="(data as Meeting).status === 'Scheduled'" permission="UPDATE_MEETINGS">
                                <ActionButton
                                    icon="pi pi-ban"
                                    tone="warning"
                                    label="Cancel meeting"
                                    @click="emit('cancel', data as Meeting)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="UPDATE_MEETINGS">
                                <ActionButton
                                    icon="pi pi-pencil"
                                    tone="edit"
                                    label="Edit meeting"
                                    @click="emit('edit', data as Meeting)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_MEETINGS">
                                <ActionButton
                                    icon="pi pi-trash"
                                    tone="delete"
                                    label="Delete meeting"
                                    @click="emit('delete', data as Meeting)"
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
.mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-sm);
    color: var(--text-secondary);
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
