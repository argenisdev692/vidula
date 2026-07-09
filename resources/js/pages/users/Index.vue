<script setup lang="ts">
/**
 * Users — admin user management over a soft-deletable account with a three-state
 * lifecycle (Pending → Active, plus Suspended when soft-deleted).
 *
 * Like the sibling Roles / Blog Categories screens, the list is driven by Inertia
 * partial reloads (`router.get` with `only: ['users','filters']` on every filter /
 * page change) rather than a JSON API. There are no create/edit page routes — the
 * backend store/update return `back()` redirects — so invite & edit happen in a
 * Volt Dialog, and delete / restore / resend / bulk go through the reusable
 * ConfirmDialog. Success / error feedback flows through the backend flash surfaced
 * app-wide by AppLayout (no duplicate client toasts).
 *
 * Suspended rows only ever appear under the "Suspended" filter (onlyTrashed), so
 * the list is always homogeneous: bulk action = restore in that view, suspend
 * otherwise. The page is gated by VIEW_ANY_USERS; every mutating control by its
 * own permission.
 */
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import type { DataTablePageEvent } from 'primevue/datatable';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import AdvancedFilter, { type FilterCriteria, type FilterField } from '@/common/data-table/AdvancedFilter.vue';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import Button from '@/volt/Button.vue';
import UsersTable from './components/UsersTable.vue';
import type { SharedProps } from '@/types/inertia';
import type { PaginatedResponse, User, UserFilters, UserQuery, UserStatus } from '@/modules/users/types';
import { buildUserExportUrl, buildUserQueryParams, type UserExportFormat } from '@/modules/users/helpers/buildUserQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    users: PaginatedResponse<User>;
    filters: UserFilters;
}>();

usePage<SharedProps>();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_USERS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_USERS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_USERS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_USERS'));

const loading = ref<boolean>(false);
const selection = ref<User[]>([]);

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<UserQuery>({
    search: props.filters.search,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.users.current_page,
    per_page: props.users.per_page,
});

const firstRecord = computed<number>(() => (props.users.current_page - 1) * props.users.per_page);
const recordLabel = computed<string>(() => `${props.users.total} ${props.users.total === 1 ? 'record' : 'records'} found`);

/** Suspended rows only appear in the suspended view ⇒ restore, otherwise suspend. */
const isSuspendedView = computed<boolean>(() => query.status === 'suspended');
const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

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

function toIsoDate(date: Date): string {
    return date.toISOString().slice(0, 10);
}

function reload(): void {
    loading.value = true;
    router.get('/users', buildUserQueryParams(query), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['users', 'filters'],
        onFinish: () => {
            loading.value = false;
        },
    });
}

