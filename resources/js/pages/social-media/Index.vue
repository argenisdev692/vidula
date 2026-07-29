<script setup lang="ts">
/**
 * Social Media — AI-generated content packages over a soft-deletable entity
 * with a generation lifecycle (draft/generating/ready/needs_review/published/
 * scheduled). There is no manual create form: "New" opens the AI wizard
 * (`/social-media/create`) — mirrors the Post module's dedicated-page
 * precedent, minus the manual content fields since content is always AI-born.
 *
 * List data arrives as Inertia props (not a separate JSON API), so the table is
 * fed by Inertia partial reloads via {@see useResourceList} — same convention
 * as Posts / Users. Pinia Colada is reserved for the JSON AI-wizard endpoints
 * on Create (YAGNI for the list).
 *
 * The shared list mechanics live in {@see useResourceList}, the confirm
 * dialogs in {@see useConfirmAction}, the page chrome in {@see CrudIndexShell}.
 * Gated by VIEW_ANY_SOCIAL_MEDIA; every mutating control by its own permission.
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
import SocialMediaTable from './components/SocialMediaTable.vue';
import type { PaginatedResponse, SocialMediaContent, SocialMediaContentFilters, SocialMediaContentListStatus, SocialMediaContentQuery } from '@/modules/social-media/types';
import { buildSocialMediaExportUrl, buildSocialMediaQueryParams } from '@/modules/social-media/helpers/buildSocialMediaQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    content: PaginatedResponse<SocialMediaContent>;
    filters: SocialMediaContentFilters;
}>();

const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_SOCIAL_MEDIA'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_SOCIAL_MEDIA'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_SOCIAL_MEDIA'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_SOCIAL_MEDIA'));

/** Remembered across history back/forward — seeded from the server-echoed props. */
const query = useRemember<SocialMediaContentQuery>(
    {
        search: props.filters.search,
        status: props.filters.status,
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
        page: props.content.current_page,
        per_page: props.content.per_page,
    },
    'social-media.index',
);

function applyCriteria(target: SocialMediaContentQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as SocialMediaContentListStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage, openExport } =
    useResourceList<SocialMediaContent, SocialMediaContentQuery>({
        baseUrl: '/social-media',
        propKey: 'content',
        query,
        pagination: computed(() => props.content),
        buildParams: buildSocialMediaQueryParams,
        applyCriteria,
        exportUrl: buildSocialMediaExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Create — dedicated AI wizard page (no modal, no manual form) ────────── */
function openCreate(): void {
    router.visit('/social-media/create');
}

function openEdit(item: SocialMediaContent): void {
    router.visit(`/social-media/${item.uuid}/edit`);
}

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; item: SocialMediaContent };

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
            title: 'Restore content',
            message: `Restore “${title}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend content',
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
            router.delete(`/social-media/${action.item.uuid}`, options);
        } else {
            router.post(`/social-media/${action.item.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} ${action.count === 1 ? 'content package' : 'content packages'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'content package' : 'content packages'}? They will be soft-deleted and hidden from the active list.`,
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
        const url = isSuspendedView.value ? '/social-media/bulk-restore' : '/social-media/bulk-delete';
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
    <Head title="Social Media" />

    <CrudIndexShell
        title="Social Media"
        subtitle="Generate scored, multi-platform content packages with AI"
        permission="VIEW_ANY_SOCIAL_MEDIA"
        fallback-text="You don't have permission to view social media content."
        search-placeholder="Search topic or headline…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New content"
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
            <SocialMediaTable
                v-model:selection="selection"
                :data="content.data"
                :total="content.total"
                :per-page="content.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(item: SocialMediaContent) => askRow({ kind: 'delete', item })"
                @restore="(item: SocialMediaContent) => askRow({ kind: 'restore', item })"
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
