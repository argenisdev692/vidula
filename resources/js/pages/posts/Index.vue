<script setup lang="ts">
/**
 * Posts — blog content management over a soft-deletable entity with a
 * draft/published/scheduled lifecycle. Create & edit are dedicated pages (not
 * a modal) since the content field is long-form and pairs with the AI assist
 * panel — mirrors the Users module precedent rather than blog-categories'
 * dialog pattern.
 *
 * The shared list mechanics live in {@see useResourceList}, the confirm
 * dialogs in {@see useConfirmAction}, the page chrome in {@see CrudIndexShell}.
 * Gated by VIEW_ANY_POSTS; every mutating control by its own permission.
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
import { toLocalIsoDate } from '@/lib/date';
import PostsTable from './components/PostsTable.vue';
import type { CategoryOption, PaginatedResponse, Post, PostFilters, PostListStatus, PostQuery } from '@/modules/post/types';
import { buildPostExportUrl, buildPostQueryParams } from '@/modules/post/helpers/buildPostQueryParams';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    posts: PaginatedResponse<Post>;
    filters: PostFilters;
    categories: CategoryOption[];
}>();

const { hasPermission } = useAuthorization();

const canCreate = computed<boolean>(() => hasPermission('CREATE_POSTS'));
const canExport = computed<boolean>(() => hasPermission('EXPORT_POSTS'));
const canBulkDelete = computed<boolean>(() => hasPermission('BULK_DELETE_POSTS'));
const canBulkRestore = computed<boolean>(() => hasPermission('BULK_RESTORE_POSTS'));

/** The reactive request state — seeded once from the server-echoed props. */
const query = reactive<PostQuery>({
    search: props.filters.search,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    category_uuid: props.filters.category_uuid,
    page: props.posts.current_page,
    per_page: props.posts.per_page,
});

function applyCriteria(target: PostQuery, criteria: FilterCriteria): void {
    target.search = criteria.search || null;
    target.status = (criteria.status as PostListStatus | undefined) || null;
    target.category_uuid = (criteria.category as string | undefined) || null;

    const range = criteria.dateRange as Date[] | undefined;
    target.date_from = range?.[0] ? toLocalIsoDate(range[0]) : null;
    target.date_to = range?.[1] ? toLocalIsoDate(range[1]) : null;
}

const { loading, selection, firstRecord, recordLabel, isSuspendedView, resetSelection, onFilters, onPage, openExport } =
    useResourceList<Post, PostQuery>({
        baseUrl: '/posts',
        propKey: 'posts',
        query,
        pagination: computed(() => props.posts),
        buildParams: buildPostQueryParams,
        applyCriteria,
        exportUrl: buildPostExportUrl,
    });

const canBulkAct = computed<boolean>(() => (isSuspendedView.value ? canBulkRestore.value : canBulkDelete.value));

/* ── Create / edit — dedicated pages (no modal) ───────────────────────── */
function openCreate(): void {
    router.visit('/posts/create');
}

function openEdit(post: Post): void {
    router.visit(`/posts/${post.uuid}/edit`);
}

/* ── Single-row suspend / restore ─────────────────────────────────────── */
type RowAction = { kind: 'delete' | 'restore'; post: Post };

const {
    visible: rowVisible,
    loading: rowLoading,
    confirm: rowConfirm,
    ask: askRow,
    run: runRow,
} = useConfirmAction<RowAction>((action) => {
    const title = action.post.post_title;
    if (action.kind === 'restore') {
        return {
            title: 'Restore post',
            message: `Restore “${title}”? It will become active again.`,
            confirmLabel: 'Restore',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend post',
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
            router.delete(`/posts/${action.post.uuid}`, options);
        } else {
            router.post(`/posts/${action.post.uuid}/restore`, {}, options);
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
            message: `Restore ${action.count} ${action.count === 1 ? 'post' : 'posts'}? They will become active again.`,
            confirmLabel: 'Restore all',
            confirmIcon: 'pi pi-check-circle',
            tone: 'success',
        };
    }
    return {
        title: 'Suspend selected',
        message: `Suspend ${action.count} ${action.count === 1 ? 'post' : 'posts'}? They will be soft-deleted and hidden from the active list.`,
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
        const uuids = selection.value.map((post) => post.uuid);
        if (uuids.length === 0) {
            finish();
            return;
        }
        const url = isSuspendedView.value ? '/posts/bulk-restore' : '/posts/bulk-delete';
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
            { label: 'Published', value: 'published' },
            { label: 'Scheduled', value: 'scheduled' },
            { label: 'Suspended', value: 'suspended' },
        ],
    },
    {
        key: 'category',
        label: 'Category',
        type: 'select',
        placeholder: 'All categories',
        options: props.categories,
    },
]);
</script>

<template>
    <CrudIndexShell
        title="Posts"
        subtitle="Write manually or generate SEO-scored drafts with AI"
        permission="VIEW_ANY_POSTS"
        fallback-text="You don't have permission to view posts."
        search-placeholder="Search title or excerpt…"
        :fields="filterFields"
        :can-export="canExport"
        :can-create="canCreate"
        create-label="New post"
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
            <PostsTable
                v-model:selection="selection"
                :data="posts.data"
                :total="posts.total"
                :per-page="posts.per_page"
                :first="firstRecord"
                :loading="loading"
                @page="onPage"
                @edit="openEdit"
                @delete="(post: Post) => askRow({ kind: 'delete', post })"
                @restore="(post: Post) => askRow({ kind: 'restore', post })"
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
