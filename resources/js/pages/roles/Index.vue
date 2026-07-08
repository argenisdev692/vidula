<script setup lang="ts">
/**
 * Roles — full CRUD over a soft-deletable entity that also syncs a set of
 * permission grants.
 *
 * Like the sibling Blog Categories / Activity Log screens, the list is driven by
 * Inertia partial reloads (`router.get` with `only: ['roles','filters']` on every
 * filter / page change) rather than a separate JSON API. There are no create/edit
 * page routes — the backend store/update return `back()` redirects — so create &
 * edit happen in a Volt Dialog, and delete / restore / bulk go through the
 * reusable ConfirmDialog. Success / error feedback flows through the backend flash
 * surfaced app-wide by AppLayout (no duplicate client toasts).
 *
 * Protected system roles cannot be suspended (backend invariant), so they are
 * excluded from the bulk-suspend action here as well. The whole page is gated by
 * VIEW_ANY_ROLES; every mutating control by its own permission.
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
import RolesTable from './components/RolesTable.vue';
import RoleFormDialog from './components/RoleFormDialog.vue';
import type { SharedProps } from '@/types/inertia';
import type { AuthorizationFilters, AuthorizationQuery, AuthorizationStatus, PaginatedResponse, Role } from '@/modules/authorization/types';
import { buildRoleExportUrl, buildRoleQueryParams, type RoleExportFormat } from '@/modules/authorization/helpers/buildRoleQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    roles: PaginatedResponse<Role>;
    filters: AuthorizationFilters;
    availablePermissions: string[];
    protectedRoles: string[];
}>();

usePage<SharedProps>();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_ROLES'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_ROLES'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_ROLES'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_ROLES'));

const loading = ref<boolean>(false);
const selection = ref<Role[]>([]);

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<AuthorizationQuery>({
    search: props.filters.search,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.roles.current_page,
    per_page: props.roles.per_page,
});

const firstRecord = computed<number>(() => (props.roles.current_page - 1) * props.roles.per_page);
const recordLabel = computed<string>(() => `${props.roles.total} ${props.roles.total === 1 ? 'record' : 'records'} found`);

/** The current list is homogeneous: suspended view ⇒ restore, otherwise suspend. */
const isSuspendedView = computed<boolean>(() => query.status === 'suspended');
const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/** Protected roles can never be suspended — block the bulk action for such selections. */
const selectionHasProtected = computed<boolean>(() => selection.value.some((r) => props.protectedRoles.includes(r.name)));
const bulkBlocked = computed<boolean>(() => !isSuspendedView.value && selectionHasProtected.value);

const filterFields: FilterField[] = [
    { key: 'dateRange', label: 'Created between', type: 'date-range', placeholder: 'Start — End' },
    {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Active',
        options: [
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
    router.get('/roles', buildRoleQueryParams(query), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['roles', 'filters'],
        onFinish: () => {
            loading.value = false;
        },
    });
}

function onFilters(criteria: FilterCriteria): void {
    query.search = criteria.search || null;
    query.status = (criteria.status as AuthorizationStatus | undefined) || null;

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

function openExport(format: RoleExportFormat): void {
    window.location.href = buildRoleExportUrl(query, format);
}

/* ── Create / edit ────────────────────────────────────────────────────── */
const formVisible = ref<boolean>(false);
const formMode = ref<'create' | 'edit'>('create');
const formRole = ref<Role | null>(null);

function openCreate(): void {
    formMode.value = 'create';
    formRole.value = null;
    formVisible.value = true;
}

function openEdit(role: Role): void {
    formMode.value = 'edit';
    formRole.value = role;
    formVisible.value = true;
}

function onSaved(): void {
    selection.value = [];
    reload();
}

/* ── Single-row delete / restore ──────────────────────────────────────── */
const rowAction = ref<{ kind: 'delete' | 'restore'; role: Role } | null>(null);
const rowActionVisible = ref<boolean>(false);
const rowActionLoading = ref<boolean>(false);

const rowConfirm = computed(() => {
    const name = rowAction.value?.role.name ?? 'this role';
    if (rowAction.value?.kind === 'restore') {
        return {
            title: 'Restore role',
            message: `Restore “${name}”? It will become active again and its grants re-apply.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    return {
        title: 'Suspend role',
        message: `Suspend “${name}”? It will be soft-deleted and its access revoked. You can restore it later.`,
        confirmLabel: 'Suspend',
        confirmIcon: 'pi pi-trash',
        tone: 'danger' as const,
    };
});

function askDelete(role: Role): void {
    rowAction.value = { kind: 'delete', role };
    rowActionVisible.value = true;
}

function askRestore(role: Role): void {
    rowAction.value = { kind: 'restore', role };
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
        router.delete(`/roles/${action.role.uuid}`, options);
    } else {
        router.post(`/roles/${action.role.uuid}/restore`, {}, options);
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
            message: `Restore ${count} ${count === 1 ? 'role' : 'roles'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${count} ${count === 1 ? 'role' : 'roles'}? They will be soft-deleted and their access revoked.`,
        confirmLabel: 'Suspend all',
        confirmIcon: 'pi pi-trash',
        tone: 'danger' as const,
    };
});

function askBulk(): void {
    if (selection.value.length > 0 && !bulkBlocked.value) {
        bulkVisible.value = true;
    }
}

function confirmBulk(): void {
    const uuids = selection.value.map((r) => r.uuid);
    if (uuids.length === 0) {
        return;
    }
    bulkLoading.value = true;

    const url = isSuspendedView.value ? '/roles/bulk-restore' : '/roles/bulk-delete';
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
    <Head title="Roles" />

    <AppHeader title="Roles" subtitle="Define roles and the permissions they grant across the app" />

    <PermissionGuard permission="VIEW_ANY_ROLES">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view roles.</p>
            </div>
        </template>

        <div class="page">
            <AdvancedFilter
                search-placeholder="Search role name…"
                :fields="filterFields"
                :show-export-pdf="canExport"
                :show-export-excel="canExport"
                :show-export-csv="canExport"
                :show-create="canCreate"
                create-label="New role"
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
                        <span v-if="bulkBlocked" class="bulk-bar__note">
                            <i class="pi pi-lock" aria-hidden="true" /> Selection includes a protected system role
                        </span>
                        <Button
                            size="small"
                            :label="isSuspendedView ? 'Restore selected' : 'Suspend selected'"
                            :icon="isSuspendedView ? 'pi pi-check-circle' : 'pi pi-trash'"
                            outlined
                            :disabled="bulkBlocked"
                            @click="askBulk"
                        />
                    </div>
                </Transition>
            </div>

            <RolesTable
                v-model:selection="selection"
                :data="roles.data"
                :total="roles.total"
                :per-page="roles.per_page"
                :first="firstRecord"
                :loading="loading"
                :protected-roles="protectedRoles"
                @page="onPage"
                @edit="openEdit"
                @delete="askDelete"
                @restore="askRestore"
            />
        </div>
    </PermissionGuard>

    <RoleFormDialog
        v-model:visible="formVisible"
        :mode="formMode"
        :role="formRole"
        :available="availablePermissions"
        :protected-roles="protectedRoles"
        @saved="onSaved"
    />

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

.bulk-bar__note {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    font-size: var(--text-xs);
    color: var(--accent-warning);
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
