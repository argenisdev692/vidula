<script setup lang="ts">
/**
 * Enrollments server-side DataTable. Parent owns query + router.get.
 * Active rows: View · Attendance · Edit · Delete; Suspended: View · Restore.
 */
import { Link } from '@inertiajs/vue3';
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDateShort } from '@/modules/enrollments/helpers/formatDate';
import type { Enrollment } from '@/modules/enrollments/types';

const props = defineProps<{
    data: Enrollment[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: Enrollment[];
}>();

const emit = defineEmits<{
    edit: [enrollment: Enrollment];
    delete: [enrollment: Enrollment];
    restore: [enrollment: Enrollment];
    attendance: [enrollment: Enrollment];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: Enrollment[]];
}>();

function rowClass(row: Enrollment): string | undefined {
    return row.deleted_at ? 'deleted-row' : undefined;
}

function statusTone(status: Enrollment['enrollment_status']): 'success' | 'danger' | 'muted' | 'primary' {
    if (status === 'active') {
        return 'success';
    }
    if (status === 'completed') {
        return 'primary';
    }
    if (status === 'dropped' || status === 'suspended') {
        return 'danger';
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
            @update:selection="(rows: Enrollment[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No enrollments match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column header="Student">
                <template #body="{ data }">
                    <span class="student-name">{{ (data as Enrollment).student?.name ?? '—' }}</span>
                </template>
            </Column>

            <Column header="Classroom">
                <template #body="{ data }">
                    <span>{{ (data as Enrollment).classroom?.product?.title ?? '—' }}</span>
                </template>
            </Column>

            <Column field="enrolled_at" header="Enrolled">
                <template #body="{ data }">
                    <span class="mono">{{ formatDateShort((data as Enrollment).enrolled_at) }}</span>
                </template>
            </Column>

            <Column field="enrollment_status" header="Status">
                <template #body="{ data }">
                    <StatusBadge
                        :tone="statusTone((data as Enrollment).enrollment_status)"
                        :label="(data as Enrollment).enrollment_status"
                    />
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-48' }">
                <template #body="{ data }">
                    <div class="actions-cell">
                        <PermissionGuard permission="VIEW_ENROLLMENTS">
                            <Link
                                :href="`/enrollments/${(data as Enrollment).uuid}`"
                                class="btn-crud-action btn-crud-action-view"
                                aria-label="View enrollment"
                                title="View"
                                v-tooltip.top="'View'"
                                prefetch
                            >
                                <i class="pi pi-eye" aria-hidden="true" />
                            </Link>
                        </PermissionGuard>

                        <template v-if="(data as Enrollment).deleted_at">
                            <PermissionGuard permission="RESTORE_ENROLLMENTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-restore"
                                    aria-label="Restore enrollment"
                                    title="Restore"
                                    v-tooltip.top="'Restore'"
                                    @click="emit('restore', data as Enrollment)"
                                >
                                    <i class="pi pi-check-circle" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard
                                v-if="(data as Enrollment).classroom?.uuid"
                                permission="VIEW_ANY_ENROLLMENTS"
                            >
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-attendance"
                                    aria-label="Attendance sheet"
                                    title="Attendance"
                                    v-tooltip.top="'Attendance'"
                                    @click="emit('attendance', data as Enrollment)"
                                >
                                    <i class="pi pi-calendar" aria-hidden="true" />
                                </button>
                            </PermissionGuard>

                            <PermissionGuard permission="UPDATE_ENROLLMENTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-edit"
                                    aria-label="Edit enrollment"
                                    title="Edit"
                                    v-tooltip.top="'Edit'"
                                    @click="emit('edit', data as Enrollment)"
                                >
                                    <i class="pi pi-pencil" aria-hidden="true" />
                                </button>
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_ENROLLMENTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-delete"
                                    aria-label="Suspend enrollment"
                                    title="Suspend"
                                    v-tooltip.top="'Suspend'"
                                    @click="emit('delete', data as Enrollment)"
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
.student-name {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

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
    text-decoration: none;
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

.btn-crud-action-view {
    color: var(--accent-info);
}

.btn-crud-action-view:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-info) 30%, transparent);
}

.btn-crud-action-attendance {
    color: var(--accent-secondary);
}

.btn-crud-action-attendance:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-secondary) 30%, transparent);
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
