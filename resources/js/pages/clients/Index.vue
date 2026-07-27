<script setup lang="ts">
/**
 * Clients — CRM CRUD over soft-deletable contacts owned by a user.
 *
 * Shared list mechanics live in useResourceList / useConfirmAction /
 * CrudIndexShell. Create & edit happen in a dialog (store/update return back()).
 * Export is wired (CLIENTS includes EXPORT in RolePermissionSeeder).
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
import ClientsTable from './components/ClientsTable.vue';
import ClientFormDialog from './components/ClientFormDialog.vue';
import type {
    Client,
    ClientFilters,
    ClientLifecycleStatus,
    ClientQuery,
    ClientSoftStatus,
    PaginatedResponse,
} from '@/modules/clients/types';
import { buildClientExportUrl, buildClientQueryParams } from '@/modules/clients/helpers/buildClientQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    clients: PaginatedResponse<Client>;
    filters: ClientFilters;
}>();

const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_CLIENTS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_CLIENTS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_CLIENTS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_CLIENTS'));

const query = reactive<ClientQuery>({
    search: props.filters.search,
    status: props.filters.status,
    client_status: props.filters.client_status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.clients.current_page,
    per_page: props.clients.per_page,
});

function applyCriteria(target: ClientQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as ClientSoftStatus | undefined) || null;
    target.client_status = (criteria.client_status as ClientLifecycleStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage, openExport } =
    useResourceList<Client, ClientQuery>({
        baseUrl: '/clients',
        propKey: 'clients',
        query,
        pagination: computed(() => props.clients),
        buildParams: buildClientQueryParams,
        applyCriteria,
        exportUrl: buildClientExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

const { visible: formVisible, mode: formMode, entity: formClient, openCreate, openEdit } = useFormDialog<Client>();

type RowAction = { kind: 'delete' | 'restore'; client: Client };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = action.client.client_name ?? 'this client';
    if (action.kind === 'restore') {
        return {
            title: 'Restore client',
            message: `Restore “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend client',
        message: `Suspend “${name}”? It will be soft-deleted and hidden from the active list. You can restore it later.`,
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
            onSuccess: () => {
                resetSelection();
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/clients/${action.client.uuid}`, options);
        } else {
            router.post(`/clients/${action.client.uuid}/restore`, {}, options);
        }
    });
}

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
            message: `Restore ${action.count} ${action.count === 1 ? 'client' : 'clients'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'client' : 'clients'}? They will be soft-deleted and hidden from the active list.`,
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
        const uuids = selection.value.map((client) => client.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/clients/bulk-restore' : '/clients/bulk-delete';
        router.post(
            url,
            { uuids },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    resetSelection();
                },
                onFinish: finish,
            },
        );
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
    {
        key: 'client_status',
        label: 'Lifecycle',
        type: 'select',
        placeholder: 'All lifecycles',
        options: [
            { label: 'Draft', value: 'DRAFT' },
            { label: 'Active', value: 'ACTIVE' },
            { label: 'Archived', value: 'ARCHIVED' },
        ],
    },
];
</script>

<template>
    <CrudIndexShell
        title="Clients"
        subtitle="Manage CRM contacts owned by instructors and creators"
        permission="VIEW_ANY_CLIENTS"
        fallback-text="You don't have permission to view clients."
        search-placeholder="Search name, email, phone, tax ID…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New client"
        :record-label="recordLabel"
        :selection-count="selection.length"
        :can-bulk-act="canBulkAct"
        :is-suspended-view="isSuspendedView"
        @filters-change="onFilters"
        @create="openCreate"
        @bulk="askBulk"
        @export-pdf="openExport('pdf')"
        @export-excel="openExport('xlsx')"
        @export-csv="openExport('csv')"
    >
        <template #table>
            <ClientsTable
                v-model:selection="selection"
                :data="clients.data"
                :total="clients.total"
                :per-page="clients.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(client: Client) => askRow({ kind: 'delete', client })"
                @restore="(client: Client) => askRow({ kind: 'restore', client })"
            />
        </template>

        <template #dialogs>
            <ClientFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :client="formClient"
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
