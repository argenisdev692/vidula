<script setup lang="ts">
/**
 * Roles — full CRUD over a soft-deletable entity that also syncs a set of
 * permission grants.
 *
 * The shared list mechanics live in {@see useResourceList}, the confirm dialogs
 * in {@see useConfirmAction}, and the page chrome in {@see CrudIndexShell}. This
 * file keeps only what is specific to roles: its filter fields, confirm copy, and
 * the protected-system-role invariant (such roles can never be suspended, so they
 * are excluded from the bulk-suspend action). Success / error feedback flows
 * through the backend flash surfaced app-wide by AppLayout (no client toasts).
 * Gated by VIEW_ANY_ROLES; every mutating control by its own permission.
 */
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import type { FilterCriteria, FilterField } from '@/common/data-table/AdvancedFilter.vue';
import CrudIndexShell from '@/common/data-table/CrudIndexShell.vue';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import { useResourceList } from '@/common/data-table/useResourceList';
import { useConfirmAction } from '@/common/data-table/useConfirmAction';
import { useFormDialog } from '@/common/data-table/useFormDialog';
import { toLocalIsoDate } from '@/lib/date';
import RolesTable from './components/RolesTable.vue';
import RoleFormDialog from './components/RoleFormDialog.vue';
import type { AuthorizationFilters, AuthorizationQuery, AuthorizationStatus, PaginatedResponse, Role } from '@/modules/authorization/types';
import { buildRoleExportUrl, buildRoleQueryParams } from '@/modules/authorization/helpers/buildRoleQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    roles: PaginatedResponse<Role>;
    filters: AuthorizationFilters;
    availablePermissions: string[];
    protectedRoles: string[];
}>();

const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_ROLES'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_ROLES'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_ROLES'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_ROLES'));

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<AuthorizationQuery>({
    search: props.filters.search,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.roles.current_page,
    per_page: props.roles.per_page,
});

function applyCriteria(target: AuthorizationQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as AuthorizationStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, reload, onFilters, onPage, openExport } =
    useResourceList<Role, AuthorizationQuery>({
        baseUrl: '/roles',
        propKey: 'roles',
        query,
        pagination: computed(() => props.roles),
        buildParams: buildRoleQueryParams,
        applyCriteria,
        exportUrl: buildRoleExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/** Protected roles can never be suspended — block the bulk action for such selections. */
const selectionHasProtected = computed<boolean>(() => selection.value.some((role) => props.protectedRoles.includes(role.name)));
const bulkBlocked = computed<boolean>(() => !isSuspendedView.value && selectionHasProtected.value);

/* ── Create / edit ────────────────────────────────────────────────────── */
const { visible: formVisible, mode: formMode, entity: formRole, openCreate, openEdit } = useFormDialog<Role>();

function onSaved(): void {
    resetSelection();
    reload();
}

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; role: Role };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = action.role.name;
    if (action.kind === 'restore') {
        return {
            title: 'Restore role',
            message: `Restore “${name}”? It will become active again and its grants re-apply.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend role',
        message: `Suspend “${name}”? It will be soft-deleted and its access revoked. You can restore it later.`,
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
            router.delete(`/roles/${action.role.uuid}`, options);
        } else {
            router.post(`/roles/${action.role.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} ${action.count === 1 ? 'role' : 'roles'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'role' : 'roles'}? They will be soft-deleted and their access revoked.`,
        confirmLabel: 'Suspend all',
        confirmIcon: 'pi pi-trash',
        tone: 'danger',
    };
});

function askBulk(): void {
    if (selection.value.length > 0 && !bulkBlocked.value) {
        askBulkConfirm({ count: selection.value.length });
    }
}

function confirmBulk(): void {
    runBulk((_action, finish) => {
        const uuids = selection.value.map((role) => role.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/roles/bulk-restore' : '/roles/bulk-delete';
        router.post(url, { uuids }, { preserveScroll: true, preserveState: true, onSuccess: resetSelection, onFinish: finish });
    });
}

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
</script>

<template>
    <CrudIndexShell
        title="Roles"
        subtitle="Define roles and the permissions they grant across the app"
        permission="VIEW_ANY_ROLES"
        fallback-text="You don't have permission to view roles."
        search-placeholder="Search role name…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New role"
        :record-label="recordLabel"
        :selection-count="selection.length"
        :can-bulk-act="canBulkAct"
        :is-suspended-view="isSuspendedView"
        :bulk-disabled="bulkBlocked"
        @filters-change="onFilters"
        @create="openCreate"
        @export-pdf="openExport('pdf')"
        @export-excel="openExport('xlsx')"
        @export-csv="openExport('csv')"
        @bulk="askBulk"
    >
        <template #bulk-note>
            <span v-if="bulkBlocked" class="bulk-bar__note">
                <i class="pi pi-lock" aria-hidden="true" /> Selection includes a protected system role
            </span>
        </template>

        <template #table>
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
                @delete="(role: Role) => askRow({ kind: 'delete', role })"
                @restore="(role: Role) => askRow({ kind: 'restore', role })"
            />
        </template>

        <template #dialogs>
            <RoleFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :role="formRole"
                :available="availablePermissions"
                :protected-roles="protectedRoles"
                @saved="onSaved"
            />

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

<style scoped>
.bulk-bar__note {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    font-size: var(--text-xs);
    color: var(--accent-warning);
}
</style>
