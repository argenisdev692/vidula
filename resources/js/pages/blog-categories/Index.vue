<script setup lang="ts">
/**
 * Blog Categories — full CRUD over a soft-deletable entity with an optional
 * cover image.
 *
 * The shared list mechanics (Inertia partial reloads, selection, filter / page /
 * export plumbing) live in {@see useResourceList}; the confirm-dialog state in
 * {@see useConfirmAction}; the page chrome in {@see CrudIndexShell}. This file
 * keeps only what is specific to blog categories: its query shape, filter fields,
 * confirm copy and toasts. There are no create/edit page routes — store/update
 * return `back()` redirects and accept a multipart image — so create & edit
 * happen in a Volt Dialog. Gated by VIEW_ANY_BLOG_CATEGORIES; every mutating
 * control by its own permission.
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
import BlogCategoriesTable from './components/BlogCategoriesTable.vue';
import BlogCategoryFormDialog from './components/BlogCategoryFormDialog.vue';
import type { BlogCategory, BlogCategoryFilters, BlogCategoryQuery, BlogCategoryStatus, PaginatedResponse } from '@/modules/blog/types';
import { buildBlogCategoryExportUrl, buildBlogCategoryQueryParams } from '@/modules/blog/helpers/buildBlogCategoryQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    blogCategories: PaginatedResponse<BlogCategory>;
    filters: BlogCategoryFilters;
}>();

const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_BLOG_CATEGORIES'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_BLOG_CATEGORIES'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_BLOG_CATEGORIES'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_BLOG_CATEGORIES'));

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<BlogCategoryQuery>({
    search: props.filters.search,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.blogCategories.current_page,
    per_page: props.blogCategories.per_page,
});

function applyCriteria(target: BlogCategoryQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as BlogCategoryStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage, openExport } =
    useResourceList<BlogCategory, BlogCategoryQuery>({
        baseUrl: '/blog-categories',
        propKey: 'blogCategories',
        query,
        pagination: computed(() => props.blogCategories),
        buildParams: buildBlogCategoryQueryParams,
        applyCriteria,
        exportUrl: buildBlogCategoryExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Create / edit ────────────────────────────────────────────────────── */
const { visible: formVisible, mode: formMode, entity: formCategory, openCreate, openEdit } = useFormDialog<BlogCategory>();

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Blog category updated' : 'Blog category created',
        life: 4000,
    });
}

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; category: BlogCategory };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const name = action.category.blog_category_name ?? 'this category';
    if (action.kind === 'restore') {
        return {
            title: 'Restore blog category',
            message: `Restore “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend blog category',
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
                    summary: action.kind === 'restore' ? 'Blog category restored' : 'Blog category suspended',
                    life: 4000,
                });
            },
            onFinish: finish,
        };
        if (action.kind === 'delete') {
            router.delete(`/blog-categories/${action.category.uuid}`, options);
        } else {
            router.post(`/blog-categories/${action.category.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} blog ${action.count === 1 ? 'category' : 'categories'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} blog ${action.count === 1 ? 'category' : 'categories'}? They will be soft-deleted and hidden from the active list.`,
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
        const uuids = selection.value.map((category) => category.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/blog-categories/bulk-restore' : '/blog-categories/bulk-delete';
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
                        summary: isSuspendedView.value ? 'Selected categories restored' : 'Selected categories suspended',
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
        title="Blog Categories"
        subtitle="Organise blog posts into reusable, searchable categories"
        permission="VIEW_ANY_BLOG_CATEGORIES"
        fallback-text="You don't have permission to view blog categories."
        search-placeholder="Search name or description…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New category"
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
            <BlogCategoriesTable
                v-model:selection="selection"
                :data="blogCategories.data"
                :total="blogCategories.total"
                :per-page="blogCategories.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(category: BlogCategory) => askRow({ kind: 'delete', category })"
                @restore="(category: BlogCategory) => askRow({ kind: 'restore', category })"
            />
        </template>

        <template #dialogs>
            <BlogCategoryFormDialog
                v-model:visible="formVisible"
                :mode="formMode"
                :category="formCategory"
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
