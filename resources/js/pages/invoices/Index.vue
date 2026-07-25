<script setup lang="ts">
/**
 * Invoices — CRUD over soft-deletable PDF invoices for CRM clients.
 * Create/edit in a dialog; PDF download regenerates from current DB state.
 */
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import type { FilterCriteria, FilterField } from '@/common/data-table/AdvancedFilter.vue';
import CrudIndexShell from '@/common/data-table/CrudIndexShell.vue';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import { useResourceList } from '@/common/data-table/useResourceList';
import { useConfirmAction } from '@/common/data-table/useConfirmAction';
import { useFormDialog } from '@/common/data-table/useFormDialog';
import { toLocalIsoDate } from '@/lib/date';
import InvoicesTable from './components/InvoicesTable.vue';
import InvoiceFormDialog from './components/InvoiceFormDialog.vue';
import type {
    Invoice,
    InvoiceClientOption,
    InvoiceFilters,
    InvoiceQuery,
    InvoiceServiceOption,
    InvoiceSoftStatus,
    NextInvoiceNumber,
    PaginatedResponse,
} from '@/modules/invoices/types';
import { buildInvoiceQueryParams } from '@/modules/invoices/helpers/buildInvoiceQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    invoices: PaginatedResponse<Invoice>;
    filters: InvoiceFilters;
    nextInvoiceNumber: NextInvoiceNumber;
    clients: InvoiceClientOption[];
    services: InvoiceServiceOption[];
    defaultNotes: string | null;
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_INVOICES'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_INVOICES'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_INVOICES'));

const query = reactive<InvoiceQuery>({
    search: props.filters.search,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    year: props.filters.year,
    client_uuid: props.filters.client_uuid,
    page: props.invoices.current_page,
    per_page: props.invoices.per_page,
});

function applyCriteria(target: InvoiceQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as InvoiceSoftStatus | undefined) || null;
    target.client_uuid = (criteria.client_uuid as string | undefined) || null;
    const yearRaw = criteria.year as string | undefined;
    target.year = yearRaw ? Number.parseInt(yearRaw, 10) || null : null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage } =
    useResourceList<Invoice, InvoiceQuery>({
        baseUrl: '/invoices',
        propKey: 'invoices',
        query,
        pagination: computed(() => props.invoices),
        buildParams: buildInvoiceQueryParams,
        applyCriteria,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

const { visible: formVisible, mode: formMode, entity: formInvoice, openCreate, openEdit } = useFormDialog<Invoice>();

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Invoice updated' : 'Invoice created',
        life: 4000,
    });
}

type RowAction = { kind: 'delete' | 'restore'; invoice: Invoice };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = action.invoice.invoice_number;
    if (action.kind === 'restore') {
        return {
            title: 'Restore invoice',
            message: `Restore invoice “${name}”?`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Delete invoice',
        message: `Delete invoice “${name}”? It will be soft-deleted and can be restored later.`,
        confirmLabel: 'Delete',
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
                toast.add({
                    severity: 'success',
                    summary: action.kind === 'restore' ? 'Invoice restored' : 'Invoice deleted',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/invoices/${action.invoice.uuid}`, options);
        } else {
            router.post(`/invoices/${action.invoice.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} ${action.count === 1 ? 'invoice' : 'invoices'}?`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Delete selected',
        message: `Delete ${action.count} ${action.count === 1 ? 'invoice' : 'invoices'}? They can be restored later.`,
        confirmLabel: 'Delete all',
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
        const uuids = selection.value.map((invoice) => invoice.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/invoices/bulk-restore' : '/invoices/bulk-delete';
        router.post(
            url,
            { uuids },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    resetSelection();
                    toast.add({
                        severity: 'success',
                        summary: isSuspendedView.value ? 'Selected invoices restored' : 'Selected invoices deleted',
                        life: 4000,
                    });
                },
                onFinish: finish,
            },
        );
    });
}

function downloadPdf(invoice: Invoice): void {
    window.open(`/invoices/${invoice.uuid}/pdf`, '_blank');
}

const clientFilterOptions = computed(() =>
    props.clients.map((client) => ({ label: client.client_name, value: client.uuid })),
);

const filterFields = computed<FilterField[]>(() => [
    { key: 'dateRange', label: 'Issue date between', type: 'date-range', placeholder: 'Start — End' },
    {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Active',
        options: [
            { label: 'Active', value: 'active' },
            { label: 'Deleted', value: 'suspended' },
        ],
    },
    {
        key: 'client_uuid',
        label: 'Client',
        type: 'select',
        placeholder: 'All clients',
        options: clientFilterOptions.value,
    },
]);
</script>

<template>
    <CrudIndexShell
        title="Invoices"
        subtitle="Create and download PDF invoices for your clients"
        permission="VIEW_ANY_INVOICES"
        fallback-text="You don't have permission to view invoices."
        search-placeholder="Search invoice number or client…"
        :fields="filterFields"
        :can-export="false"
        :can-create="canCreate"
        create-label="New invoice"
        :record-label="recordLabel"
        :selection-count="selection.length"
        :can-bulk-act="canBulkAct"
        :is-suspended-view="isSuspendedView"
        @filters-change="onFilters"
        @create="openCreate"
        @bulk="askBulk"
    >
        <template #table>
            <InvoicesTable
                v-model:selection="selection"
                :data="invoices.data"
                :total="invoices.total"
                :per-page="invoices.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @pdf="downloadPdf"
                @delete="(invoice: Invoice) => askRow({ kind: 'delete', invoice })"
                @restore="(invoice: Invoice) => askRow({ kind: 'restore', invoice })"
            />
        </template>

        <template #dialogs>
            <InvoiceFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :invoice="formInvoice"
                :clients="clients"
                :services="services"
                :next-invoice-number="nextInvoiceNumber"
                :default-notes="defaultNotes"
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
