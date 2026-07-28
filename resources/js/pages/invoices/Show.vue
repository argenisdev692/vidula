<script setup lang="ts">
/**
 * Invoice detail / preview page with PDF download.
 */
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import DetailCard from '@/common/ui/DetailCard.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import StatusBadge from '@/common/ui/StatusBadge.vue';
import Button from '@/volt/Button.vue';
import { formatDate, formatMoney } from '@/modules/invoices/helpers/formatDate';
import type { Invoice } from '@/modules/invoices/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    invoice: Invoice;
}>();

const pdfUrl = computed(() => `/invoices/${props.invoice.uuid}/pdf`);
const isSuspended = computed<boolean>(() => props.invoice.deleted_at !== null);
const paymentTone = computed<'success' | 'danger'>(() => (props.invoice.is_paid ? 'success' : 'danger'));
const paymentLabel = computed<string>(() => (props.invoice.is_paid ? 'Paid' : 'Pending'));
const vatLabel = computed<string>(() =>
    props.invoice.tax_mode === 'EXEMPT' || Number(props.invoice.tax_rate ?? 0) === 0
        ? 'Exento'
        : formatMoney(props.invoice.tax_amount, props.invoice.currency),
);

function openPdf(): void {
    window.open(pdfUrl.value, '_blank', 'noopener,noreferrer');
}
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <DetailCard
        header-title="Invoice"
        header-subtitle="Client invoice detail"
        permission="VIEW_INVOICES"
        fallback-text="You don't have permission to view this invoice."
        back-href="/invoices"
        back-label="Back to invoices"
        :title="invoice.invoice_number"
        mono-title
        :columns="4"
        max-width="56rem"
    >
        <template #badges>
            <StatusBadge :tone="paymentTone" :label="paymentLabel" strong />
            <StatusBadge
                :tone="isSuspended ? 'danger' : 'success'"
                :label="isSuspended ? 'Suspended' : 'Active'"
            />
        </template>

        <div class="toolbar">
            <PermissionGuard permission="EXPORT_INVOICES">
                <Button
                    type="button"
                    label="Download PDF"
                    icon="pi pi-file-pdf"
                    aria-label="Download invoice PDF"
                    @click="openPdf"
                />
            </PermissionGuard>
        </div>

        <dl class="facts">
            <div class="fact">
                <dt>Client</dt>
                <dd>{{ invoice.client_name }}</dd>
            </div>
            <div class="fact">
                <dt>Issue date</dt>
                <dd>{{ formatDate(invoice.issue_date) }}</dd>
            </div>
            <div class="fact">
                <dt>Due date</dt>
                <dd>{{ formatDate(invoice.due_date) }}</dd>
            </div>
            <div class="fact">
                <dt>Total</dt>
                <dd class="mono">{{ formatMoney(invoice.total, invoice.currency) }}</dd>
            </div>
            <div class="fact">
                <dt>VAT</dt>
                <dd>{{ vatLabel }}</dd>
            </div>
            <div v-if="invoice.product" class="fact">
                <dt>Product</dt>
                <dd>{{ invoice.product.title }}</dd>
            </div>
        </dl>

        <table class="items">
            <thead>
                <tr>
                    <th>Concept</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in invoice.items ?? []" :key="item.uuid ?? index">
                    <td>
                        <strong>{{ item.title }}</strong>
                        <p v-if="item.description">{{ item.description }}</p>
                    </td>
                    <td class="mono">{{ item.quantity }}</td>
                    <td class="mono">{{ formatMoney(item.unit_price, invoice.currency) }}</td>
                    <td class="mono">{{ formatMoney(item.amount, invoice.currency) }}</td>
                </tr>
            </tbody>
        </table>

        <p v-if="invoice.notes" class="notes">{{ invoice.notes }}</p>
    </DetailCard>
</template>

<style scoped>
.toolbar {
    display: flex;
    justify-content: flex-end;
    margin-bottom: var(--space-4);
}

.mono {
    font-variant-numeric: tabular-nums;
    font-family: var(--font-mono, ui-monospace, monospace);
}

.items {
    width: 100%;
    border-collapse: collapse;
    margin-top: var(--space-4);
}

.items th,
.items td {
    text-align: left;
    padding: var(--space-3) var(--space-2);
    border-bottom: 1px solid var(--border-subtle);
    vertical-align: top;
    color: var(--text-primary);
}

.items th {
    color: var(--text-secondary);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.items p {
    margin: var(--space-1) 0 0;
    color: var(--text-muted);
    font-size: var(--text-sm);
}

.notes {
    margin-top: var(--space-4);
    white-space: pre-line;
    color: var(--text-secondary);
}
</style>
