<script setup lang="ts">
/**
 * Campaigns — AI-generated, quality-scored Meta Ads packages over a
 * soft-deletable entity with a generation lifecycle (draft/generating/ready/
 * needs_review/published/scheduled). There is no manual create form: "New"
 * opens the AI wizard (`/campaigns/create`) — mirrors the Social Media
 * module's dedicated-page precedent.
 *
 * List data arrives as Inertia props (not a separate JSON API), so the table is
 * fed by Inertia partial reloads via {@see useResourceList} — same convention
 * as Social Media / Posts. Pinia Colada is reserved for the JSON AI-wizard
 * endpoints on Create (YAGNI for the list).
 *
 * The shared list mechanics live in {@see useResourceList}, the confirm
 * dialogs in {@see useConfirmAction}, the page chrome in {@see CrudIndexShell}.
 * Gated by VIEW_ANY_CAMPAIGNS; every mutating control by its own permission.
 */
import { computed } from 'vue';
import { Head, router, useRemember } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import type { FilterCriteria, FilterField } from '@/common/data-table/AdvancedFilter.vue';
import CrudIndexShell from '@/common/data-table/CrudIndexShell.vue';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import { useResourceList } from '@/common/data-table/useResourceList';
import { useConfirmAction } from '@/common/data-table/useConfirmAction';
import { toLocalIsoDate } from '@/lib/date';
import CampaignsTable from './components/CampaignsTable.vue';
import type { Campaign, CampaignFilters, CampaignListStatus, CampaignQuery, PaginatedResponse } from '@/modules/campaigns/types';
import { buildCampaignExportUrl, buildCampaignQueryParams } from '@/modules/campaigns/helpers/buildCampaignQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    campaigns: PaginatedResponse<Campaign>;
    filters: CampaignFilters;
}>();

const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_CAMPAIGNS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_CAMPAIGNS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_CAMPAIGNS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_CAMPAIGNS'));

/** Remembered across history back/forward — seeded from the server-echoed props. */
const query = useRemember<CampaignQuery>(
    {
        search: props.filters.search,
        status: props.filters.status,
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
        page: props.campaigns.current_page,
        per_page: props.campaigns.per_page,
    },
    'campaigns.index',
);

function applyCriteria(target: CampaignQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as CampaignListStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage, openExport } =
    useResourceList<Campaign, CampaignQuery>({
        baseUrl: '/campaigns',
        propKey: 'campaigns',
        query,
        pagination: computed(() => props.campaigns),
        buildParams: buildCampaignQueryParams,
        applyCriteria,
        exportUrl: buildCampaignExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Create — dedicated AI wizard page (no modal, no manual form) ────────── */
function openCreate(): void {
    router.visit('/campaigns/create');
}

function openEdit(item: Campaign): void {
    router.visit(`/campaigns/${item.uuid}/edit`);
}

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; item: Campaign };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const title = action.item.topic;
    if (action.kind === 'restore') {
        return {
            title: 'Restore campaign',
            message: `Restore “${title}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend campaign',
        message: `Suspend “${title}”? It will be soft-deleted and hidden from the active list. You can restore it later.`,
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
            router.delete(`/campaigns/${action.item.uuid}`, options);
        } else {
            router.post(`/campaigns/${action.item.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} ${action.count === 1 ? 'campaign' : 'campaigns'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'campaign' : 'campaigns'}? They will be soft-deleted and hidden from the active list.`,
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
        const uuids = selection.value.map((item) => item.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/campaigns/bulk-restore' : '/campaigns/bulk-delete';
        router.post(url, { uuids }, { preserveScroll: true, preserveState: true, onSuccess: resetSelection, onFinish: finish });
    });
}

const filterFields = computed<FilterField[]>(() => [
    { key: 'dateRange', label: 'Created between', type: 'date-range', placeholder: 'Start — End' },
    {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'All',
        options: [
            { label: 'Draft', value: 'draft' },
            { label: 'Generating', value: 'generating' },
            { label: 'Ready', value: 'ready' },
            { label: 'Needs review', value: 'needs_review' },
            { label: 'Published', value: 'published' },
            { label: 'Scheduled', value: 'scheduled' },
            { label: 'Suspended', value: 'suspended' },
        ],
    },
]);
</script>

<template>
    <Head title="Campaigns" />

    <CrudIndexShell
        title="Campaigns"
        subtitle="Generate scored Meta Ads campaigns with AI"
        permission="VIEW_ANY_CAMPAIGNS"
        fallback-text="You don't have permission to view campaigns."
        search-placeholder="Search topic or headline…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New campaign"
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
            <CampaignsTable
                v-model:selection="selection"
                :data="campaigns.data"
                :total="campaigns.total"
                :per-page="campaigns.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(item: Campaign) => askRow({ kind: 'delete', item })"
                @restore="(item: Campaign) => askRow({ kind: 'restore', item })"
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
