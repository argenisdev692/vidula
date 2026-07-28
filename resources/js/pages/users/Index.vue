<script setup lang="ts">
/**
 * Users — admin user management over a soft-deletable account with a three-state
 * lifecycle (Pending → Active, plus Suspended when soft-deleted).
 *
 * List data arrives as Inertia props (not a separate JSON API), so the table is
 * fed by Inertia partial reloads via {@see useResourceList} — the same convention
 * as Clients / Activity Log. Pinia Colada is reserved for JSON `/data/admin/*`
 * surfaces; using it here would bypass Inertia (YAGNI).
 *
 * Confirm dialogs live in {@see useConfirmAction}; page chrome in {@see CrudIndexShell}.
 * Invite & edit are dedicated pages (not a modal). Invitation mail is sent through
 * Brevo (`UsesBrevoMailer`) — "Resend invitation" is the UX verb, not the Resend.com
 * provider. Gated by VIEW_ANY_USERS; every mutating control by its own permission.
 */
import { computed } from 'vue';
import { router, useRemember } from '@inertiajs/vue3';
import type { DataTableSortEvent } from 'primevue/datatable';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import type { FilterCriteria, FilterField } from '@/common/data-table/AdvancedFilter.vue';
import CrudIndexShell from '@/common/data-table/CrudIndexShell.vue';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import { useResourceList } from '@/common/data-table/useResourceList';
import { useConfirmAction } from '@/common/data-table/useConfirmAction';
import { toLocalIsoDate } from '@/lib/date';
import UsersTable from './components/UsersTable.vue';
import type { PaginatedResponse, User, UserFilters, UserQuery, UserStatus } from '@/modules/users/types';
import { buildUserExportUrl, buildUserQueryParams } from '@/modules/users/helpers/buildUserQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    users: PaginatedResponse<User>;
    filters: UserFilters;
}>();

const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_USERS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_USERS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_USERS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_USERS'));

/** Remembered across history back/forward — seeded from the server-echoed props. */
const query = useRemember<UserQuery>(
    {
        search: props.filters.search,
        status: props.filters.status,
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
        sort_field: props.filters.sort_field ?? 'created_at',
        sort_order: props.filters.sort_order === 1 ? 1 : -1,
        page: props.users.current_page,
        per_page: props.users.per_page,
    },
    'users.index',
);

function applyCriteria(target: UserQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as UserStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, reload, onFilters, onPage, openExport } =
    useResourceList<User, UserQuery>({
        baseUrl: '/users',
        propKey: 'users',
        query,
        pagination: computed(() => props.users),
        buildParams: buildUserQueryParams,
        applyCriteria,
        exportUrl: buildUserExportUrl,
    });

function onSort(event: DataTableSortEvent): void {
    const field = typeof event.sortField === 'string' ? event.sortField : 'created_at';
    query.sort_field = field;
    query.sort_order = event.sortOrder === 1 ? 1 : -1;
    query.page = 1;
    reload();
}

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Invite / edit — dedicated pages (no modal) ───────────────────────── */
function openCreate(): void {
    router.visit('/users/create');
}

function openEdit(user: User): void {
    router.visit(`/users/${user.uuid}/edit`);
}

/* ── Single-row suspend / restore / resend ────────────────────────────── */
type RowKind = 'delete' | 'restore' | 'resend';
type RowAction = { kind: RowKind; user: User };

function displayName(user: User): string {
    return [user.first_name, user.last_name].filter(Boolean).join(' ').trim() || user.email;
}

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = displayName(action.user);
    if (action.kind === 'restore') {
        return {
            title: 'Restore user',
            message: `Restore “${name}”? Their account becomes active again and they can sign in.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    if (action.kind === 'resend') {
        return {
            title: 'Resend invitation',
            message: `Send a fresh activation link to “${name}”? Any previous link is superseded.`,
            confirmLabel: 'Resend',
            confirmIcon: 'pi pi-send',
            tone: 'primary',
        };
    }
    return {
        title: 'Suspend user',
        message: `Suspend “${name}”? Their account is soft-deleted and sign-in is revoked. You can restore it later.`,
        confirmLabel: 'Suspend',
        confirmIcon: 'pi pi-trash',
        tone: 'danger',
    };
});

function confirmRowAction(): void {
    runRow((action, finish) => {
        const options = {
            preserveScroll: true,
            preserveState: true,
            onSuccess: resetSelection,
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/users/${action.user.uuid}`, options);
        } else if (action.kind === 'restore') {
            router.post(`/users/${action.user.uuid}/restore`, {}, options);
        } else {
            router.post(`/users/${action.user.uuid}/resend-invitation`, {}, options);
        }
    });
}

