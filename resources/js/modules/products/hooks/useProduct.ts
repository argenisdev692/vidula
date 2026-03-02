import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { ProductDetail } from '@/types/api';

/**
 * useSingleProduct — Fetches a single company profile by UUID or for the current user.
 */
export const useSingleProduct = (uuid?: string) => {
  return useQuery({
    queryKey: ['product', uuid || 'me'],
    queryFn: async () => {
      // Backend controller: show(Request $request, ?string $uuid = null)
      // If uuid is null, it uses $request->user()?->uuid
      const url = uuid ? `/product/data/admin/${uuid}` : '/product/data/me';
      const { data } = await axios.get<{ data: ProductDetail }>(url);
      return data.data;
    },
    enabled: !!uuid || true, // Always fetch if no uuid (me), or if uuid exists
  });
};

// Alias for backward compatibility if needed, but we prefer useSingleProduct
export const useProduct = useSingleProduct;
