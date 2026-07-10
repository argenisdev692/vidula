import { computed, ref, watch, type ComputedRef, type Ref } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { apiFetch } from '@/lib/http';

/**
 * Realtime "is this username/email free?" check, shared by the register, profile
 * and user-management forms. Debounces the watched value, hits the given backend
 * endpoint (`?field=&value=` plus any `extraParams`, e.g. an `ignore` uuid) and
 * exposes a small state machine the UI turns into green/red "color check"
 * feedback. The backend stays authoritative — this is UX only.
 *
 *   idle      → empty, or unchanged from the stored `original`
 *   invalid   → fails the optional client-side shape check
 *   checking  → request in flight
 *   available → free (render green)
 *   taken     → already in use (render red)
 *
 * Stale responses are dropped (the value changed again mid-flight) and any
 * network/rate-limit error degrades to `idle` so a hiccup never blocks the form.
 */
export type AvailabilityStatus = 'idle' | 'invalid' | 'checking' | 'available' | 'taken';

interface UseFieldAvailabilityOptions {
    /** Which column the backend should check. */
    field: 'username' | 'email';
    /** Backend endpoint, e.g. '/users/availability'. */
    endpoint: string;
    /** Reactive getter for the current field value (this is what gets watched). */
    source: () => string;
    /** Baseline value treated as "unchanged" → idle (e.g. the stored username). */
    original?: () => string | null | undefined;
    /** Extra query params merged into the request (e.g. `{ ignore: uuid }`). */
    extraParams?: () => Record<string, string>;
    /** Client-side shape check; a `false` result short-circuits to `invalid`. */
    validate?: (value: string) => boolean;
    /** Debounce window in ms (default 400). */
    debounceMs?: number;
}

interface UseFieldAvailabilityReturn {
    status: Ref<AvailabilityStatus>;
    /** True while the value is unusable (taken or invalid) — gate submit on it. */
    isBlocking: ComputedRef<boolean>;
    /** Re-run the check imperatively (already debounced). */
    recheck: () => void;
}

export function useFieldAvailability(options: UseFieldAvailabilityOptions): UseFieldAvailabilityReturn {
    const status = ref<AvailabilityStatus>('idle');

    const run = useDebounceFn(async (): Promise<void> => {
        const value = options.source().trim();
        const original = (options.original?.() ?? '') || '';

        if (!value || value === original) {
            status.value = 'idle';
            return;
        }
        if (options.validate && !options.validate(value)) {
            status.value = 'invalid';
            return;
        }

        status.value = 'checking';
        const params = new URLSearchParams({
            field: options.field,
            value,
            ...(options.extraParams?.() ?? {}),
        });

        try {
            const result = await apiFetch<{ available: boolean }>('GET', `${options.endpoint}?${params.toString()}`);
            // Drop the response if the field moved on while we were waiting.
            if (options.source().trim() !== value) {
                return;
            }
            status.value = result.available ? 'available' : 'taken';
        } catch {
            status.value = 'idle';
        }
    }, options.debounceMs ?? 400);

    watch(options.source, () => {
        void run();
    });

    const isBlocking = computed<boolean>(() => status.value === 'taken' || status.value === 'invalid');

    return { status, isBlocking, recheck: run };
}
