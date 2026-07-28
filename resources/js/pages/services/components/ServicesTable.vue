<script setup lang="ts">
/**
 * Services server-side DataTable. Fed by Inertia partial reloads (the parent
 * owns the reactive query + `router.get`), so it never sorts / filters /
 * paginates in the browser. Rows are homogeneous per the `status` filter: an
 * ACTIVE row (deleted_at === null) shows View · Edit · Delete; a SUSPENDED row
 * shows View · Restore (never Edit). Every action is permission-gated.
 *
 * `is_active` (the public-catalog visibility flag) is unrelated to the soft-delete
 * status above and rendered as its own badge column.
 *
 * Action pills use shared {@see ActionButton} (FRONTEND §7 / §11 — no hover
 * scale > 1.04; tokens only).
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import ActionButton from '@/common/data-table/ActionButton.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDateShort } from '@/modules/services/helpers/formatDate';
import type { Service } from '@/modules/services/types';

const props = defineProps<{
    data: Service[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: Service[];
}>();

const emit = defineEmits<{
    edit: [service: Service];
    delete: [service: Service];
    restore: [service: Service];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: Service[]];
}>();

function authorName(row: Service): string {
    const name = [row.user?.first_name, row.user?.last_name].filter(Boolean).join(' ').trim();
    return name || 'System';
}

function rowClass(row: Service): string | undefined {
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
            @update:selection="(rows: Service[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No services match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column field="name" header="Name">
                <template #body="{ data }">
                    <span class="svc-name">{{ (data as Service).name }}</span>
                </template>
            </Column>

            <Column field="slug" header="Slug">
                <template #body="{ data }">
                    <span class="mono">{{ (data as Service).slug }}</span>
                </template>
            </Column>

            <Column field="description" header="Description">
                <template #body="{ data }">
                    <span class="svc-desc">{{ (data as Service).description || '—' }}</span>
                </template>
            </Column>

            <Column header="Catalog">
                <template #body="{ data }">
                    <StatusBadge
                        :tone="(data as Service).is_active ? 'success' : 'muted'"
                        :label="(data as Service).is_active ? 'Active' : 'Inactive'"
                    />
                </template>
            </Column>

            <Column field="sort_order" header="Order">
                <template #body="{ data }">
                    <span class="mono">{{ (data as Service).sort_order }}</span>
                </template>
            </Column>

            <Column header="Author">
                <template #body="{ data }">
                    <span class="muted">{{ authorName(data as Service) }}</span>
                </template>
            </Column>

            <Column field="created_at" header="Created">
                <template #body="{ data }">
                    <span class="mono">{{ formatDateShort((data as Service).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-32' }">
                <template #body="{ data }">
                    <div class="row-actions">
                        <PermissionGuard permission="VIEW_SERVICES">
                            <ActionButton
                                icon="pi pi-eye"
                                tone="view"
                                label="View service"
                                :href="`/services/${(data as Service).uuid}`"
                            />
                        </PermissionGuard>

                        <template v-if="(data as Service).deleted_at">
                            <PermissionGuard permission="RESTORE_SERVICES">
                                <ActionButton
                                    icon="pi pi-check-circle"
                                    tone="restore"
                                    label="Restore service"
                                    @click="emit('restore', data as Service)"
                                />
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard permission="UPDATE_SERVICES">
                                <ActionButton
                                    icon="pi pi-pencil"
                                    tone="edit"
                                    label="Edit service"
                                    @click="emit('edit', data as Service)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_SERVICES">
                                <ActionButton
                                    icon="pi pi-trash"
                                    tone="delete"
                                    label="Suspend service"
                                    @click="emit('delete', data as Service)"
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
.svc-name {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.svc-desc {
    display: inline-block;
    max-width: 20rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--text-secondary);
    font-size: var(--text-sm);
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
