<script setup lang="ts">
/**
 * Portfolio server-side DataTable. Fed by Inertia partial reloads (the parent
 * owns the reactive query + `router.get`), so it never sorts / filters /
 * paginates in the browser. Rows are homogeneous per the `status` filter: an
 * ACTIVE row (deleted_at === null) shows View · Edit · Delete; a SUSPENDED row
 * shows View · Restore (never Edit). Every action is permission-gated.
 *
 * `is_public` (the landing-page gallery visibility flag) is unrelated to the
 * soft-delete status above and rendered as its own badge column, alongside the
 * gallery photo count (`gallery_count`, from `withCount('gallery')`).
 *
 * Transparent-grid + action-pill styling mirrors the Services / Blog Categories
 * tables (the project's server-side DataTable reference).
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import Image from 'primevue/image';
import DataTable from '@/volt/DataTable.vue';
import Tag from '@/volt/Tag.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import ActionButton from '@/common/data-table/ActionButton.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { imagePreviewPt } from '@/common/media/imagePreviewPt';
import { formatDateShort } from '@/modules/portfolio/helpers/formatDate';
import type { Portfolio } from '@/modules/portfolio/types';

const props = defineProps<{
    data: Portfolio[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: Portfolio[];
}>();

const emit = defineEmits<{
    edit: [portfolio: Portfolio];
    delete: [portfolio: Portfolio];
    restore: [portfolio: Portfolio];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: Portfolio[]];
}>();

function authorName(row: Portfolio): string {
    const name = [row.user?.first_name, row.user?.last_name].filter(Boolean).join(' ').trim();
    return name || 'System';
}

function rowClass(row: Portfolio): string | undefined {
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
            @update:selection="(rows: Portfolio[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-images" aria-hidden="true" />
                    <span>No portfolio projects match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column header="Cover" header-style="width: 4.5rem">
                <template #body="{ data }">
                    <Image
                        v-if="(data as Portfolio).cover_url"
                        :src="(data as Portfolio).cover_url as string"
                        :alt="`${(data as Portfolio).title} cover`"
                        preview
                        :pt="imagePreviewPt"
                        image-class="pf-cover-thumb"
                    />
                    <span v-else class="pf-cover-thumb pf-cover-thumb--empty" aria-hidden="true">
                        <i class="pi pi-image" />
                    </span>
                </template>
            </Column>

            <Column field="title" header="Title">
                <template #body="{ data }">
                    <span class="pf-title">{{ (data as Portfolio).title }}</span>
                </template>
            </Column>

            <Column field="client_name" header="Client">
                <template #body="{ data }">
                    <span>{{ (data as Portfolio).client_name }}</span>
                </template>
            </Column>

            <Column field="project_type" header="Type">
                <template #body="{ data }">
                    <Tag :value="(data as Portfolio).project_type" severity="info" />
                </template>
            </Column>

            <Column header="Stack">
                <template #body="{ data }">
                    <div class="pf-stack">
                        <Tag
                            v-for="tech in ((data as Portfolio).tech_stack ?? []).slice(0, 3)"
                            :key="tech"
                            :value="tech"
                            severity="secondary"
                        />
                        <span
                            v-if="((data as Portfolio).tech_stack ?? []).length > 3"
                            class="muted"
                        >
                            +{{ ((data as Portfolio).tech_stack ?? []).length - 3 }}
                        </span>
                        <span v-if="!((data as Portfolio).tech_stack ?? []).length" class="muted">—</span>
                    </div>
                </template>
            </Column>

            <Column header="Visibility">
                <template #body="{ data }">
                    <StatusBadge
                        :tone="(data as Portfolio).is_public ? 'success' : 'muted'"
                        :label="(data as Portfolio).is_public ? 'Public' : 'Hidden'"
                    />
                </template>
            </Column>

            <Column header="Gallery">
                <template #body="{ data }">
                    <span class="mono">{{ (data as Portfolio).gallery_count ?? 0 }}</span>
                </template>
            </Column>

            <Column field="sort_order" header="Order">
                <template #body="{ data }">
                    <span class="mono">{{ (data as Portfolio).sort_order }}</span>
                </template>
            </Column>

            <Column header="Author">
                <template #body="{ data }">
                    <span class="muted">{{ authorName(data as Portfolio) }}</span>
                </template>
            </Column>

            <Column field="created_at" header="Created">
                <template #body="{ data }">
                    <span class="mono">{{ formatDateShort((data as Portfolio).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-32' }">
                <template #body="{ data }">
                    <div class="row-actions">
                        <PermissionGuard permission="VIEW_PORTFOLIOS">
                            <ActionButton
                                icon="pi pi-eye"
                                tone="view"
                                label="View portfolio project"
                                :href="`/portfolios/${(data as Portfolio).uuid}`"
                            />
                        </PermissionGuard>

                        <template v-if="(data as Portfolio).deleted_at">
                            <PermissionGuard permission="RESTORE_PORTFOLIOS">
                                <ActionButton
                                    icon="pi pi-check-circle"
                                    tone="restore"
                                    label="Restore portfolio project"
                                    @click="emit('restore', data as Portfolio)"
                                />
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard permission="UPDATE_PORTFOLIOS">
                                <ActionButton
                                    icon="pi pi-pencil"
                                    tone="edit"
                                    label="Edit portfolio project"
                                    @click="emit('edit', data as Portfolio)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_PORTFOLIOS">
                                <ActionButton
                                    icon="pi pi-trash"
                                    tone="delete"
                                    label="Suspend portfolio project"
                                    @click="emit('delete', data as Portfolio)"
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
:deep(.pf-cover-thumb) {
    display: block;
    width: 40px;
    height: 40px;
    margin-inline: auto;
    object-fit: cover;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
    cursor: pointer;
}

.pf-cover-thumb--empty {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--text-disabled);
    font-size: var(--text-sm);
}

.pf-title {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.pf-stack {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-1);
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

.row-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    white-space: nowrap;
}

/* ── Minimalist transparent CRUD table (matches the Clients reference) ── */
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

/* Soft-deleted (suspended) rows — token-driven tint. */
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
