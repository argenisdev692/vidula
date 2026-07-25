<script setup lang="ts">
/**
 * CVs — upload CRUD for PDF/Markdown resumes (niche fullstack | other).
 * Shared list mechanics: useResourceList / useConfirmAction / CrudIndexShell.
 * Create & edit happen in a dialog (store/update return back()).
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
import CvsTable from './components/CvsTable.vue';
import CvFormDialog from './components/CvFormDialog.vue';
import type { Cv, CvFilters, CvNiche, CvQuery, CvSoftStatus, PaginatedResponse } from '@/modules/cvs/types';
import { buildCvExportUrl, buildCvQueryParams } from '@/modules/cvs/helpers/buildCvQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    cvs: PaginatedResponse<Cv>;
    filters: CvFilters;
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_CVS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_CVS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_CVS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_CVS'));

const query = reactive<CvQuery>({
    search: props.filters.search,
    status: props.filters.status,
    niche: props.filters.niche,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.cvs.current_page,
    per_page: props.cvs.per_page,
});

function applyCriteria(target: CvQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as CvSoftStatus | undefined) || null;
    target.niche = (criteria.niche as CvNiche | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage, openExport } =
    useResourceList<Cv, CvQuery>({
        baseUrl: '/cvs',
        propKey: 'cvs',
        query,
        pagination: computed(() => props.cvs),
        buildParams: buildCvQueryParams,
        applyCriteria,
        exportUrl: buildCvExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

const { visible: formVisible, mode: formMode, entity: formCv, openCreate, openEdit } = useFormDialog<Cv>();

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'CV updated' : 'CV uploaded',
        life: 4000,
    });
}

type RowAction = { kind: 'delete' | 'restore'; cv: Cv };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = action.cv.title ?? 'this CV';
    if (action.kind === 'restore') {
        return {
            title: 'Restore CV',
            message: `Restore “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend CV',
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
                toast.add({
                    severity: 'success',
                    summary: action.kind === 'restore' ? 'CV restored' : 'CV suspended',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/cvs/${action.cv.uuid}`, options);
        } else {
            router.post(`/cvs/${action.cv.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} ${action.count === 1 ? 'CV' : 'CVs'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'CV' : 'CVs'}? They will be soft-deleted and hidden from the active list.`,
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
        const uuids = selection.value.map((cv) => cv.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/cvs/bulk-restore' : '/cvs/bulk-delete';
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
                        summary: isSuspendedView.value ? 'Selected CVs restored' : 'Selected CVs suspended',
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
    {
        key: 'niche',
        label: 'Niche',
        type: 'select',
        placeholder: 'All niches',
        options: [
            { label: 'Fullstack', value: 'fullstack' },
            { label: 'Other', value: 'other' },
        ],
    },
];
</script>

<template>
    <CrudIndexShell
        title="CVs"
        subtitle="Upload PDF or Markdown resumes for ATS optimization later"
        permission="VIEW_ANY_CVS"
        fallback-text="You don't have permission to view CVs."
        search-placeholder="Search title, filename, niche…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="Upload CV"
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
            <CvsTable
                v-model:selection="selection"
                :data="cvs.data"
                :total="cvs.total"
                :per-page="cvs.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(cv: Cv) => askRow({ kind: 'delete', cv })"
                @restore="(cv: Cv) => askRow({ kind: 'restore', cv })"
            />
        </template>

        <template #dialogs>
            <CvFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :cv="formCv"
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
