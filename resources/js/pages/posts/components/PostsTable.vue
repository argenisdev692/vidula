<script setup lang="ts">
/**
 * Posts server-side DataTable. Fed by Inertia partial reloads (the parent owns
 * the reactive query + `router.get`), so it never sorts / filters / paginates
 * in the browser. An ACTIVE row (deleted_at === null) shows Edit · Delete; a
 * SUSPENDED row shows Restore (never Edit). Every action is permission-gated.
 * Styling mirrors the Blog Categories table (project's DataTable reference).
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDate } from '@/modules/post/helpers/formatDate';
import type { Post } from '@/modules/post/types';

const props = defineProps<{
    data: Post[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: Post[];
}>();

const emit = defineEmits<{
    edit: [post: Post];
    delete: [post: Post];
    restore: [post: Post];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: Post[]];
}>();

function authorName(row: Post): string {
    const name = [row.user?.first_name, row.user?.last_name].filter(Boolean).join(' ').trim();
    return name || 'System';
}

const STATUS_TONE: Record<string, string> = {
    draft: 'status-pill--draft',
    published: 'status-pill--published',
    scheduled: 'status-pill--scheduled',
};

function scoreTone(score: number | null): string {
    if (score === null) {
        return 'score--muted';
    }
    if (score >= 75) {
        return 'score--good';
    }
    return score >= 50 ? 'score--warn' : 'score--bad';
}

function rowClass(row: Post): string | undefined {
    return row.deleted_at ? 'deleted-row' : undefined;
}
</script>

<template>
    <div class="crud-table-wrap">
        <DataTable
            :value="props.data"
            data-key="uuid"
            lazy
            paginator
            :rows="props.perPage"
            :total-records="props.total"
            :first="props.first"
            :loading="props.loading"
            :row-class="rowClass"
            :selection="props.selection"
            paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            current-page-report-template="{first}–{last} of {totalRecords}"
            @update:selection="(rows: Post[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No posts match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column header="Cover" :pt="{ bodyCell: 'w-20' }">
                <template #body="{ data }">
                    <img
                        v-if="(data as Post).cover_image_url"
                        :src="(data as Post).cover_image_url as string"
                        :alt="(data as Post).post_title"
                        class="thumb"
                    />
                    <span v-else class="thumb thumb--empty" aria-hidden="true">
                        <i class="pi pi-image" />
                    </span>
                </template>
            </Column>

            <Column field="post_title" header="Title">
                <template #body="{ data }">
                    <div class="title-cell">
                        <span class="post-title">{{ (data as Post).post_title }}</span>
                        <i v-if="(data as Post).is_ai_generated" class="pi pi-sparkles ai-badge" v-tooltip.top="'AI-generated'" />
                    </div>
                </template>
            </Column>

            <Column header="Category">
                <template #body="{ data }">
                    <span class="muted">{{ (data as Post).category?.blog_category_name ?? '—' }}</span>
                </template>
            </Column>

            <Column header="Author">
                <template #body="{ data }">
                    <span class="muted">{{ authorName(data as Post) }}</span>
                </template>
            </Column>

            <Column header="Status">
                <template #body="{ data }">
                    <span class="status-pill" :class="STATUS_TONE[(data as Post).post_status]">
                        {{ (data as Post).post_status }}
                    </span>
                </template>
            </Column>

            <Column header="SEO">
                <template #body="{ data }">
                    <span class="score" :class="scoreTone((data as Post).seo_score)">
                        {{ (data as Post).seo_score ?? '—' }}
                    </span>
                </template>
            </Column>

            <Column field="created_at" header="Created">
                <template #body="{ data }">
                    <span class="mono">{{ formatDate((data as Post).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-32' }">
                <template #body="{ data }">
                    <div class="actions-cell">
                        <template v-if="(data as Post).deleted_at">
                            <PermissionGuard permission="RESTORE_POSTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-restore"
                                    aria-label="Restore post"
                                    title="Restore"
                                    v-tooltip.top="'Restore'"
                                    @click="emit('restore', data as Post)"
                                >
                                    <i class="pi pi-check-circle" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard permission="VIEW_POSTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-edit"
                                    aria-label="Edit post"
                                    title="Edit"
                                    v-tooltip.top="'Edit'"
                                    @click="emit('edit', data as Post)"
                                >
                                    <i class="pi pi-pencil" aria-hidden="true" />
                                </button>
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_POSTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-delete"
                                    aria-label="Suspend post"
                                    title="Suspend"
                                    v-tooltip.top="'Suspend'"
                                    @click="emit('delete', data as Post)"
                                >
                                    <i class="pi pi-trash" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                        </template>
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<style scoped>
.thumb {
    width: 44px;
    height: 44px;
    object-fit: cover;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
    display: inline-block;
}

.thumb--empty {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    background: color-mix(in srgb, var(--text-muted) 10%, transparent);
}

.title-cell {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
}

.post-title {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.ai-badge {
    color: var(--accent-primary);
    font-size: 0.85rem;
}

.mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.muted {
    color: var(--text-muted);
    font-size: var(--text-sm);
}

.status-pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: var(--radius-full, 99px);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: capitalize;
}

.status-pill--draft {
    color: var(--accent-warning);
    background: color-mix(in srgb, var(--accent-warning) 14%, transparent);
}

.status-pill--published {
    color: var(--accent-success);
    background: color-mix(in srgb, var(--accent-success) 14%, transparent);
}

.status-pill--scheduled {
    color: var(--accent-info);
    background: color-mix(in srgb, var(--accent-info) 14%, transparent);
}

.score {
    font-family: var(--font-mono, monospace);
    font-weight: var(--font-semibold);
}

.score--good {
    color: var(--accent-success);
}

.score--warn {
    color: var(--accent-warning);
}

.score--bad {
    color: var(--accent-error);
}

.score--muted {
    color: var(--text-muted);
}

/* ── Minimalist transparent CRUD table (matches the Activity Log reference) ── */
.crud-table-wrap :deep(table) {
    background: transparent;
}

