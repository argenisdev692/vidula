import { z } from 'zod';

const invoiceItemSchema = z.object({
    title: z.string().trim().min(1, 'Title is required').max(255),
    description: z.string().trim().max(500),
    quantity: z.number().min(0.01, 'Quantity must be at least 0.01').max(999999),
    unit_price: z.number().min(0, 'Unit price cannot be negative').max(9999999.99),
    service_uuid: z.string().uuid().nullable(),
    sort_order: z.number().int().min(0).max(65535),
});

export const invoiceFormSchema = z
    .object({
        client_uuid: z.string().uuid('Select a client'),
        invoice_number: z
            .string()
            .trim()
            .regex(/^\d{1,6}\/\d{4}$/, 'Use format NNN/YYYY (e.g. 007/2026)'),
        issue_date: z.string().min(1, 'Issue date is required'),
        due_date: z.string().min(1, 'Due date is required'),
        currency: z.string().length(3).regex(/^[A-Z]{3}$/, 'Currency must be 3 uppercase letters'),
        tax_mode: z.enum(['EXEMPT', 'PERCENT']),
        tax_rate: z.number().min(0).max(100).nullable().default(0),
        tax_label: z.string().trim().min(1).max(32),
        is_paid: z.boolean(),
        payment_method: z.string().trim().max(255),
        transfer_number: z.string().trim().max(255),
        payment_date: z.string(),
        amount_received: z.number().min(0).max(9999999.99).nullable(),
        notes: z.string().trim().max(5000),
        items: z.array(invoiceItemSchema).min(1, 'Add at least one line item').max(50),
    })
    .superRefine((data, ctx) => {
        if (data.due_date < data.issue_date) {
            ctx.addIssue({
                code: 'custom',
                path: ['due_date'],
                message: 'Due date must be on or after the issue date',
            });
        }
        if (data.is_paid) {
            if (!data.payment_method.trim()) {
                ctx.addIssue({
                    code: 'custom',
                    path: ['payment_method'],
                    message: 'Payment method is required when marked as paid',
                });
            }
            if (!data.payment_date) {
                ctx.addIssue({
                    code: 'custom',
                    path: ['payment_date'],
                    message: 'Payment date is required when marked as paid',
                });
            }
            if (data.amount_received === null || Number.isNaN(data.amount_received)) {
                ctx.addIssue({
                    code: 'custom',
                    path: ['amount_received'],
                    message: 'Amount received is required when marked as paid',
                });
            }
        }
    });

export type InvoiceFormValues = z.infer<typeof invoiceFormSchema>;
export type InvoiceItemFormValues = z.infer<typeof invoiceItemSchema>;

export function emptyInvoiceItem(sortOrder = 0): InvoiceItemFormValues {
    return {
        title: '',
        description: '',
        quantity: 1,
        unit_price: 0,
        service_uuid: null,
        sort_order: sortOrder,
    };
}
