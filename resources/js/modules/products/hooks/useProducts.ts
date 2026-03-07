import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axios from 'axios';
import {
  type ProductFilters,
  type ProductsResponse,
  type RawProductListItem,
  toProductListItem,
} from '@/modules/products/types';

/**
 * useProducts — Fetches a paginated list of products.
 *
 * Módulo: Products (independiente de CompanyData).
 * Endpoint: GET /products/data/admin
 * QueryKey: ['products', filters]
 */
export const useProducts = (filters: ProductFilters) => {
  return useQuery({
    queryKey: ['products', filters],
    queryFn: async () => {
      const { data } = await axios.get<{ data: RawProductListItem[]; meta: ProductsResponse['meta'] }>('/products/data/admin', {
        params: filters,
      });
      return {
        data: data.data.map(toProductListItem),
        meta: data.meta,
      } satisfies ProductsResponse;
    },
    placeholderData: keepPreviousData, // ✅ v5
    staleTime: 1000 * 60 * 2,
  });
};
