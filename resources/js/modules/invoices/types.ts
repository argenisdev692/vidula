/**
 * Invoices module — snake_case interfaces mirroring backend payloads.
 */

export type InvoiceTaxMode = 'EXEMPT' | 'PERCENT';
export type InvoiceSoftStatus = 'active' | 'suspended';

export interface InvoiceItem {
    uuid?: string;
    service_id?: number | null;
    service?: { uuid: string; name: string; description: string | null } | null;
    sort_order: number;
    title: string;
    description: string | null;
    quantity: string | number;
    unit_price: string | number;
    amount: string | number;
}

export interface InvoiceProduct {
    uuid: string;
    title: string;
    description?: string | null;
    price?: string | number;
    currency?: string;
    type: string;
}

export interface Invoice {
    uuid: string;
    invoice_number: string;
    sequence: number;
    year: number;
    issue_date: string;
    due_date: string;
    currency: string;
    tax_mode: InvoiceTaxMode;
    tax_rate: string | number | null;
    tax_label: string;
    subtotal: string | number;
    tax_amount: string | number;
    total: string | number;
    is_paid: boolean;
    payment_method: string | null;
    transfer_number: string | null;
    payment_date: string | null;
    amount_received: string | number | null;
    notes: string | null;
    additional_notes: string | null;
    client?: {
        uuid: string;
        client_name: string;
        email?: string | null;
        phone?: string | null;
        tax_id?: string | null;
        nif?: string | null;
        address?: string | null;
        country?: string | null;
        country_code?: string | null;
    } | null;
    product?: InvoiceProduct | null;
    items?: InvoiceItem[];
    user?: { first_name: string | null; last_name: string | null } | null;
    created_at: string | null;
    deleted_at: string | null;
}

export interface InvoiceClientOption {
    uuid: string;
    client_name: string;
    tax_id: string | null;
    nif: string | null;
    address: string | null;
    email: string | null;
    country: string | null;
    country_code: string | null;
}

export interface InvoiceServiceOption {
    uuid: string;
    name: string;
    description: string | null;
}

export interface InvoiceProductOption {
    uuid: string;
    title: string;
    description: string | null;
    price: string | number;
    currency: string;
    type: string;
}

export interface NextInvoiceNumber {
    invoice_number: string;
    sequence: number;
    year: number;
}

export interface InvoiceFilters {
    search: string | null;
    status: InvoiceSoftStatus | null;
    date_from: string | null;
    date_to: string | null;
    year: number | null;
    client_uuid: string | null;
}

export interface InvoiceQuery {
    search: string | null;
    status: InvoiceSoftStatus | null;
    date_from: string | null;
    date_to: string | null;
    year: number | null;
    client_uuid: string | null;
    page: number;
    per_page: number;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}
