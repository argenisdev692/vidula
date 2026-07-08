<script setup lang="ts">
/**
 * Blog Categories — full CRUD over a soft-deletable entity with an optional
 * cover image.
 *
 * Like the sibling Activity Log / Company Data screens, the list is driven by
 * Inertia partial reloads (`router.get` with `only: ['blogCategories','filters']`
 * on every filter / page change) rather than a separate JSON API. There are no
 * create/edit page routes — the backend store/update return `back()` redirects
 * and accept a multipart image — so create & edit happen in a Volt Dialog, and
 * delete / restore / bulk go through the reusable ConfirmDialog. The whole page
 * is gated by VIEW_ANY_BLOG_CATEGORIES; every mutating control by its own
 * permission (CREATE / UPDATE / DELETE / RESTORE / BULK_*).
 */
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import type { DataTablePageEvent } from 'primevue/datatable';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { useAuthorization } from '@/modules/auth/composables/useAuthorization';
import AdvancedFilter, { type FilterCriteria, type FilterField } from '@/common/data-table/AdvancedFilter.vue';
import ConfirmDialog from '@/common/data-table/ConfirmDialog.vue';
import Button from '@/volt/Button.vue';
import BlogCategoriesTable from './components/BlogCategoriesTable.vue';
import BlogCategoryFormDialog from './components/BlogCategoryFormDialog.vue';
import type { SharedProps } from '@/types/inertia';
import type { BlogCategory, BlogCategoryFilters, BlogCategoryQuery, BlogCategoryStatus, PaginatedResponse } from '@/modules/blog/types';
import {
    buildBlogCategoryExportUrl,
    buildBlogCategoryQueryParams,
    type BlogCategoryExportFormat,
} from '@/modules/blog/helpers/buildBlogCategoryQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    blogCategories: PaginatedResponse<BlogCategory>;
    filters: BlogCategoryFilters;
}>();

usePage<SharedProps>();
const toast = useToast();
const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_BLOG_CATEGORIES'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_BLOG_CATEGORIES'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_BLOG_CATEGORIES'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_BLOG_CATEGORIES'));

const loading = ref<boolean>(false);
const selection = ref<BlogCategory[]>([]);

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<BlogCategoryQuery>({
    search: props.filters.search,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    page: props.blogCategories.current_page,
    per_page: props.blogCategories.per_page,
});

const firstRecord = computed<number>(() => (props.blogCategories.current_page - 1) * props.blogCategories.per_page);
const recordLabel = computed<string>(
    () => `${props.blogCategories.total} ${props.blogCategories.total === 1 ? 'record' : 'records'} found`,
);

/** The current list is homogeneous: suspended view ⇒ restore, otherwise delete. */
const isSuspendedView = computed<boolean>(() => query.status === 'suspended');
const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

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

function toIsoDate(date: Date): string {
    return date.toISOString().slice(0, 10);
}

function reload(): void {
    loading.value = true;
    router.get('/blog-categories', buildBlogCategoryQueryParams(query), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['blogCategories', 'filters'],
        onFinish: () => {
            loading.value = false;
        },
    });
}

