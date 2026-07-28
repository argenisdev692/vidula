import { useMutation } from '@pinia/colada';
import { apiFetch } from '@/lib/http';

/**
 * Signed ZIP download URL for a completed generation package.
 * Non-Inertia JSON — Pinia Colada owns the request lifecycle.
 */
export function useProductPackageDownloadMutation() {
    return useMutation({
        mutation: (productUuid: string) =>
            apiFetch<{ data: { url: string } }>('GET', `/products/${productUuid}/package/download`),
    });
}
