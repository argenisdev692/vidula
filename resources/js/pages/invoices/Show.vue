<script setup lang="ts">
/**
 * Invoice detail / preview page with PDF download.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import { formatDate, formatMoney } from '@/modules/invoices/helpers/formatDate';
import type { Invoice } from '@/modules/invoices/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    invoice: Invoice;
}>();

const pdfUrl = computed(() => `/invoices/${props.invoice.uuid}/pdf`);
</script>

<template>
    <AppHeader
        :title="`Invoice ${invoice.invoice_number}`"
        :subtitle="invoice.client_name"
    />

    <div class="invoice-show">
        <div class="actions">
            <Link href="/invoices" class="back-link">
                <i class="pi pi-arrow-left" aria-hidden="true" />
                Back to invoices
            </Link>
            <PermissionGuard permission="EXPORT_INVOICES">
                <a :href="pdfUrl" target="_blank" rel="noopener" class="pdf-btn">
                    <i class="pi pi-file-pdf" aria-hidden="true" />
                    Download PDF
                </a>
            </PermissionGuard>
        </div>

        <dl class="meta">
            <div>
                <dt>Issue date</dt>
                <dd>{{ formatDate(invoice.issue_date) }}</dd>
            </div>
            <div>
                <dt>Due date</dt>
                <dd>{{ formatDate(invoice.due_date) }}</dd>
            </div>
            <div>
                <dt>Total</dt>
                <dd>{{ formatMoney(invoice.total, invoice.currency) }}</dd>
            </div>
            <div>
                <dt>VAT</dt>
                <dd>
                    {{
                        invoice.tax_mode === 'EXEMPT' || Number(invoice.tax_rate ?? 0) === 0
                            ? 'Exento'
                            : formatMoney(invoice.tax_amount, invoice.currency)
                    }}
                </dd>
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
                <tr v-for="(item, index) in invoice.items ?? []" :key="index">
                    <td>
                        <strong>{{ item.title }}</strong>
                        <p v-if="item.description">{{ item.description }}</p>
                    </td>
                    <td>{{ item.quantity }}</td>
                    <td>{{ formatMoney(item.unit_price, invoice.currency) }}</td>
                    <td>{{ formatMoney(item.amount, invoice.currency) }}</td>
                </tr>
            </tbody>
        </table>

        <p v-if="invoice.notes" class="notes">{{ invoice.notes }}</p>
    </div>
</template>

<style scoped>
.invoice-show {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    font-family: var(--font-sans);
}

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
    justify-content: space-between;
}

.back-link,
.pdf-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-decoration: none;
    color: var(--text-primary);
}

.pdf-btn {
    padding: 0.5rem 0.85rem;
    border-radius: var(--radius-md, 0.5rem);
    background: var(--primary);
    color: var(--primary-contrast, #fff);
}

.meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
    gap: 0.75rem;
    margin: 0;
}

.meta dt {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.meta dd {
    margin: 0.15rem 0 0;
    font-weight: 600;
}

.items {
    width: 100%;
    border-collapse: collapse;
}

.items th,
.items td {
    text-align: left;
    padding: 0.65rem 0.5rem;
    border-bottom: 1px solid var(--border-subtle);
    vertical-align: top;
}

.items p {
    margin: 0.25rem 0 0;
    color: var(--text-muted);
    font-size: 0.875rem;
}

.notes {
    white-space: pre-line;
    color: var(--text-secondary);
}
</style>
