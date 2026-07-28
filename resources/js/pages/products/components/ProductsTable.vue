<script setup lang="ts">
/**
 * Products server-side DataTable. Parent owns query + router.get — never sorts /
 * filters / paginates in the browser. Active rows: View · Delete; Suspended:
 * View · Restore. Every action is permission-gated.
 */
import { Link } from '@inertiajs/vue3';
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDateShort } from '@/modules/products/helpers/formatDate';
import { formatPrice, lifecycleTone, productTypeLabel } from '@/modules/products/helpers/formatProduct';
import type { Product } from '@/modules/products/types';

const props = defineProps<{
    data: Product[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: Product[];
}>();

const emit = defineEmits<{
    delete: [product: Product];
    restore: [product: Product];
    edit: [product: Product];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: Product[]];
}>();

function ownerName(row: Product): string {
    const name = [row.user?.first_name, row.user?.last_name].filter(Boolean).join(' ').trim();
    return name || 'System';
}

function rowClass(row: Product): string | undefined {
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
            @update:selection="(rows: Product[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No products match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column field="title" header="Title">
                <template #body="{ data }">
                    <span class="product-title">{{ (data as Product).title }}</span>
                </template>
            </Column>

            <Column field="type" header="Type">
                <template #body="{ data }">
                    <span class="muted">{{ productTypeLabel((data as Product).type) }}</span>
                </template>
            </Column>

            <Column header="Client">
                <template #body="{ data }">
                    <span class="muted">{{ (data as Product).client?.client_name || '—' }}</span>
                </template>
            </Column>

            <Column header="Price">
                <template #body="{ data }">
                    <span class="mono">{{ formatPrice((data as Product).price, (data as Product).currency) }}</span>
                </template>
            </Column>

            <Column header="Lifecycle">
                <template #body="{ data }">
                    <StatusBadge
                        :tone="lifecycleTone((data as Product).status)"
                        :label="(data as Product).status"
                    />
                </template>
            </Column>

            <Column header="Owner">
                <template #body="{ data }">
                    <span class="muted">{{ ownerName(data as Product) }}</span>
                </template>
            </Column>

            <Column field="created_at" header="Created">
                <template #body="{ data }">
                    <span class="mono">{{ formatDateShort((data as Product).created_at) }}</span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-32' }">
                <template #body="{ data }">
                    <div class="actions-cell">
                        <PermissionGuard permission="VIEW_PRODUCTS">
                            <Link
                                :href="`/products/${(data as Product).uuid}`"
                                prefetch
                                class="btn-crud-action btn-crud-action-view"
                                aria-label="View product"
                                title="View"
                                v-tooltip.top="'View'"
                            >
                                <i class="pi pi-eye" aria-hidden="true" />
                            </Link>
                        </PermissionGuard>

                        <template v-if="(data as Product).deleted_at">
                            <PermissionGuard permission="RESTORE_PRODUCTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-restore"
                                    aria-label="Restore product"
                                    title="Restore"
                                    v-tooltip.top="'Restore'"
                                    @click="emit('restore', data as Product)"
                                >
                                    <i class="pi pi-check-circle" aria-hidden="true" />
                                </button>
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard permission="UPDATE_PRODUCTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-edit"
                                    aria-label="Edit product"
                                    title="Edit"
                                    v-tooltip.top="'Edit'"
                                    @click="emit('edit', data as Product)"
                                >
                                    <i class="pi pi-pencil" aria-hidden="true" />
                                </button>
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_PRODUCTS">
                                <button
                                    type="button"
                                    class="btn-crud-action btn-crud-action-delete"
                                    aria-label="Suspend product"
                                    title="Suspend"
                                    v-tooltip.top="'Suspend'"
                                    @click="emit('delete', data as Product)"
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
.product-title {
    font-weight: var(--font-medium);
    color: var(--text-primary);
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
    transform: scale(1.04);
    border-color: currentColor;
}

.btn-crud-action:hover::after {
    opacity: 0.1;
}

.btn-crud-action:active {
    transform: scale(0.98);
}

.btn-crud-action:focus-visible {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
}

.btn-crud-action .pi {
    position: relative;
    z-index: 1;
    font-size: 0.8rem;
}

.btn-crud-action-view {
    color: var(--accent-info);
}

.btn-crud-action-view:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-info) 30%, transparent);
}

.btn-crud-action-edit {
    color: var(--accent-warning);
}

.btn-crud-action-edit:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-warning) 30%, transparent);
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

@media (prefers-reduced-motion: reduce) {
    .btn-crud-action,
    .btn-crud-action::after {
        transition: none;
    }

    .btn-crud-action:hover,
    .btn-crud-action:active {
        transform: none;
    }
}
</style>
