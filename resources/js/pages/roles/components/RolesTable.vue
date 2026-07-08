<script setup lang="ts">
/**
 * Roles server-side DataTable. Fed by Inertia partial reloads (the parent owns the
 * reactive query + `router.get`), so it never sorts / filters / paginates in the
 * browser. Rows are homogeneous per the `status` filter: an ACTIVE row
 * (deleted_at === null) shows View · Edit · Delete; a SUSPENDED row shows
 * View · Restore (never Edit). Every action is permission-gated.
 *
 * Protected system roles (name ∈ protectedRoles) are locked: they can be viewed
 * and their permissions edited, but never deleted — the Delete action is replaced
 * by a lock badge, mirroring the backend invariant.
 *
 * Transparent-grid + action-pill styling mirrors the Blog Categories / Activity
 * Log tables (the project's DataTable reference).
 */
import { Link } from '@inertiajs/vue3';
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDate } from '@/modules/authorization/helpers/formatDate';
import type { Role } from '@/modules/authorization/types';

const props = defineProps<{
    data: Role[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: Role[];
    protectedRoles: string[];
}>();

const emit = defineEmits<{
    edit: [role: Role];
    delete: [role: Role];
    restore: [role: Role];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: Role[]];
}>();

function isProtected(row: Role): boolean {
    return props.protectedRoles.includes(row.name);
}

function rowClass(row: Role): string | undefined {
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
            @update:selection="(rows: Role[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No roles match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column field="name" header="Role">
                <template #body="{ data }">
                    <div class="role-name">
                        <i class="pi pi-shield" aria-hidden="true" />
                        <span class="role-name__text">{{ (data as Role).name }}</span>
                        <span v-if="isProtected(data as Role)" class="chip chip--system">System</span>
                    </div>
                </template>
            </Column>

            <Column header="Permissions">
                <template #body="{ data }">
                    <span class="chip chip--count">
                        {{ (data as Role).permissions_count }}
                        {{ (data as Role).permissions_count === 1 ? 'permission' : 'permissions' }}
                    </span>
                </template>
            </Column>

            <Column field="guard_name" header="Guard">
                <template #body="{ data }">
                    <span class="mono">{{ (data as Role).guard_name }}</span>
                </template>
            </Column>

            <Column field="created_at" header="Created">
                <template #body="{ data }">
                    <span class="mono">{{ formatDate((data as Role).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-32' }">
                <template #body="{ data }">
                    <div class="actions-cell">
                        <PermissionGuard permission="VIEW_ROLES">
                            <Link
                                :href="`/roles/${(data as Role).uuid}`"
                                class="btn-crud-action btn-crud-action-view"
                                aria-label="View role"
                                title="View"
                                v-tooltip.top="'View'"
                            >
                                <i class="pi pi-eye" aria-hidden="true" />
                            </Link>
                        </PermissionGuard>

                        <template v-if="(data as Role).deleted_at">
                            <PermissionGuard permission="RESTORE_ROLES">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-restore"
                                    aria-label="Restore role"
                                    title="Restore"
                                    v-tooltip.top="'Restore'"
                                    @click="emit('restore', data as Role)"
                                >
                                    <i class="pi pi-check-circle" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard permission="UPDATE_ROLES">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-edit"
                                    aria-label="Edit role"
                                    title="Edit"
                                    v-tooltip.top="'Edit'"
                                    @click="emit('edit', data as Role)"
                                >
                                    <i class="pi pi-pencil" aria-hidden="true" />
                                </button>
                            </PermissionGuard>

                            <PermissionGuard v-if="!isProtected(data as Role)" permission="DELETE_ROLES">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-delete"
                                    aria-label="Suspend role"
                                    title="Suspend"
                                    v-tooltip.top="'Suspend'"
                                    @click="emit('delete', data as Role)"
                                >
                                    <i class="pi pi-trash" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                            <span
                                v-else
                                class="btn-crud-action btn-crud-action-locked"
                                aria-label="System role cannot be suspended"
                                title="Protected system role"
                                v-tooltip.top="'Protected system role'"
                            >
                                <i class="pi pi-lock" aria-hidden="true" />
                            </span>
                        </template>
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<style scoped>
.role-name {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
}

.role-name .pi {
    color: var(--accent-primary);
    font-size: 0.85rem;
}

.role-name__text {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.chip {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-2);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    line-height: 1.4;
}

.chip--system {
    background: color-mix(in srgb, var(--accent-warning) 18%, transparent);
    color: var(--accent-warning);
}

.chip--count {
    background: color-mix(in srgb, var(--accent-primary) 14%, transparent);
    color: var(--accent-primary);
}

.mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-sm);
    color: var(--text-secondary);
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

.btn-crud-action-view {
    color: var(--accent-info);
}

.btn-crud-action-view:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-info) 30%, transparent);
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

.btn-crud-action-locked {
    color: var(--text-disabled);
    cursor: not-allowed;
}

.btn-crud-action-locked:hover {
    transform: none;
    border-color: var(--border-subtle);
}

.btn-crud-action-locked:hover::after {
    opacity: 0;
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