/* ── Bulk suspend / restore ───────────────────────────────────────────── */
const {
    visible: bulkVisible,
    loading: bulkLoading,
    confirm: bulkConfirm,
    ask: askBulkConfirm,
    run: runBulk,
} = useConfirmAction<{ count: number }>((action) => {
    if (isSuspendedView.value) {
        return {
            title: 'Restore selected',
            message: `Restore ${action.count} ${action.count === 1 ? 'user' : 'users'}? Their accounts become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'user' : 'users'}? Their accounts are soft-deleted and sign-in revoked.`,
        confirmLabel: 'Suspend all',
        confirmIcon: 'pi pi-trash',
        tone: 'danger',
    };
});

function askBulk(): void {
    if (selection.value.length > 0) {
        askBulkConfirm({ count: selection.value.length });
    }
}

function confirmBulk(): void {
    runBulk((_action, finish) => {
        const uuids = selection.value.map((user) => user.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/users/bulk-restore' : '/users/bulk-delete';
        router.post(url, { uuids }, { preserveScroll: true, preserveState: true, onSuccess: resetSelection, onFinish: finish });
    });
}

const filterFields: FilterField[] = [
    { key: 'dateRange', label: 'Created between', type: 'date-range', placeholder: 'Start — End' },
    {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'All',
        options: [
            { label: 'Pending', value: 'pending' },
            { label: 'Active', value: 'active' },
            { label: 'Suspended', value: 'suspended' },
        ],
    },
];
</script>

<template>
    <CrudIndexShell
        title="Users"
        subtitle="Invite, manage and suspend the people who can access the app"
        permission="VIEW_ANY_USERS"
        fallback-text="You don't have permission to view users."
        search-placeholder="Search name, username or email…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="Invite user"
        :record-label="recordLabel"
        :selection-count="selection.length"
        :can-bulk-act="canBulkAct"
        :is-suspended-view="isSuspendedView"
        @filters-change="onFilters"
        @create="openCreate"
        @export-pdf="openExport('pdf')"
        @export-excel="openExport('xlsx')"
        @export-csv="openExport('csv')"
        @bulk="askBulk"
    >
        <template #table>
            <UsersTable
                v-model:selection="selection"
                :data="users.data"
                :total="users.total"
                :per-page="users.per_page"
                :first="firstRecord"
                :loading="loading"
                :sort-field="query.sort_field"
                :sort-order="query.sort_order"
                @page="onPage"
                @sort="onSort"
                @edit="openEdit"
                @delete="(user: User) => askRow({ kind: 'delete', user })"
                @restore="(user: User) => askRow({ kind: 'restore', user })"
                @resend="(user: User) => askRow({ kind: 'resend', user })"
            />
        </template>

        <template #dialogs>
            <ConfirmDialog
                v-model:visible="rowVisible"
                :title="rowConfirm.title"
                :message="rowConfirm.message"
                :confirm-label="rowConfirm.confirmLabel"
                :confirm-icon="rowConfirm.confirmIcon"
                :tone="rowConfirm.tone"
                :loading="rowLoading"
                @confirm="confirmRowAction"
            />

            <ConfirmDialog
                v-model:visible="bulkVisible"
                :title="bulkConfirm.title"
                :message="bulkConfirm.message"
                :confirm-label="bulkConfirm.confirmLabel"
                :confirm-icon="bulkConfirm.confirmIcon"
                :tone="bulkConfirm.tone"
                :loading="bulkLoading"
                @confirm="confirmBulk"
            />
        </template>
    </CrudIndexShell>
</template>
