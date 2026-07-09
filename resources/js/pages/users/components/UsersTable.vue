<script setup lang="ts">
/**
 * Users server-side DataTable. Fed by Inertia partial reloads (the parent owns the
 * reactive query + `router.get`), so it never sorts / filters / paginates in the
 * browser. Rows are homogeneous per the `status` filter: a non-suspended row
 * (deleted_at === null) shows View · Edit · Delete — plus Resend when it is still
 * PENDING — while a SUSPENDED row shows View · Restore (never Edit).
 * Every action is permission-gated.
 *
 * Transparent-grid + action-pill styling mirrors the Roles / Blog Categories
 * tables (the project's DataTable reference).
 */
import { Link } from '@inertiajs/vue3';
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDate } from '@/modules/users/helpers/formatDate';
import { resolveUserStatus, USER_STATUS_META } from '@/modules/users/helpers/userStatus';
import type { User } from '@/modules/users/types';

const props = defineProps<{
    data: User[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: User[];
}>();

const emit = defineEmits<{
    edit: [user: User];
    delete: [user: User];
    restore: [user: User];
    resend: [user: User];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: User[]];
}>();

function fullName(row: User): string {
    return [row.first_name, row.last_name].filter(Boolean).join(' ').trim() || '—';
}

function isPending(row: User): boolean {
    return resolveUserStatus(row) === 'pending';
}

function rowClass(row: User): string | undefined {
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
            @update:selection="(rows: User[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No users match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column field="first_name" header="User">
                <template #body="{ data }">
                    <div class="user-name">
                        <i class="pi pi-user" aria-hidden="true" />
                        <span class="user-name__block">
                            <span class="user-name__text">{{ fullName(data as User) }}</span>
                            <span v-if="(data as User).username" class="user-name__handle">
                                @{{ (data as User).username }}
                            </span>
                        </span>
                    </div>
                </template>
            </Column>

            <Column field="email" header="Email">
                <template #body="{ data }">
                    <span class="mono">{{ (data as User).email }}</span>
                </template>
            </Column>

            <Column field="phone" header="Phone">
                <template #body="{ data }">
                    <span class="mono">{{ (data as User).phone ?? '—' }}</span>
                </template>
            </Column>

            <Column header="Roles">
                <template #body="{ data }">
                    <span v-if="(data as User).roles?.length" class="role-tags">
                        <span v-for="role in (data as User).roles" :key="role.id" class="role-tag">
                            {{ role.name }}
                        </span>
                    </span>
                    <span v-else class="mono">—</span>
                </template>
            </Column>

            <Column header="Status">
                <template #body="{ data }">
                    <span class="badge" :class="USER_STATUS_META[resolveUserStatus(data as User)].className">
                        {{ USER_STATUS_META[resolveUserStatus(data as User)].label }}
                    </span>
                </template>
            </Column>

            <Column field="created_at" header="Created">
                <template #body="{ data }">
                    <span class="mono">{{ formatDate((data as User).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-40' }">
                <template #body="{ data }">
                    <div class="actions-cell">
                        <PermissionGuard permission="VIEW_USERS">
                            <Link
                                :href="`/users/${(data as User).uuid}`"
                                class="btn-crud-action btn-crud-action-view"
                                aria-label="View user"
                                title="View"
                                v-tooltip.top="'View'"
                            >
                                <i class="pi pi-eye" aria-hidden="true" />
                            </Link>
                        </PermissionGuard>

                        <PermissionGuard permission="ASSIGN_PERMISSIONS_USERS">
                            <Link
                                :href="`/users/${(data as User).uuid}/permissions`"
                                class="btn-crud-action btn-crud-action-permissions"
                                aria-label="Manage permissions"
                                title="Permissions"
                                v-tooltip.top="'Permissions'"
                            >
                                <i class="pi pi-shield" aria-hidden="true" />
                            </Link>
                        </PermissionGuard>

                        <template v-if="(data as User).deleted_at">
                            <PermissionGuard permission="RESTORE_USERS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-restore"
                                    aria-label="Restore user"
                                    title="Restore"
                                    v-tooltip.top="'Restore'"
                                    @click="emit('restore', data as User)"
                                >
                                    <i class="pi pi-check-circle" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard v-if="isPending(data as User)" permission="CREATE_USERS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-resend"
                                    aria-label="Resend invitation"
                                    title="Resend invitation"
                                    v-tooltip.top="'Resend invitation'"
                                    @click="emit('resend', data as User)"
                                >
                                    <i class="pi pi-send" aria-hidden="true" />
                                </button>
                            </PermissionGuard>

                            <PermissionGuard permission="UPDATE_USERS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-edit"
                                    aria-label="Edit user"
                                    title="Edit"
                                    v-tooltip.top="'Edit'"
                                    @click="emit('edit', data as User)"
                                >
                                    <i class="pi pi-pencil" aria-hidden="true" />
                                </button>
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_USERS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-delete"
                                    aria-label="Suspend user"
                                    title="Suspend"
                                    v-tooltip.top="'Suspend'"
                                    @click="emit('delete', data as User)"
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
.user-name {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
}

.user-name .pi {
    color: var(--accent-primary);
    font-size: 0.85rem;
}

.user-name__block {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    line-height: 1.2;
}

.user-name__text {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.user-name__handle {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-2);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    line-height: 1.4;
}

.badge--pending {
    background: color-mix(in srgb, var(--accent-warning) 18%, transparent);
    color: var(--accent-warning);
}

.badge--active {
    background: color-mix(in srgb, var(--accent-success) 18%, transparent);
    color: var(--accent-success);
}

.badge--suspended {
    background: color-mix(in srgb, var(--accent-error) 18%, transparent);
    color: var(--accent-error);
}

.mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.role-tags {
    display: inline-flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--space-1);
}

.role-tag {
    display: inline-flex;
    align-items: center;
    padding: 1px var(--space-2);
    border-radius: var(--radius-sm);
    background: color-mix(in srgb, var(--accent-primary) 14%, transparent);
    color: var(--accent-primary);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    font-family: var(--font-mono, monospace);
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

.btn-crud-action-permissions {
    color: var(--accent-primary);
}

.btn-crud-action-permissions:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-primary) 30%, transparent);
}

.btn-crud-action-resend {
    color: var(--accent-warning);
}

.btn-crud-action-resend:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-warning) 30%, transparent);
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