.crud-table-wrap :deep(thead th) {
    background: transparent;
    border-bottom: 1px solid var(--border-default);
    color: var(--text-secondary);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: center;
}

.crud-table-wrap :deep(thead th > div) {
    justify-content: center;
}

.crud-table-wrap :deep(tbody tr) {
    background: transparent;
    transition: background var(--transition);
}

.crud-table-wrap :deep(tbody tr:hover) {
    background: color-mix(in srgb, var(--bg-overlay) 40%, transparent);
}

.crud-table-wrap :deep(tbody td) {
    background: transparent;
    border-bottom: 1px solid var(--border-subtle);
    color: var(--text-primary);
    text-align: center;
    vertical-align: middle;
}

.crud-table-wrap :deep(tbody tr.deleted-row) {
    background: var(--deleted-row-bg);
    opacity: var(--deleted-row-opacity, 0.7);
}

.crud-table-wrap :deep(tbody tr.deleted-row td) {
    border-bottom-color: var(--deleted-row-border);
}

.crud-table-wrap :deep([data-pc-name='paginator']),
.crud-table-wrap :deep([data-pc-name='pcpaginator']) {
    background: transparent;
}

.actions-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    white-space: nowrap;
}

.btn-crud-action {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-subtle);
    background: color-mix(in srgb, var(--bg-elevated) 50%, transparent);
    color: var(--text-secondary);
    cursor: pointer;
    transition: transform var(--transition), border-color var(--transition), box-shadow var(--transition);
    overflow: hidden;
}

.btn-crud-action::after {
    content: '';
    position: absolute;
    inset: 0;
    background: currentColor;
    opacity: 0;
    border-radius: inherit;
    transition: opacity var(--transition);
}

.btn-crud-action:hover {
    transform: scale(1.15);
    border-color: currentColor;
}

.btn-crud-action:hover::after {
    opacity: 0.1;
}

.btn-crud-action:active {
    transform: scale(0.95);
}

.btn-crud-action:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.btn-crud-action .pi {
    position: relative;
    z-index: 1;
    font-size: 0.8rem;
}

.btn-crud-action-edit {
    color: var(--accent-primary);
}

.btn-crud-action-edit:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-primary) 30%, transparent);
}

.btn-crud-action-delete {
    color: var(--accent-error);
}

.btn-crud-action-delete:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-error) 30%, transparent);
}

.btn-crud-action-restore {
    color: var(--accent-success);
}

.btn-crud-action-restore:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-success) 30%, transparent);
}

.table-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-10) var(--space-6);
    color: var(--text-muted);
}

.table-empty .pi {
    font-size: var(--text-2xl);
}
</style>
