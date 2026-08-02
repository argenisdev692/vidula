<script setup lang="ts">
/**
 * Invoices server-side DataTable. Parent owns query + router.get.
 * Active rows: View · PDF · Edit · Delete; Suspended: View · Restore.
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import ActionButton from '@/common/data-table/ActionButton.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import { formatDate, formatMoney } from '@/modules/invoices/helpers/formatDate';
import type { Invoice } from '@/modules/invoices/types';

const props = defineProps<{
    data: Invoice[];
    total: number;
    perPage: number;
    first: number;
    loading: boolean;
    selection: Invoice[];
}>();

const emit = defineEmits<{
    edit: [invoice: Invoice];
    delete: [invoice: Invoice];
    restore: [invoice: Invoice];
    pdf: [invoice: Invoice];
    page: [event: DataTablePageEvent];
    'update:selection': [rows: Invoice[]];
}>();

function rowClass(row: Invoice): string | undefined {
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
            @update:selection="(rows: Invoice[]) => emit('update:selection', rows)"
            @page="(event: DataTablePageEvent) => emit('page', event)"
        >
            <template #empty>
                <div class="table-empty">
                    <i class="pi pi-inbox" aria-hidden="true" />
                    <span>No invoices match your filters.</span>
                </div>
            </template>

            <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />

            <Column field="invoice_number" header="Number">
                <template #body="{ data }">
                    <span class="mono invoice-number">{{ (data as Invoice).invoice_number }}</span>
                </template>
            </Column>

            <Column field="client_name" header="Client">
                <template #body="{ data }">
                    <span>{{ (data as Invoice).client?.client_name ?? '—' }}</span>
                </template>
            </Column>

            <Column field="issue_date" header="Issue date">
                <template #body="{ data }">
                    <span class="mono">{{ formatDate((data as Invoice).issue_date) }}</span>
                </template>
            </Column>

            <Column field="due_date" header="Due date">
                <template #body="{ data }">
                    <span class="mono">{{ formatDate((data as Invoice).due_date) }}</span>
                </template>
            </Column>

            <Column field="total" header="Total">
                <template #body="{ data }">
                    <span class="mono">{{ formatMoney((data as Invoice).total, (data as Invoice).currency) }}</span>
                </template>
            </Column>

            <Column field="is_paid" header="Payment">
                <template #body="{ data }">
                    <StatusBadge
                        :tone="(data as Invoice).is_paid ? 'success' : 'danger'"
                        :label="(data as Invoice).is_paid ? 'Paid' : 'Pending'"
                    />
                </template>
            </Column>

            <Column field="tax_mode" header="VAT">
                <template #body="{ data }">
                    <span class="muted">
                        {{
                            (data as Invoice).tax_mode === 'EXEMPT' || Number((data as Invoice).tax_rate ?? 0) === 0
                                ? 'Exento'
                                : 'Taxed'
                        }}
                    </span>
                </template>
            </Column>

            <Column header="Actions" :pt="{ bodyCell: 'w-44' }">
                <template #body="{ data }">
                    <div class="row-actions">
                        <PermissionGuard permission="VIEW_INVOICES">
                            <ActionButton
                                icon="pi pi-eye"
                                tone="view"
                                label="View invoice"
                                :href="`/invoices/${(data as Invoice).uuid}`"
                            />
                        </PermissionGuard>

                        <template v-if="(data as Invoice).deleted_at">
                            <PermissionGuard permission="RESTORE_INVOICES">
                                <ActionButton
                                    icon="pi pi-check-circle"
                                    tone="restore"
                                    label="Restore invoice"
                                    @click="emit('restore', data as Invoice)"
                                />
                            </PermissionGuard>
                        </template>

                        <template v-else>
                            <PermissionGuard permission="EXPORT_INVOICES">
                                <ActionButton
                                    icon="pi pi-file-pdf"
                                    tone="view"
                                    label="Download PDF"
                                    @click="emit('pdf', data as Invoice)"
                                />
                            </PermissionGuard>
                            <PermissionGuard permission="UPDATE_INVOICES">
                                <ActionButton
                                    icon="pi pi-pencil"
                                    tone="edit"
                                    label="Edit invoice"
                                    @click="emit('edit', data as Invoice)"
                                />
                            </PermissionGuard>
                            <PermissionGuard permission="DELETE_INVOICES">
                                <ActionButton
                                    icon="pi pi-trash"
                                    tone="delete"
                                    label="Delete invoice"
                                    @click="emit('delete', data as Invoice)"
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

.mono {
    font-variant-numeric: tabular-nums;
    font-family: var(--font-mono, ui-monospace, monospace);
    font-size: var(--text-sm);
}

.invoice-number {
    color: var(--accent-error);
    font-weight: var(--font-semibold, 600);
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
</style>
