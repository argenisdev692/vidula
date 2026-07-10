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
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import Tag from '@/volt/Tag.vue';
import ActionButton from '@/common/data-table/ActionButton.vue';
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
                    <Tag
                        :value="USER_STATUS_META[resolveUserStatus(data as User)].label"
                        :severity="USER_STATUS_META[resolveUserStatus(data as User)].severity"
                    />
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
                            <ActionButton
                                icon="pi pi-eye"
                                tone="view"
                                label="View user"
                                :href="`/users/${(data as User).uuid}`"
                            />
                        </PermissionGuard>

                        <PermissionGuard :permission="['ASSIGN_ROLES_USERS', 'ASSIGN_PERMISSIONS_USERS']">
                            <ActionButton
                                icon="pi pi-shield"
                                tone="warning"
                                label="Manage access"
                                :href="`/users/${(data as User).uuid}/permissions`"
                            />
                        </PermissionGuard>

                        <template v-if="(data as User).deleted_at">
                            <PermissionGuard permission="RESTORE_USERS">
                                <ActionButton
                                    icon="pi pi-check-circle"
                                    tone="restore"
                                    label="Restore user"
                                    @click="emit('restore', data as User)"
                                />
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard v-if="isPending(data as User)" permission="CREATE_USERS">
                                <ActionButton
                                    icon="pi pi-send"
                                    tone="warning"
                                    label="Resend invitation"
                                    @click="emit('resend', data as User)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="UPDATE_USERS">
                                <ActionButton
                                    icon="pi pi-pencil"
                                    tone="edit"
                                    label="Edit user"
                                    @click="emit('edit', data as User)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_USERS">
                                <ActionButton
                                    icon="pi pi-trash"
                                    tone="delete"
                                    label="Suspend user"
                                    @click="emit('delete', data as User)"
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
