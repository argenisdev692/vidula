import { computed, ref, watch, type ComputedRef, type Ref } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { apiFetch } from '@/lib/http';

export type InvoiceNumberCheckStatus = 'idle' | 'invalid' | 'checking' | 'available' | 'taken';

export interface InvoiceNumberConflict {
    uuid: string;
    invoice_number: string;
    client_name: string;
    is_suspended: boolean;
}

interface CheckInvoiceNumberResponse {
    available: boolean;
    invoice_number: string;
    invoice: InvoiceNumberConflict | null;
}

interface UseInvoiceNumberCheckOptions {
    source: () => string;
    /** Own invoice number on edit → idle when unchanged. */
    original?: () => string | null | undefined;
    /** Edited invoice UUID so the current row is ignored. */
    ignoreUuid?: () => string | null | undefined;
    debounceMs?: number;
}

interface UseInvoiceNumberCheckReturn {
    status: Ref<InvoiceNumberCheckStatus>;
    conflict: Ref<InvoiceNumberConflict | null>;
    isBlocking: ComputedRef<boolean>;
    message: ComputedRef<string>;
}

const NUMBER_PATTERN = /^(\d{1,6}|\d{1,6}\/\d{4})$/;

/**
 * Debounced realtime check against `GET /invoices/check-number`.
 * Taken → "Generated invoice — {client_name}" (soft-deleted noted).
 */
export function useInvoiceNumberCheck(options: UseInvoiceNumberCheckOptions): UseInvoiceNumberCheckReturn {
    const status = ref<InvoiceNumberCheckStatus>('idle');
    const conflict = ref<InvoiceNumberConflict | null>(null);

    const run = useDebounceFn(async (): Promise<void> => {
        const value = options.source().trim();
        const original = (options.original?.() ?? '').trim();

        if (!value || value === original) {
            status.value = 'idle';
            conflict.value = null;
            return;
        }

        if (!NUMBER_PATTERN.test(value)) {
            status.value = 'invalid';
            conflict.value = null;
            return;
        }

        status.value = 'checking';
        conflict.value = null;

        const params = new URLSearchParams({ invoice_number: value });
        const ignore = options.ignoreUuid?.();
        if (ignore) {
            params.set('ignore', ignore);
        }

        try {
            const result = await apiFetch<CheckInvoiceNumberResponse>(
                'GET',
                `/invoices/check-number?${params.toString()}`,
            );

            if (options.source().trim() !== value) {
                return;
            }

            if (result.available) {
                status.value = 'available';
                conflict.value = null;
                return;
            }

            status.value = 'taken';
            conflict.value = result.invoice;
        } catch {
            status.value = 'idle';
            conflict.value = null;
        }
    }, options.debounceMs ?? 400);

    watch(options.source, () => {
        void run();
    });

    const isBlocking = computed<boolean>(() => status.value === 'taken' || status.value === 'invalid');

    const message = computed<string>(() => {
        if (status.value === 'checking') {
            return 'Checking invoice number…';
        }
        if (status.value === 'available') {
            return '✓ Invoice number is available';
        }
        if (status.value === 'taken' && conflict.value) {
            const suspended = conflict.value.is_suspended ? ' (suspended)' : '';
            return `Generated invoice — ${conflict.value.client_name}${suspended}`;
        }
        if (status.value === 'invalid') {
            return 'Use NNN/YYYY (e.g. 014/2026) or bare sequence (014)';
        }
        return '';
    });

    return { status, conflict, isBlocking, message };
}
