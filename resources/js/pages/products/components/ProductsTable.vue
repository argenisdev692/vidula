<script setup lang="ts">
/**
 * Products server-side DataTable. Parent owns query + router.get — never sorts /
 * filters / paginates in the browser. Active rows: View · Edit · Delete;
 * Suspended: View · Restore. Every action is permission-gated.
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import ActionButton from '@/common/data-table/ActionButton.vue';
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
                    <div class="row-actions">
                        <PermissionGuard permission="VIEW_PRODUCTS">
                            <ActionButton
                                icon="pi pi-eye"
                                tone="view"
                                label="View product"
                                :href="`/products/${(data as Product).uuid}`"
                            />
                        </PermissionGuard>

                        <template v-if="(data as Product).deleted_at">
                            <PermissionGuard permission="RESTORE_PRODUCTS">
                                <ActionButton
                                    icon="pi pi-check-circle"
                                    tone="restore"
                                    label="Restore product"
                                    @click="emit('restore', data as Product)"
                                />
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard permission="UPDATE_PRODUCTS">
                                <ActionButton
                                    icon="pi pi-pencil"
                                    tone="edit"
                                    label="Edit product"
                                    @click="emit('edit', data as Product)"
                                />
                            </PermissionGuard>

                            <PermissionGuard permission="DELETE_PRODUCTS">
                                <ActionButton
                                    icon="pi pi-trash"
                                    tone="delete"
                                    label="Suspend product"
                                    @click="emit('delete', data as Product)"
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

.row-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    white-space: nowrap;
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
    .crud-table-wrap :deep(tbody tr) {
        transition: none;
    }
}
</style>
