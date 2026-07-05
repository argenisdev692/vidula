import { computed, type ComputedRef } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { CompanyBranding, SharedProps } from '@/types/inertia';

/**
 * DB-driven company branding (name + logo URLs) shared on every page by
 * HandleInertiaRequests. Single source for the hero, navbar and sidebar so the
 * logos/name are managed from `company_data`, never hardcoded.
 */
export function useCompany(): ComputedRef<CompanyBranding> {
    const page = usePage<SharedProps>();

    return computed(() => page.props.company);
}
