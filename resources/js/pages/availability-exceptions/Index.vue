<script setup lang="ts">
/**
 * Availability Exceptions — full CRUD over per-date overrides (closures and
 * forced-open windows). Holiday rows are system-materialised; they are still
 * viewable / editable per permission and flagged by a Source badge in the table.
 *
 * The shared list mechanics live in {@see useResourceList}, the confirm dialogs
 * in {@see useConfirmAction}, and the page chrome in {@see CrudIndexShell}. This
 * file keeps only what is specific to exceptions: its state / date filters,
 * confirm copy and toasts. Gated by VIEW_ANY_AVAILABILITY_EXCEPTIONS; every
 * mutating control by its own permission.
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
import AvailabilityExceptionsTable from './components/AvailabilityExceptionsTable.vue';
import AvailabilityExceptionFormDialog from './components/AvailabilityExceptionFormDialog.vue';
import { formatDate } from '@/modules/availability/helpers/availabilityFormat';
import { buildAvailabilityExceptionQueryParams, buildAvailabilityExportUrl } from '@/modules/availability/helpers/buildAvailabilityQueryParams';
import type { ExportFormat } from '@/lib/queryParams';
import type {
    AvailabilityException,
    AvailabilityExceptionFilters,
    AvailabilityExceptionQuery,
    AvailabilityStatus,
    ExceptionAvailability,
    PaginatedResponse,
} from '@/modules/availability/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    availabilityExceptions: PaginatedResponse<AvailabilityException>;
    filters: AvailabilityExceptionFilters;
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_AVAILABILITY_EXCEPTIONS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_AVAILABILITY_EXCEPTIONS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_AVAILABILITY_EXCEPTIONS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_AVAILABILITY_EXCEPTIONS'));

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<AvailabilityExceptionQuery>({
    availability: props.filters.availability,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.availabilityExceptions.current_page,
    per_page: props.availabilityExceptions.per_page,
});

function applyCriteria(target: AvailabilityExceptionQuery, criteria: FilterCriteria): void {
    target.availability = (criteria.availability as ExceptionAvailability | undefined) || null;
    target.status = (criteria.status as AvailabilityStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

function exportUrl(current: AvailabilityExceptionQuery, format: ExportFormat): string {
    return buildAvailabilityExportUrl('/availability-exceptions/export', buildAvailabilityExceptionQueryParams(current), format);
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage, openExport } =
    useResourceList<AvailabilityException, AvailabilityExceptionQuery>({
        baseUrl: '/availability-exceptions',
        propKey: 'availabilityExceptions',
        query,
        pagination: computed(() => props.availabilityExceptions),
        buildParams: buildAvailabilityExceptionQueryParams,
        applyCriteria,
        exportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Create / edit ────────────────────────────────────────────────────── */
const { visible: formVisible, mode: formMode, entity: formException, openCreate, openEdit } = useFormDialog<AvailabilityException>();

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Date exception updated' : 'Date exception created',
        life: 4000,
    });
}

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; exception: AvailabilityException };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = formatDate(action.exception.date);
    if (action.kind === 'restore') {
        return {
            title: 'Restore date exception',
            message: `Restore the exception for ${name}? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend date exception',
        message: `Suspend the exception for ${name}? It will be soft-deleted and stop overriding the weekly template. You can restore it later.`,
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
                toast.add({
                    severity: 'success',
                    summary: action.kind === 'restore' ? 'Date exception restored' : 'Date exception suspended',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/availability-exceptions/${action.exception.uuid}`, options);
        } else {
            router.post(`/availability-exceptions/${action.exception.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} date ${action.count === 1 ? 'exception' : 'exceptions'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} date ${action.count === 1 ? 'exception' : 'exceptions'}? They will be soft-deleted and stop overriding the weekly template.`,
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
        const uuids = selection.value.map((exception) => exception.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/availability-exceptions/bulk-restore' : '/availability-exceptions/bulk-delete';
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
                        summary: isSuspendedView.value ? 'Selected exceptions restored' : 'Selected exceptions suspended',
                        life: 4000,
                    });
                },
                onFinish: finish,
            },
        );
    });
}

const filterFields: FilterField[] = [
    { key: 'dateRange', label: 'Date between', type: 'date-range', placeholder: 'Start — End' },
    {
        key: 'availability',
        label: 'State',
        type: 'select',
        placeholder: 'Any',
        options: [
            { label: 'Open', value: 'open' },
            { label: 'Closed', value: 'closed' },
        ],
    },
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
        title="Availability Exceptions"
        subtitle="Per-date closures and forced-open overrides"
        permission="VIEW_ANY_AVAILABILITY_EXCEPTIONS"
        fallback-text="You don't have permission to view availability exceptions."
        search-placeholder="Search…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New exception"
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
            <AvailabilityExceptionsTable
                v-model:selection="selection"
                :data="availabilityExceptions.data"
                :total="availabilityExceptions.total"
                :per-page="availabilityExceptions.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(exception: AvailabilityException) => askRow({ kind: 'delete', exception })"
                @restore="(exception: AvailabilityException) => askRow({ kind: 'restore', exception })"
            />
        </template>

        <template #dialogs>
            <AvailabilityExceptionFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :exception="formException"
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
