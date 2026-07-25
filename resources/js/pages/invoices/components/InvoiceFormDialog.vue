<script setup lang="ts">
/**
 * Create / edit modal for an invoice with multi line-items.
 *   · create → POST /invoices
 *   · edit   → PUT  /invoices/{uuid}
 */
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/common/form/TextField.vue';
import TextareaField from '@/common/form/TextareaField.vue';
import SelectField from '@/common/form/SelectField.vue';
import DateField from '@/common/form/DateField.vue';
import AppModal from '@/common/ui/AppModal.vue';
import ToggleSwitch from '@/volt/ToggleSwitch.vue';
import { apiFetch } from '@/lib/http';
import {
    emptyInvoiceItem,
    invoiceFormSchema,
    type InvoiceFormValues,
    type InvoiceItemFormValues,
} from '@/modules/invoices/schemas/invoiceFormSchema';
import { formatMoney } from '@/modules/invoices/helpers/formatDate';
import { toLocalIsoDate } from '@/lib/date';
import type {
    Invoice,
    InvoiceClientOption,
    InvoiceServiceOption,
    NextInvoiceNumber,
} from '@/modules/invoices/types';
import type { SelectOption } from '@/common/form/types';

const visible = defineModel<boolean>('visible', { default: false });

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit';
        invoice?: Invoice | null;
        clients: InvoiceClientOption[];
        services: InvoiceServiceOption[];
        nextInvoiceNumber: NextInvoiceNumber;
        defaultNotes?: string | null;
    }>(),
    { mode: 'create', invoice: null, defaultNotes: null },
);

const emit = defineEmits<{ saved: [] }>();

const loadingInvoice = ref(false);

function todayIso(): string {
    return toLocalIsoDate(new Date()) ?? '';
}

function plusDaysIso(days: number): string {
    const d = new Date();
    d.setDate(d.getDate() + days);
    return toLocalIsoDate(d) ?? '';
}

const form = useForm<InvoiceFormValues>({
    client_uuid: '',
    invoice_number: '',
    issue_date: todayIso(),
    due_date: plusDaysIso(5),
    currency: 'USD',
    tax_mode: 'EXEMPT',
    tax_rate: 0,
    tax_label: 'IVA',
    is_paid: false,
    payment_method: '',
    transfer_number: '',
    payment_date: '',
    amount_received: null,
    notes: '',
    items: [emptyInvoiceItem(0)],
});

const isEdit = computed<boolean>(() => props.mode === 'edit');
const dialogTitle = computed<string>(() => (isEdit.value ? 'Edit invoice' : 'New invoice'));

const clientOptions = computed<SelectOption[]>(() =>
    props.clients.map((client) => ({ label: client.client_name, value: client.uuid })),
);

const serviceOptions = computed<SelectOption[]>(() =>
    props.services.map((service) => ({ label: service.name, value: service.uuid })),
);

const taxModeOptions: SelectOption[] = [
    { label: 'Exento (0% / Reverse Charge)', value: 'EXEMPT' },
    { label: 'Percentage (IVA)', value: 'PERCENT' },
];

const clientModel = computed<string | null>({
    get: () => form.client_uuid || null,
    set: (value) => {
        form.client_uuid = value ?? '';
    },
});

const taxModeModel = computed<string | null>({
    get: () => form.tax_mode,
    set: (value) => {
        form.tax_mode = (value as InvoiceFormValues['tax_mode']) || 'EXEMPT';
        if (form.tax_mode === 'EXEMPT') {
            form.tax_rate = 0;
        } else if (form.tax_rate === null) {
            form.tax_rate = 0;
        }
    },
});

const issueDateModel = computed<string | null>({
    get: () => form.issue_date || null,
    set: (value) => {
        form.issue_date = value ?? '';
    },
});

const dueDateModel = computed<string | null>({
    get: () => form.due_date || null,
    set: (value) => {
        form.due_date = value ?? '';
    },
});

const paymentDateModel = computed<string | null>({
    get: () => form.payment_date || null,
    set: (value) => {
        form.payment_date = value ?? '';
    },
});

function onPaidToggle(value: boolean): void {
    form.is_paid = value;
    if (!value) {
        form.payment_method = '';
        form.transfer_number = '';
        form.payment_date = '';
        form.amount_received = null;
        return;
    }
    if (!form.payment_date) {
        form.payment_date = todayIso();
    }
    if (form.amount_received === null) {
        form.amount_received = Math.round(total.value * 100) / 100;
    }
}

const subtotal = computed<number>(() =>
    form.items.reduce((sum, item) => sum + Number(item.quantity) * Number(item.unit_price), 0),
);

