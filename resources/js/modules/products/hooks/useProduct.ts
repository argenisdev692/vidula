import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { type RawProductDetail, toProductDetail } from '@/modules/products/types';

/**
 * useSingleProduct — Fetches a single company profile by UUID or for the current user.
 */
export const useSingleProduct = (uuid: string) => {
  return useQuery({
    queryKey: ['products', uuid],
    queryFn: async () => {
      const { data } = await axios.get<{ data: RawProductDetail }>(`/products/data/admin/${uuid}`);
      return toProductDetail(data.data);
    },
    enabled: uuid.length > 0,
  });
};

// Alias for backward compatibility if needed, but we prefer useSingleProduct
export const useProduct = useSingleProduct;
