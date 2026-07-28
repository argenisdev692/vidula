<script setup lang="ts">
/**
 * Students server-side DataTable. Parent owns query + router.get — never sorts /
 * filters / paginates in the browser. Active rows: View · Edit · Delete;
 * Suspended: View · Restore. Every action is permission-gated.
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import ActionButton from '@/common/data-table/ActionButton.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDateShort } from '@/modules/students/helpers/formatDate';
import type { Student, StudentLifecycleStatus } from '@/modules/students/types';

const props = defineProps<{
    data: Student[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: Student[];
}>();

const emit = defineEmits<{
    edit: [student: Student];
    delete: [student: Student];
    restore: [student: Student];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: Student[]];
}>();

function rowClass(row: Student): string | undefined {
    return row.deleted_at ? 'deleted-row' : undefined;
}

function lifecycleTone(status: StudentLifecycleStatus): 'success' | 'primary' | 'muted' {
    if (status === 'ACTIVE') {
        return 'success';
    }
    if (status === 'DRAFT') {
        return 'primary';
    }
    return 'muted';
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
            @update:selection="(rows: Student[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No students match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column field="name" header="Name">
                <template #body="{ data }">
                    <span class="student-name">{{ (data as Student).name }}</span>
                </template>
            </Column>

            <Column field="email" header="Email">
                <template #body="{ data }">
                    <span class="muted">{{ (data as Student).email || '—' }}</span>
                </template>
            </Column>

            <Column field="phone" header="Phone">
                <template #body="{ data }">
                    <span class="mono">{{ (data as Student).phone || '—' }}</span>
                </template>
            </Column>

            <Column header="Lifecycle">
                <template #body="{ data }">
                    <StatusBadge
                        :tone="lifecycleTone((data as Student).status)"
                        :label="(data as Student).status"
                    />
                </template>
            </Column>

            <Column header="Flag">
                <template #body="{ data }">
                    <StatusBadge
                        :tone="(data as Student).active ? 'success' : 'muted'"
                        :label="(data as Student).active ? 'Active' : 'Inactive'"
                    />
                </template>
            </Column>

            <Column field="created_at" header="Created">
                <template #body="{ data }">
                    <span class="mono">{{ formatDateShort((data as Student).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-32' }">
                <template #body="{ data }">
                    <div class="row-actions">
                        <PermissionGuard permission="VIEW_STUDENTS">
                            <ActionButton
                                icon="pi pi-eye"
                                tone="view"
                                label="View student"
                                :href="`/students/${(data as Student).uuid}`"
                            />
                        </PermissionGuard>

                        <template v-if="(data as Student).deleted_at">
                            <PermissionGuard permission="RESTORE_STUDENTS">
                                <ActionButton
                                    icon="pi pi-check-circle"
                                    tone="restore"
                                    label="Restore student"
                                    @click="emit('restore', data as Student)"
                                />
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard permission="UPDATE_STUDENTS">
                                <ActionButton
                                    icon="pi pi-pencil"
                                    tone="edit"
                                    label="Edit student"
                                    @click="emit('edit', data as Student)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_STUDENTS">
                                <ActionButton
                                    icon="pi pi-trash"
                                    tone="delete"
                                    label="Suspend student"
                                    @click="emit('delete', data as Student)"
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
.student-name {
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
</style>