const taxAmount = computed<number>(() => {
    if (form.tax_mode !== 'PERCENT') {
        return 0;
    }
    const rate = form.tax_rate ?? 0;
    return Math.round(subtotal.value * (rate / 100) * 100) / 100;
});

const total = computed<number>(() => Math.round((subtotal.value + taxAmount.value) * 100) / 100);

function parseIsoDate(value: string): string {
    return value.includes('T') ? value.slice(0, 10) : value;
}

function mapItemsFromInvoice(invoice: Invoice): InvoiceItemFormValues[] {
    const items = invoice.items ?? [];
    if (items.length === 0) {
        return [emptyInvoiceItem(0)];
    }

    return items.map((item, index) => ({
        title: item.title,
        description: item.description ?? '',
        quantity: Number(item.quantity),
        unit_price: Number(item.unit_price),
        service_uuid: item.service?.uuid ?? null,
        sort_order: item.sort_order ?? index,
    }));
}

function fillFromInvoice(invoice: Invoice): void {
    form.client_uuid = invoice.client?.uuid ?? '';
    form.invoice_number = invoice.invoice_number;
    form.issue_date = parseIsoDate(invoice.issue_date);
    form.due_date = parseIsoDate(invoice.due_date);
    form.currency = invoice.currency;
    form.tax_mode = invoice.tax_mode;
    form.tax_rate = invoice.tax_rate !== null ? Number(invoice.tax_rate) : 0;
    form.tax_label = invoice.tax_label || 'IVA';
    form.is_paid = Boolean(invoice.is_paid);
    form.payment_method = invoice.payment_method ?? '';
    form.transfer_number = invoice.transfer_number ?? '';
    form.payment_date = invoice.payment_date ? parseIsoDate(invoice.payment_date) : '';
    form.amount_received =
        invoice.amount_received !== null && invoice.amount_received !== undefined
            ? Number(invoice.amount_received)
            : null;
    form.notes = invoice.notes ?? '';
    form.items = mapItemsFromInvoice(invoice);
}

watch(visible, async (open) => {
    if (!open) {
        return;
    }
    form.clearErrors();

    if (isEdit.value && props.invoice) {
        loadingInvoice.value = true;
        try {
            const payload = await apiFetch<{ data: Invoice }>('GET', `/invoices/${props.invoice.uuid}`);
            fillFromInvoice(payload.data);
        } catch {
            fillFromInvoice(props.invoice);
        } finally {
            loadingInvoice.value = false;
        }
        return;
    }

    form.client_uuid = '';
    form.invoice_number = props.nextInvoiceNumber.invoice_number;
    form.issue_date = todayIso();
    form.due_date = plusDaysIso(5);
    form.currency = 'USD';
    form.tax_mode = 'EXEMPT';
    form.tax_rate = 0;
    form.tax_label = 'IVA';
    form.is_paid = false;
    form.payment_method = '';
    form.transfer_number = '';
    form.payment_date = '';
    form.amount_received = null;
    form.notes = props.defaultNotes ?? '';
    form.items = [emptyInvoiceItem(0)];
});

function close(): void {
    visible.value = false;
}

function addItem(): void {
    form.items.push(emptyInvoiceItem(form.items.length));
}

function removeItem(index: number): void {
    if (form.items.length <= 1) {
        return;
    }
    form.items.splice(index, 1);
}

function onServiceChange(index: number, serviceUuid: string | null): void {
    const item = form.items[index];
    if (!item) {
        return;
    }
    item.service_uuid = serviceUuid;
    if (!item.service_uuid) {
        return;
    }
    const service = props.services.find((row) => row.uuid === item.service_uuid);
    if (!service) {
        return;
    }
    item.title = service.name;
    item.description = service.description ?? '';
}

function lineTotal(item: InvoiceItemFormValues): number {
    return Math.round(Number(item.quantity) * Number(item.unit_price) * 100) / 100;
}

