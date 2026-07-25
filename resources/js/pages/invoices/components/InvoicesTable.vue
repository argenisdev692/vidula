<script setup lang="ts">
/**
 * Invoices server-side DataTable. Parent owns query + router.get.
 */
import type { DataTablePageEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import DataTable from '@/volt/DataTable.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
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
                    <span class="mono">{{ (data as Invoice).invoice_number }}</span>
                </template>
            </Column>

            <Column field="client_name" header="Client">
                <template #body="{ data }">
                    <span>{{ (data as Invoice).client_name }}</span>
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

            <Column field="is_paid" header="Status">
                <template #body="{ data }">
                    <span class="muted">{{ (data as Invoice).is_paid ? 'Paid' : 'Unpaid' }}</span>
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

            <Column header="Actions" :pt="{ bodyCell: 'w-40' }">
                <template #body="{ data }">
                    <div class="row-actions">
                        <PermissionGuard v-if="!(data as Invoice).deleted_at" permission="EXPORT_INVOICES">
                            <button
                                type="button"
                                class="icon-btn"
                                title="Download PDF"
                                aria-label="Download PDF"
                                @click="emit('pdf', data as Invoice)"
                            >
                                <i class="pi pi-file-pdf" aria-hidden="true" />
                            </button>
                        </PermissionGuard>
                        <PermissionGuard v-if="!(data as Invoice).deleted_at" permission="UPDATE_INVOICES">
                            <button
                                type="button"
                                class="icon-btn"
                                title="Edit"
                                aria-label="Edit"
                                @click="emit('edit', data as Invoice)"
                            >
                                <i class="pi pi-pencil" aria-hidden="true" />
                            </button>
                        </PermissionGuard>
                        <PermissionGuard v-if="!(data as Invoice).deleted_at" permission="DELETE_INVOICES">
                            <button
                                type="button"
                                class="icon-btn icon-btn--danger"
                                title="Delete"
                                aria-label="Delete"
                                @click="emit('delete', data as Invoice)"
                            >
                                <i class="pi pi-trash" aria-hidden="true" />
                            </button>
                        </PermissionGuard>
                        <PermissionGuard v-if="!!(data as Invoice).deleted_at" permission="RESTORE_INVOICES">
                            <button
                                type="button"
                                class="icon-btn icon-btn--success"
                                title="Restore"
                                aria-label="Restore"
                                @click="emit('restore', data as Invoice)"
                            >
                                <i class="pi pi-replay" aria-hidden="true" />
                            </button>
                        </PermissionGuard>
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
    gap: 0.5rem;
    padding: 2rem 1rem;
    color: var(--text-muted);
}

.mono {
    font-variant-numeric: tabular-nums;
    font-family: var(--font-mono, ui-monospace, monospace);
}

.muted {
    color: var(--text-muted);
}

.row-actions {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: none;
    border-radius: var(--radius-md, 0.375rem);
    background: transparent;
    color: var(--text-secondary);
    cursor: pointer;
}

.icon-btn:hover {
    background: var(--surface-hover);
    color: var(--text-primary);
}

.icon-btn--danger:hover {
    color: var(--danger, #b91c1c);
}

.icon-btn--success:hover {
    color: var(--success, #15803d);
}

:deep(.deleted-row) {
    opacity: 0.65;
}
</style>
