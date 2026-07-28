<script setup lang="ts">
/**
 * Portfolio — full CRUD over a soft-deletable project catalog (the gallery
 * shown on the landing page's portfolio section).
 *
 * Shared list mechanics live in useResourceList / useConfirmAction /
 * CrudIndexShell. Create & edit happen in a dialog (store/update return back()).
 * Gallery image management lives on the Show page. Export is wired
 * (PORTFOLIOS includes EXPORT in RolePermissionSeeder).
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
import PortfoliosTable from './components/PortfoliosTable.vue';
import PortfolioFormDialog from './components/PortfolioFormDialog.vue';
import type { PaginatedResponse, Portfolio, PortfolioFilters, PortfolioQuery, PortfolioStatus } from '@/modules/portfolio/types';
import { buildPortfolioExportUrl, buildPortfolioQueryParams } from '@/modules/portfolio/helpers/buildPortfolioQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    portfolios: PaginatedResponse<Portfolio>;
    filters: PortfolioFilters;
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_PORTFOLIOS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_PORTFOLIOS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_PORTFOLIOS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_PORTFOLIOS'));

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<PortfolioQuery>({
    search: props.filters.search,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.portfolios.current_page,
    per_page: props.portfolios.per_page,
});

function applyCriteria(target: PortfolioQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as PortfolioStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage, openExport } =
    useResourceList<Portfolio, PortfolioQuery>({
        baseUrl: '/portfolios',
        propKey: 'portfolios',
        query,
        pagination: computed(() => props.portfolios),
        buildParams: buildPortfolioQueryParams,
        applyCriteria,
        exportUrl: buildPortfolioExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Create / edit ────────────────────────────────────────────────────── */
const { visible: formVisible, mode: formMode, entity: formPortfolio, openCreate, openEdit } = useFormDialog<Portfolio>();

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Portfolio project updated' : 'Portfolio project created',
        life: 4000,
    });
}

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; portfolio: Portfolio };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const title = action.portfolio.title ?? 'this project';
    if (action.kind === 'restore') {
        return {
            title: 'Restore portfolio project',
            message: `Restore “${title}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend portfolio project',
        message: `Suspend “${title}”? It will be soft-deleted and hidden from the gallery. You can restore it later.`,
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
                    summary: action.kind === 'restore' ? 'Portfolio project restored' : 'Portfolio project suspended',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/portfolios/${action.portfolio.uuid}`, options);
        } else {
            router.post(`/portfolios/${action.portfolio.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} ${action.count === 1 ? 'project' : 'projects'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'project' : 'projects'}? They will be soft-deleted and hidden from the gallery.`,
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
        const uuids = selection.value.map((portfolio) => portfolio.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/portfolios/bulk-restore' : '/portfolios/bulk-delete';
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
                        summary: isSuspendedView.value ? 'Selected projects restored' : 'Selected projects suspended',
                        life: 4000,
                    });
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
];
</script>

<template>
    <CrudIndexShell
        title="Portfolio"
        subtitle="Manage the project gallery showcased on the landing page"
        permission="VIEW_ANY_PORTFOLIOS"
        fallback-text="You don't have permission to view portfolio projects."
        search-placeholder="Search title or client…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New project"
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
            <PortfoliosTable
                v-model:selection="selection"
                :data="portfolios.data"
                :total="portfolios.total"
                :per-page="portfolios.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(portfolio: Portfolio) => askRow({ kind: 'delete', portfolio })"
                @restore="(portfolio: Portfolio) => askRow({ kind: 'restore', portfolio })"
            />
        </template>

        <template #dialogs>
            <PortfolioFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :portfolio="formPortfolio"
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