function emptyToNull(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

function submit(): void {
    const parsed = invoiceFormSchema.safeParse({
        ...form.data(),
        tax_rate: form.tax_mode === 'PERCENT' ? (form.tax_rate ?? 0) : 0,
        is_paid: form.is_paid,
        payment_method: form.payment_method,
        transfer_number: form.transfer_number,
        payment_date: form.payment_date,
        amount_received: form.is_paid ? (form.amount_received ?? 0) : null,
        items: form.items.map((item, index) => ({
            ...item,
            sort_order: index,
            service_uuid: item.service_uuid || null,
        })),
    });

    if (!parsed.success) {
        form.clearErrors();
        for (const issue of parsed.error.issues) {
            const path = issue.path.join('.');
            if (path) {
                form.setError(path as keyof InvoiceFormValues, issue.message);
            }
        }
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            close();
        },
    };

    form.transform((data) => ({
        client_uuid: data.client_uuid,
        invoice_number: data.invoice_number.trim(),
        issue_date: data.issue_date,
        due_date: data.due_date,
        currency: data.currency.toUpperCase(),
        tax_mode: data.tax_mode,
        tax_rate: data.tax_mode === 'PERCENT' ? (data.tax_rate ?? 0) : null,
        tax_label: data.tax_label.trim() || 'IVA',
        is_paid: data.is_paid,
        payment_method: data.is_paid ? emptyToNull(data.payment_method) : null,
        transfer_number: data.is_paid ? emptyToNull(data.transfer_number) : null,
        payment_date: data.is_paid ? emptyToNull(data.payment_date) : null,
        amount_received: data.is_paid ? (data.amount_received ?? 0) : null,
        notes: emptyToNull(data.notes),
        items: data.items.map((item, index) => ({
            title: item.title.trim(),
            description: emptyToNull(item.description),
            quantity: item.quantity,
            unit_price: item.unit_price,
            service_uuid: item.service_uuid || null,
            sort_order: index,
        })),
    }));

    if (isEdit.value) {
        form.put(`/invoices/${props.invoice!.uuid}`, options);
    } else {
        form.post('/invoices', options);
    }
}
</script>

<template>
    <AppModal
        v-model:visible="visible"
        :title="dialogTitle"
        :subtitle="isEdit ? 'Update invoice details and regenerate the PDF later.' : 'Create a PDF invoice for a client.'"
        icon="pi pi-file"
        :confirm-label="isEdit ? 'Save changes' : 'Create invoice'"
        confirm-icon="pi pi-check"
        :loading="form.processing || loadingInvoice"
        :dismissable="!form.processing && !loadingInvoice"
        width="48rem"
        @confirm="submit"
        @cancel="close"
    >
        <form class="invoice-form" @submit.prevent="submit">
            <div class="form-grid">
                <SelectField
                    v-model="clientModel"
                    name="client_uuid"
                    label="Client"
                    required
                    :options="clientOptions"
                    placeholder="Select client"
                    :error="form.errors.client_uuid"
                />
                <TextField
                    v-model="form.invoice_number"
                    name="invoice_number"
                    label="Invoice number"
                    required
                    hint="Format NNN/YYYY"
                    :error="form.errors.invoice_number"
                />
                <DateField
                    v-model="issueDateModel"
                    name="issue_date"
                    label="Issue date"
                    required
                    :error="form.errors.issue_date"
                />
                <DateField
                    v-model="dueDateModel"
                    name="due_date"
                    label="Due date"
                    required
                    :min-date="form.issue_date || undefined"
                    :error="form.errors.due_date"
                />
                <SelectField
                    v-model="taxModeModel"
                    name="tax_mode"
                    label="VAT / IVA"
                    required
                    :options="taxModeOptions"
                    :error="form.errors.tax_mode"
                />
                <TextField
                    v-if="form.tax_mode === 'PERCENT'"
                    :model-value="form.tax_rate === null ? '0' : String(form.tax_rate)"
                    name="tax_rate"
                    label="Tax rate (%)"
                    type="number"
                    hint="Default 0% — you are not charging IVA yet."
                    :error="form.errors.tax_rate"
                    @update:model-value="(v: string) => (form.tax_rate = v === '' ? 0 : Number(v))"
                />
            </div>

            <div class="section-label">
                <span>Line items</span>
                <button type="button" class="link-btn" @click="addItem">
                    <i class="pi pi-plus" aria-hidden="true" />
                    Add line
                </button>
            </div>
            <p v-if="form.errors.items" class="field-error">{{ form.errors.items }}</p>

            <div v-for="(item, index) in form.items" :key="index" class="line-item">
                <div class="form-grid form-grid--dense">
                    <SelectField
                        :model-value="item.service_uuid"
                        :name="`items.${index}.service_uuid`"
                        label="Service (optional)"
                        :options="serviceOptions"
                        placeholder="Custom / free text"
                        @update:model-value="(v: string | null) => onServiceChange(index, v)"
                    />
                    <TextField
                        v-model="item.title"
                        :name="`items.${index}.title`"
                        label="Title"
                        required
                        :error="form.errors[`items.${index}.title` as keyof InvoiceFormValues]"
                    />
                    <TextField
                        :model-value="String(item.quantity)"
                        :name="`items.${index}.quantity`"
                        label="Qty"
                        type="number"
                        required
                        @update:model-value="(v: string) => (item.quantity = Number(v) || 0)"
                    />
                    <TextField
                        :model-value="String(item.unit_price)"
                        :name="`items.${index}.unit_price`"
                        label="Unit price"
                        type="number"
                        required
                        @update:model-value="(v: string) => (item.unit_price = Number(v) || 0)"
                    />
                </div>
                <TextareaField
                    v-model="item.description"
                    :name="`items.${index}.description`"
                    label="Description"
                    :rows="2"
                />
                <div class="line-item__footer">
                    <span class="line-total">{{ formatMoney(lineTotal(item), form.currency) }}</span>
                    <button
                        type="button"
                        class="link-btn link-btn--danger"
                        :disabled="form.items.length <= 1"
                        @click="removeItem(index)"
                    >
                        Remove
                    </button>
                </div>
            </div>

            <div class="totals">
                <div><span>Subtotal</span><strong>{{ formatMoney(subtotal, form.currency) }}</strong></div>
                <div>
                    <span>{{ form.tax_label || 'IVA' }}</span>
                    <strong>
                        {{
                            form.tax_mode === 'EXEMPT' || (form.tax_rate ?? 0) === 0
                                ? 'Exento'
                                : formatMoney(taxAmount, form.currency)
                        }}
                    </strong>
                </div>
                <div class="totals__grand">
                    <span>Total</span>
                    <strong>{{ formatMoney(total, form.currency) }}</strong>
                </div>
            </div>

            <div class="paid-toggle">
                <div class="paid-toggle__copy">
                    <span class="paid-toggle__label">Mark as paid</span>
                    <span class="paid-toggle__hint">
                        When enabled, the PDF shows ✓ PAYMENT RECEIVED instead of bank details.
                    </span>
                </div>
                <ToggleSwitch
                    :model-value="form.is_paid"
                    input-id="is_paid"
                    aria-label="Mark invoice as paid"
                    @update:model-value="onPaidToggle"
                />
            </div>

            <div v-if="form.is_paid" class="payment-fields">
                <div class="section-label"><span>Payment received</span></div>
                <div class="form-grid">
                    <TextField
                        v-model="form.payment_method"
                        name="payment_method"
                        label="Payment method"
                        required
                        placeholder="Remitly (International Transfer)"
                        :error="form.errors.payment_method"
                    />
                    <TextField
                        v-model="form.transfer_number"
                        name="transfer_number"
                        label="Transfer number"
                        placeholder="R20 386 959 937"
                        :error="form.errors.transfer_number"
                    />
                    <DateField
                        v-model="paymentDateModel"
                        name="payment_date"
                        label="Payment date"
                        required
                        :error="form.errors.payment_date"
                    />
                    <TextField
                        :model-value="form.amount_received === null ? '' : String(form.amount_received)"
                        name="amount_received"
                        label="Amount received"
                        type="number"
                        required
                        :error="form.errors.amount_received"
                        @update:model-value="(v: string) => (form.amount_received = v === '' ? null : Number(v))"
                    />
                </div>
            </div>

            <TextareaField
                v-model="form.notes"
                name="notes"
                label="Important notes"
                hint="Shown on the PDF (e.g. VAT reverse charge)."
                :rows="3"
                :error="form.errors.notes"
            />
        </form>
    </AppModal>
