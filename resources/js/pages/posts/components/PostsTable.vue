<script setup lang="ts">
/**
 * Posts server-side DataTable. Fed by Inertia partial reloads (the parent owns
 * the reactive query + `router.get`), so it never sorts / filters / paginates
 * in the browser. An ACTIVE row (deleted_at === null) shows View · Edit · Delete;
 * a SUSPENDED row shows View · Restore (never Edit). Every action is
 * permission-gated. Action pills use shared {@see ActionButton} (FRONTEND §7 /
 * §11 — no hover scale > 1.04).
 */
import type { DataTablePageEvent, DataTableSortEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import ActionButton from '@/common/data-table/ActionButton.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDateShort } from '@/modules/post/helpers/formatDate';
import type { Post } from '@/modules/post/types';

const props = withDefaults(
    defineProps<{
        data: Post[];
        total: number;
        perPage: number;
        first: number;
        loading: boolean;
        selection: Post[];
        sortField?: string;
        sortOrder?: number;
    }>(),
    { sortField: 'created_at', sortOrder: -1 },
);

const emit = defineEmits<{
    edit: [post: Post];
    delete: [post: Post];
    restore: [post: Post];
    page: [event: DataTablePageEvent];
    sort: [event: DataTableSortEvent];
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
            removable-sort
            :rows="props.perPage"
            :total-records="props.total"
            :first="props.first"
            :loading="props.loading"
            :row-class="rowClass"
            :selection="props.selection"
            :sort-field="props.sortField"
            :sort-order="props.sortOrder"
            paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            current-page-report-template="{first}–{last} of {totalRecords}"
            @update:selection="(rows: Post[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
            @sort="(event: DataTableSortEvent) => emit('sort', event)"
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

            <Column field="post_title" header="Title" sortable>
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

            <Column field="post_status" header="Status" sortable>
                <template #body="{ data }">
                    <span class="status-pill" :class="STATUS_TONE[(data as Post).post_status]">
                        {{ (data as Post).post_status }}
                    </span>
                </template>
            </Column>

            <Column field="seo_score" header="SEO" sortable>
                <template #body="{ data }">
                    <span class="score" :class="scoreTone((data as Post).seo_score)">
                        {{ (data as Post).seo_score ?? '—' }}
                    </span>
                </template>
            </Column>

            <Column field="created_at" header="Created" sortable>
                <template #body="{ data }">
                    <span class="mono">{{ formatDateShort((data as Post).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-36' }">
                <template #body="{ data }">
                    <div class="actions-cell">
                        <PermissionGuard permission="VIEW_POSTS">
                            <ActionButton
                                icon="pi pi-eye"
                                tone="view"
                                label="View post"
                                :href="`/posts/${(data as Post).uuid}/edit`"
                            />
                        </PermissionGuard>

                        <template v-if="(data as Post).deleted_at">
                            <PermissionGuard permission="RESTORE_POSTS">
                                <ActionButton
                                    icon="pi pi-check-circle"
                                    tone="restore"
                                    label="Restore post"
                                    @click="emit('restore', data as Post)"
                                />
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard permission="UPDATE_POSTS">
                                <ActionButton
                                    icon="pi pi-pencil"
                                    tone="edit"
                                    label="Edit post"
                                    @click="emit('edit', data as Post)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_POSTS">
                                <ActionButton
                                    icon="pi pi-trash"
                                    tone="delete"
                                    label="Suspend post"
                                    @click="emit('delete', data as Post)"
                                />
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
