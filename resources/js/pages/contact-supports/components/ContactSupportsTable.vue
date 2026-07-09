<script setup lang="ts">
/**
 * Contact-supports server-side DataTable. Fed by Inertia partial reloads (the
 * parent owns the reactive query + `router.get`), so it never sorts / filters /
 * paginates in the browser. Rows are homogeneous per the `status` filter: an
 * ACTIVE row (deleted_at === null) shows View · Mark-read (unread only) · Edit ·
 * Delete; a SUSPENDED row shows View · Restore (never Edit). Every action is
 * permission-gated.
 *
 * Transparent-grid + action-pill styling mirrors the Blog Categories / Activity
 * Log tables (the project's DataTable reference).
 */
import { Link } from '@inertiajs/vue3';
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDate } from '@/modules/contact-support/helpers/formatDate';
import type { ContactSupport } from '@/modules/contact-support/types';

const props = defineProps<{
    data: ContactSupport[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: ContactSupport[];
}>();

const emit = defineEmits<{
    edit: [contact: ContactSupport];
    delete: [contact: ContactSupport];
    restore: [contact: ContactSupport];
    markRead: [contact: ContactSupport];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: ContactSupport[]];
}>();

function fullName(row: ContactSupport): string {
    return [row.first_name, row.last_name].filter(Boolean).join(' ').trim() || '—';
}

function rowClass(row: ContactSupport): string | undefined {
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
            @update:selection="(rows: ContactSupport[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No contact requests match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column header="Name">
                <template #body="{ data }">
                    <span class="cs-name">{{ fullName(data as ContactSupport) }}</span>
                </template>
            </Column>

            <Column field="email" header="Email">
                <template #body="{ data }">
                    <span class="cs-email">{{ (data as ContactSupport).email || '—' }}</span>
                </template>
            </Column>

            <Column field="subject" header="Subject">
                <template #body="{ data }">
                    <span class="cs-subject">{{ (data as ContactSupport).subject || '—' }}</span>
                </template>
            </Column>

            <Column header="Status" :pt="{ bodyCell: 'w-28' }">
                <template #body="{ data }">
                    <span
                        class="badge"
                        :class="(data as ContactSupport).readed ? 'badge--read' : 'badge--unread'"
                    >
                        {{ (data as ContactSupport).readed ? 'Read' : 'Unread' }}
                    </span>
                </template>
            </Column>

            <Column field="created_at" header="Received">
                <template #body="{ data }">
                    <span class="mono">{{ formatDate((data as ContactSupport).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-40' }">
                <template #body="{ data }">
                    <div class="actions-cell">
                        <PermissionGuard permission="VIEW_CONTACT_SUPPORTS">
                            <Link
                                :href="`/contact-supports/${(data as ContactSupport).uuid}`"
                                class="btn-crud-action btn-crud-action-view"
                                aria-label="View contact request"
                                title="View"
                                v-tooltip.top="'View'"
                            >
                                <i class="pi pi-eye" aria-hidden="true" />
                            </Link>
                        </PermissionGuard>

                        <template v-if="(data as ContactSupport).deleted_at">
                            <PermissionGuard permission="RESTORE_CONTACT_SUPPORTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-restore"
                                    aria-label="Restore contact request"
                                    title="Restore"
                                    v-tooltip.top="'Restore'"
                                    @click="emit('restore', data as ContactSupport)"
                                >
                                    <i class="pi pi-check-circle" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard v-if="!(data as ContactSupport).readed" permission="UPDATE_CONTACT_SUPPORTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-read"
                                    aria-label="Mark as read"
                                    title="Mark as read"
                                    v-tooltip.top="'Mark as read'"
                                    @click="emit('markRead', data as ContactSupport)"
                                >
                                    <i class="pi pi-envelope-open" aria-hidden="true" />
                                </button>
                            </PermissionGuard>

                            <PermissionGuard permission="UPDATE_CONTACT_SUPPORTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-edit"
                                    aria-label="Edit contact request"
                                    title="Edit"
                                    v-tooltip.top="'Edit'"
                                    @click="emit('edit', data as ContactSupport)"
                                >
                                    <i class="pi pi-pencil" aria-hidden="true" />
                                </button>
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_CONTACT_SUPPORTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-delete"
                                    aria-label="Suspend contact request"
                                    title="Suspend"
                                    v-tooltip.top="'Suspend'"
                                    @click="emit('delete', data as ContactSupport)"
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
.cs-name {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.cs-email {
    color: var(--text-secondary);
    font-size: var(--text-sm);
}

.cs-subject {
    display: inline-block;
    max-width: 22rem;
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

/* ── Minimalist transparent CRUD table (matches the Activity Log reference) ── */
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

/* Soft-deleted (suspended) rows — token-driven tint. */
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

.btn-crud-action-read {
    color: var(--accent-primary);
}

.btn-crud-action-read:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-primary) 30%, transparent);
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