function onFilters(criteria: FilterCriteria): void {
    query.search = criteria.search || null;
    query.status = (criteria.status as BlogCategoryStatus | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    query.date_from = range?.[0] ? toIsoDate(range[0]) : null;
    query.date_to = range?.[1] ? toIsoDate(range[1]) : null;

    query.page = 1;
    selection.value = [];
    reload();
}

function onPage(event: DataTablePageEvent): void {
    query.page = event.page + 1;
    query.per_page = event.rows;
    reload();
}

function openExport(format: BlogCategoryExportFormat): void {
    window.location.href = buildBlogCategoryExportUrl(query, format);
}

/* ── Create / edit ────────────────────────────────────────────────────── */
const formVisible = ref<boolean>(false);
const formMode = ref<'create' | 'edit'>('create');
const formCategory = ref<BlogCategory | null>(null);

function openCreate(): void {
    formMode.value = 'create';
    formCategory.value = null;
    formVisible.value = true;
}

function openEdit(category: BlogCategory): void {
    formMode.value = 'edit';
    formCategory.value = category;
    formVisible.value = true;
}

function onSaved(): void {
    toast.add({
        severity: 'success',
        summary: formMode.value === 'edit' ? 'Blog category updated' : 'Blog category created',
        life: 4000,
    });
}

/* ── Single-row delete / restore ──────────────────────────────────────── */
const rowAction = ref<{ kind: 'delete' | 'restore'; category: BlogCategory } | null>(null);
const rowActionVisible = ref<boolean>(false);
const rowActionLoading = ref<boolean>(false);

const rowConfirm = computed(() => {
    const name = rowAction.value?.category.blog_category_name ?? 'this category';
    if (rowAction.value?.kind === 'restore') {
        return {
            title: 'Restore blog category',
            message: `Restore “${name}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    return {
        title: 'Suspend blog category',
        message: `Suspend “${name}”? It will be soft-deleted and hidden from the active list. You can restore it later.`,
        confirmLabel: 'Suspend',
        confirmIcon: 'pi pi-trash',
        tone: 'danger' as const,
    };
});

function askDelete(category: BlogCategory): void {
    rowAction.value = { kind: 'delete', category };
    rowActionVisible.value = true;
}

function askRestore(category: BlogCategory): void {
    rowAction.value = { kind: 'restore', category };
    rowActionVisible.value = true;
}

function confirmRowAction(): void {
    const action = rowAction.value;
    if (!action) {
        return;
    }
    rowActionLoading.value = true;

    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selection.value = [];
            toast.add({
                severity: 'success',
                summary: action.kind === 'restore' ? 'Blog category restored' : 'Blog category suspended',
                life: 4000,
            });
        },
        onFinish: () => {
            rowActionLoading.value = false;
            rowActionVisible.value = false;
        },
    };

    if (action.kind === 'delete') {
        router.delete(`/blog-categories/${action.category.uuid}`, options);
    } else {
        router.post(`/blog-categories/${action.category.uuid}/restore`, {}, options);
    }
}

/* ── Bulk suspend / restore ───────────────────────────────────────────── */
const bulkVisible = ref<boolean>(false);
const bulkLoading = ref<boolean>(false);

const bulkConfirm = computed(() => {
    const count = selection.value.length;
    if (isSuspendedView.value) {
        return {
            title: 'Restore selected',
            message: `Restore ${count} blog ${count === 1 ? 'category' : 'categories'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success' as const,
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${count} blog ${count === 1 ? 'category' : 'categories'}? They will be soft-deleted and hidden from the active list.`,
        confirmLabel: 'Suspend all',
        confirmIcon: 'pi pi-trash',
        tone: 'danger' as const,
    };
});

function askBulk(): void {
    if (selection.value.length > 0) {
        bulkVisible.value = true;
    }
}

function confirmBulk(): void {
    const uuids = selection.value.map((c) => c.uuid);
    if (uuids.length === 0) {
        return;
    }
    bulkLoading.value = true;

    const url = isSuspendedView.value ? '/blog-categories/bulk-restore' : '/blog-categories/bulk-delete';
    router.post(
        url,
        { uuids },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                selection.value = [];
                toast.add({
                    severity: 'success',
                    summary: isSuspendedView.value ? 'Selected categories restored' : 'Selected categories suspended',
                    life: 4000,
                });
            },
            onFinish: () => {
                bulkLoading.value = false;
                bulkVisible.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Blog Categories" />

    <AppHeader title="Blog Categories" subtitle="Organise blog posts into reusable, searchable categories" />

    <PermissionGuard permission="VIEW_ANY_BLOG_CATEGORIES">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view blog categories.</p>
            </div>
        </template>

        <div class="page">
            <AdvancedFilter
                search-placeholder="Search name or description…"
                :fields="filterFields"
                :show-export-pdf="canExport"
                :show-export-excel="canExport"
                :show-export-csv="canExport"
                :show-create="canCreate"
                create-label="New category"
                @filters-change="onFilters"
                @create="openCreate"
                @export-pdf="openExport('pdf')"
                @export-excel="openExport('xlsx')"
                @export-csv="openExport('csv')"
            />

            <div class="toolbar">
                <p class="counter">{{ recordLabel }}</p>

                <Transition name="fade">
                    <div v-if="selection.length > 0 && canBulkAct" class="bulk-bar">
                        <span class="bulk-bar__count">{{ selection.length }} selected</span>
                        <Button
                            size="small"
                            :label="isSuspendedView ? 'Restore selected' : 'Suspend selected'"
                            :icon="isSuspendedView ? 'pi pi-check-circle' : 'pi pi-trash'"
                            outlined
                            @click="askBulk"
                        />
                    </div>
                </Transition>
            </div>

            <BlogCategoriesTable
                v-model:selection="selection"
                :data="blogCategories.data"
                :total="blogCategories.total"
                :per-page="blogCategories.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="askDelete"
                @restore="askRestore"
            />
        </div>
    </PermissionGuard>

    <BlogCategoryFormDialog
        v-model:visible="formVisible"
        :mode="formMode"
        :category="formCategory"
        @saved="onSaved"
    />

    <ConfirmDialog
        v-model:visible="rowActionVisible"
        :title="rowConfirm.title"
        :message="rowConfirm.message"
        :confirm-label="rowConfirm.confirmLabel"
        :confirm-icon="rowConfirm.confirmIcon"
        :tone="rowConfirm.tone"
        :loading="rowActionLoading"
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

<style scoped>
.page {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    flex-wrap: wrap;
}

.counter {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.bulk-bar {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.bulk-bar__count {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity var(--transition), transform var(--transition);
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-16) var(--space-6);
    color: var(--text-muted);
}

.empty .pi {
    font-size: var(--text-3xl);
}
</style>
