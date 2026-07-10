<script setup lang="ts">
/**
 * Permissions — full CRUD over the soft-deletable permission catalogue.
 *
 * The shared list mechanics live in {@see useResourceList}, the confirm dialogs
 * in {@see useConfirmAction}, and the page chrome in {@see CrudIndexShell}. This
 * file keeps only what is specific to permissions: its filter fields and confirm
 * copy. Success / error feedback flows through the backend flash surfaced
 * app-wide by AppLayout. Gated by VIEW_ANY_PERMISSIONS; every mutating control by
 * its own permission.
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
import PermissionsTable from './components/PermissionsTable.vue';
import PermissionFormDialog from './components/PermissionFormDialog.vue';
import type { AuthorizationFilters, AuthorizationQuery, AuthorizationStatus, PaginatedResponse, Permission } from '@/modules/authorization/types';
import { buildPermissionExportUrl, buildPermissionQueryParams } from '@/modules/authorization/helpers/buildPermissionQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    permissions: PaginatedResponse<Permission>;
    filters: AuthorizationFilters;
}>();

const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_PERMISSIONS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_PERMISSIONS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_PERMISSIONS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_PERMISSIONS'));

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<AuthorizationQuery>({
    search: props.filters.search,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.permissions.current_page,
    per_page: props.permissions.per_page,
});

function applyCriteria(target: AuthorizationQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as AuthorizationStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, reload, onFilters, onPage, openExport } =
    useResourceList<Permission, AuthorizationQuery>({
        baseUrl: '/permissions',
        propKey: 'permissions',
        query,
        pagination: computed(() => props.permissions),
        buildParams: buildPermissionQueryParams,
        applyCriteria,
        exportUrl: buildPermissionExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Create / edit ────────────────────────────────────────────────────── */
const { visible: formVisible, mode: formMode, entity: formPermission, openCreate, openEdit } = useFormDialog<Permission>();

function onSaved(): void {
    resetSelection();
    reload();
}

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; permission: Permission };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = action.permission.name;
    if (action.kind === 'restore') {
        return {
            title: 'Restore permission',
            message: `Restore “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend permission',
        message: `Suspend “${name}”? It will be soft-deleted and removed from every role's effective grants. You can restore it later.`,
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
            router.delete(`/permissions/${action.permission.uuid}`, options);
        } else {
            router.post(`/permissions/${action.permission.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} ${action.count === 1 ? 'permission' : 'permissions'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'permission' : 'permissions'}? They will be soft-deleted and removed from every role's grants.`,
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
        const uuids = selection.value.map((permission) => permission.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/permissions/bulk-restore' : '/permissions/bulk-delete';
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
        title="Permissions"
        subtitle="The catalogue of grants roles can be built from"
        permission="VIEW_ANY_PERMISSIONS"
        fallback-text="You don't have permission to view permissions."
        search-placeholder="Search permission name…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New permission"
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
            <PermissionsTable
                v-model:selection="selection"
                :data="permissions.data"
                :total="permissions.total"
                :per-page="permissions.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(permission: Permission) => askRow({ kind: 'delete', permission })"
                @restore="(permission: Permission) => askRow({ kind: 'restore', permission })"
            />
        </template>

        <template #dialogs>
            <PermissionFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :permission="formPermission"
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
