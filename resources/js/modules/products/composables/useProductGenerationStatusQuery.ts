import { toValue, type MaybeRefOrGetter } from 'vue';
import { useQuery } from '@pinia/colada';
import { apiFetch } from '@/lib/http';
import type { ProductGenerationStatus } from '../types';

/**
 * Polls generation status over JSON (not Inertia). Server state lives in
 * Pinia Colada — call `refetch()` on an interval while the run is in-flight.
 */
export function useProductGenerationStatusQuery(
    productUuid: MaybeRefOrGetter<string>,
    generationUuid: MaybeRefOrGetter<string | null | undefined>,
) {
    return useQuery({
        key: () =>
            [
                'products',
                toValue(productUuid),
                'generations',
                toValue(generationUuid) ?? 'none',
            ] as const,
        enabled: () => Boolean(toValue(generationUuid)),
        staleTime: 0,
        async query() {
            const product = toValue(productUuid);
            const generation = toValue(generationUuid);

            if (!generation) {
                throw new Error('Generation uuid is required.');
            }

            return apiFetch<{ data: ProductGenerationStatus }>(
                'GET',
                `/products/${product}/generations/${generation}`,
            );
        },
    });
}
