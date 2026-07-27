<script setup lang="ts">
/**
 * Appointments server-side DataTable. Fed by Inertia partial reloads (the
 * parent owns the reactive query + `router.get`), so it never sorts / filters
 * / paginates in the browser. Rows are homogeneous per the `status` filter: an
 * ACTIVE row (deleted_at === null) shows View · Mark-read (unread only) ·
 * Edit · Delete; a SUSPENDED row shows View · Restore (never Edit). Every
 * action is permission-gated.
 *
 * Transparent-grid + action-pill styling mirrors the Users / Contact & Support
 * tables (the project's DataTable reference).
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import Tag from '@/volt/Tag.vue';
import ActionButton from '@/common/data-table/ActionButton.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDate, formatDateTime } from '@/modules/appointments/helpers/formatDate';
import { appointmentDisplayName } from '@/modules/appointments/helpers/displayName';
import { CLIENT_TYPE_LABEL, MEETING_STATUS_META, STATUS_LEAD_META } from '@/modules/appointments/helpers/statusMeta';
import type { Appointment } from '@/modules/appointments/types';

const props = defineProps<{
    data: Appointment[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: Appointment[];
}>();

const emit = defineEmits<{
    edit: [appointment: Appointment];
    delete: [appointment: Appointment];
    restore: [appointment: Appointment];
    markRead: [appointment: Appointment];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: Appointment[]];
}>();

function rowClass(row: Appointment): string | undefined {
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
            @update:selection="(rows: Appointment[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-calendar-times" aria-hidden="true" />
                    <span>No leads match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column field="first_name" header="Lead">
                <template #body="{ data }">
                    <div class="lead-name">
                        <i class="pi pi-user" aria-hidden="true" />
                        <span class="lead-name__block">
                            <span class="lead-name__text">{{ appointmentDisplayName(data as Appointment) }}</span>
                            <span class="lead-name__handle">
                                {{ CLIENT_TYPE_LABEL[(data as Appointment).client_type] }}
                                <template v-if="(data as Appointment).company_name">
                                    · {{ (data as Appointment).company_name }}
                                </template>
                            </span>
                        </span>
                    </div>
                </template>
            </Column>

            <Column field="email" header="Email">
                <template #body="{ data }">
                    <span class="mono">{{ (data as Appointment).email }}</span>
                </template>
            </Column>

            <Column field="phone" header="Phone">
                <template #body="{ data }">
                    <span class="mono">{{ (data as Appointment).phone ?? '—' }}</span>
                </template>
            </Column>

            <Column header="Lead status">
                <template #body="{ data }">
                    <Tag
                        v-if="(data as Appointment).status_lead"
                        :value="STATUS_LEAD_META[(data as Appointment).status_lead!].label"
                        :severity="STATUS_LEAD_META[(data as Appointment).status_lead!].severity"
                    />
                    <span v-else class="mono">—</span>
                </template>
            </Column>

            <Column header="Meeting">
                <template #body="{ data }">
                    <Tag
                        v-if="(data as Appointment).meeting_status"
                        :value="MEETING_STATUS_META[(data as Appointment).meeting_status!].label"
                        :severity="MEETING_STATUS_META[(data as Appointment).meeting_status!].severity"
                    />
                    <span v-else class="mono">—</span>
                </template>
            </Column>

            <Column field="scheduled_at" header="Scheduled">
                <template #body="{ data }">
                    <span class="mono">{{ formatDateTime((data as Appointment).scheduled_at) }}</span>
                </template>
            </Column>

            <Column header="Read">
                <template #body="{ data }">
                    <span class="badge" :class="(data as Appointment).readed ? 'badge--read' : 'badge--unread'">
                        {{ (data as Appointment).readed ? 'Read' : 'Unread' }}
                    </span>
                </template>
            </Column>

            <Column field="created_at" header="Created">
                <template #body="{ data }">
                    <span class="mono">{{ formatDate((data as Appointment).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-40' }">
                <template #body="{ data }">
                    <div class="actions-cell">
                        <PermissionGuard permission="VIEW_APPOINTMENTS">
                            <ActionButton
                                icon="pi pi-eye"
                                tone="view"
                                label="View lead"
                                :href="`/appointments/${(data as Appointment).uuid}`"
                            />
                        </PermissionGuard>

                        <template v-if="(data as Appointment).deleted_at">
                            <PermissionGuard permission="RESTORE_APPOINTMENTS">
                                <ActionButton
                                    icon="pi pi-check-circle"
                                    tone="restore"
                                    label="Restore lead"
                                    @click="emit('restore', data as Appointment)"
                                />
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard v-if="!(data as Appointment).readed" permission="UPDATE_APPOINTMENTS">
                                <ActionButton
                                    icon="pi pi-envelope-open"
                                    tone="warning"
                                    label="Mark as read"
                                    @click="emit('markRead', data as Appointment)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="UPDATE_APPOINTMENTS">
                                <ActionButton
                                    icon="pi pi-pencil"
                                    tone="edit"
                                    label="Edit lead"
                                    @click="emit('edit', data as Appointment)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_APPOINTMENTS">
                                <ActionButton
                                    icon="pi pi-trash"
                                    tone="delete"
                                    label="Suspend lead"
                                    @click="emit('delete', data as Appointment)"
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
.lead-name {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
}

.lead-name .pi {
    color: var(--accent-primary);
    font-size: 0.85rem;
}

.lead-name__block {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    line-height: 1.2;
}

.lead-name__text {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.lead-name__handle {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-3);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
}

.badge--read {
    background: color-mix(in srgb, var(--text-muted) 16%, transparent);
    color: var(--text-secondary);
}

.badge--unread {
    background: color-mix(in srgb, var(--accent-primary) 18%, transparent);
    color: var(--accent-primary);
    font-weight: var(--font-semibold);
}

/* ── Minimalist transparent CRUD table (matches the reference tables) ── */
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

/* ── Action icons (bordered colour pill + glow) ── */
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