</template>

<style scoped>
.invoice-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
}

@media (min-width: 640px) {
    .form-grid {
        grid-template-columns: 1fr 1fr;
    }

    .form-grid--dense {
        grid-template-columns: 1.4fr 1.4fr 0.7fr 0.9fr;
    }
}

.section-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    color: var(--text-primary);
    font-family: var(--font-sans);
}

.line-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.75rem;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md, 0.5rem);
    background: var(--surface-raised);
}

.line-item__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.line-total {
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}

.link-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border: none;
    background: transparent;
    color: var(--primary);
    cursor: pointer;
    font-family: var(--font-sans);
    font-size: 0.875rem;
}

.link-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.link-btn--danger {
    color: var(--danger, #b91c1c);
}

.totals {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    align-items: flex-end;
    font-family: var(--font-sans);
}

.totals > div {
    display: flex;
    gap: 1.5rem;
    min-width: 14rem;
    justify-content: space-between;
}

.totals__grand {
    margin-top: 0.25rem;
    padding-top: 0.5rem;
    border-top: 2px solid var(--border-subtle);
    font-size: 1.05rem;
}

.paid-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.75rem;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-md, 0.5rem);
    background: var(--surface-raised);
}

.paid-toggle__copy {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.paid-toggle__label {
    font-weight: 600;
    color: var(--text-primary);
}

.paid-toggle__hint {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.payment-fields {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.field-error {
    margin: 0;
    color: var(--danger, #b91c1c);
    font-size: 0.875rem;
}
</style>