function onFilters(criteria: FilterCriteria): void {
    query.search = criteria.search || null;
    query.status = (criteria.status as UserStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    query.date_from = range?.[0] ? toIsoDate(range[0]) : null;
    query.date_to = range?.[1] ? toIsoDate(range[1]) : null;

    query.page = 1;
    selection.value = [];
    reload();
}

function onPage(event: DataTablePageEvent): void {
    query.page = event.page + 1;
    query.per_page = event.rows;
    reload();
}

function openExport(format: UserExportFormat): void {
    window.location.href = buildUserExportUrl(query, format);
}

/* ── Invite / edit — dedicated pages (no modal) ───────────────────────── */
function openCreate(): void {
    router.visit('/users/create');
}

function openEdit(user: User): void {
    router.visit(`/users/${user.uuid}/edit`);
}

/* ── Single-row delete / restore / resend ─────────────────────────────── */
type RowActionKind = 'delete' | 'restore' | 'resend';
const rowAction = ref<{ kind: RowActionKind; user: User } | null>(null);
const rowActionVisible = ref<boolean>(false);
const rowActionLoading = ref<boolean>(false);

function displayName(user: User | undefined): string {
    if (!user) {
        return 'this user';
    }
    return [user.first_name, user.last_name].filter(Boolean).join(' ').trim() || user.email;
}

const rowConfirm = computed(() => {
    const name = displayName(rowAction.value?.user);
    if (rowAction.value?.kind === 'restore') {
        return {
            title: 'Restore user',
            message: `Restore “${name}”? Their account becomes active again and they can sign in.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    if (rowAction.value?.kind === 'resend') {
        return {
            title: 'Resend invitation',
            message: `Send a fresh activation link to “${name}”? Any previous link is superseded.`,
            confirmLabel: 'Resend',
            confirmIcon: 'pi pi-send',
            tone: 'primary' as const,
        };
    }
    return {
        title: 'Suspend user',
        message: `Suspend “${name}”? Their account is soft-deleted and sign-in is revoked. You can restore it later.`,
        confirmLabel: 'Suspend',
        confirmIcon: 'pi pi-trash',
        tone: 'danger' as const,
    };
});

function askDelete(user: User): void {
    rowAction.value = { kind: 'delete', user };
    rowActionVisible.value = true;
}

function askRestore(user: User): void {
    rowAction.value = { kind: 'restore', user };
    rowActionVisible.value = true;
}

function askResend(user: User): void {
    rowAction.value = { kind: 'resend', user };
    rowActionVisible.value = true;
}

function confirmRowAction(): void {
    const action = rowAction.value;
    if (!action) {
        return;
    }
    rowActionLoading.value = true;

    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selection.value = [];
        },
        onFinish: () => {
            rowActionLoading.value = false;
            rowActionVisible.value = false;
        },
    };

    if (action.kind === 'delete') {
        router.delete(`/users/${action.user.uuid}`, options);
    } else if (action.kind === 'restore') {
        router.post(`/users/${action.user.uuid}/restore`, {}, options);
    } else {
        router.post(`/users/${action.user.uuid}/resend-invitation`, {}, options);
    }
}

/* ── Bulk suspend / restore ───────────────────────────────────────────── */
const bulkVisible = ref<boolean>(false);
const bulkLoading = ref<boolean>(false);

const bulkConfirm = computed(() => {
    const count = selection.value.length;
    if (isSuspendedView.value) {
        return {
            title: 'Restore selected',
            message: `Restore ${count} ${count === 1 ? 'user' : 'users'}? Their accounts become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${count} ${count === 1 ? 'user' : 'users'}? Their accounts are soft-deleted and sign-in revoked.`,
        confirmLabel: 'Suspend all',
        confirmIcon: 'pi pi-trash',
        tone: 'danger' as const,
    };
});

function askBulk(): void {
    if (selection.value.length > 0) {
        bulkVisible.value = true;
    }
}

function confirmBulk(): void {
    const uuids = selection.value.map((u) => u.uuid);
    if (uuids.length === 0) {
        return;
    }
    bulkLoading.value = true;

    const url = isSuspendedView.value ? '/users/bulk-restore' : '/users/bulk-delete';
    router.post(
        url,
        { uuids },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                selection.value = [];
            },
            onFinish: () => {
                bulkLoading.value = false;
                bulkVisible.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Users" />

    <AppHeader title="Users" subtitle="Invite, manage and suspend the people who can access the app" />

    <PermissionGuard permission="VIEW_ANY_USERS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view users.</p>
            </div>
        </template>

        <div class="page">
            <AdvancedFilter
                search-placeholder="Search name, username or email…"
                :fields="filterFields"
                :show-export-pdf="canExport"
                :show-export-excel="canExport"
                :show-export-csv="canExport"
                :show-create="canCreate"
                create-label="Invite user"
                @filters-change="onFilters"
                @create="openCreate"
                @export-pdf="openExport('pdf')"
                @export-excel="openExport('xlsx')"
                @export-csv="openExport('csv')"
            />

            <div class="toolbar">
                <p class="counter">{{ recordLabel }}</p>

                <Transition name="fade">
                    <div v-if="selection.length > 0 && canBulkAct" class="bulk-bar">
                        <span class="bulk-bar__count">{{ selection.length }} selected</span>
                        <Button
                            size="small"
                            :label="isSuspendedView ? 'Restore selected' : 'Suspend selected'"
                            :icon="isSuspendedView ? 'pi pi-check-circle' : 'pi pi-trash'"
                            outlined
                            @click="askBulk"
                        />
                    </div>
                </Transition>
            </div>

            <UsersTable
                v-model:selection="selection"
                :data="users.data"
                :total="users.total"
                :per-page="users.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="askDelete"
                @restore="askRestore"
                @resend="askResend"
            />
        </div>
    </PermissionGuard>

    <ConfirmDialog
        v-model:visible="rowActionVisible"
        :title="rowConfirm.title"
        :message="rowConfirm.message"
        :confirm-label="rowConfirm.confirmLabel"
        :confirm-icon="rowConfirm.confirmIcon"
        :tone="rowConfirm.tone"
        :loading="rowActionLoading"
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

<style scoped>
.page {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    flex-wrap: wrap;
}

.counter {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.bulk-bar {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    flex-wrap: wrap;
}

.bulk-bar__count {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity var(--transition), transform var(--transition);
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-16) var(--space-6);
    color: var(--text-muted);
}

.empty .pi {
    font-size: var(--text-3xl);
}
</style>
