import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axios from 'axios';
import type { ProductListItem, ProductFilters, PaginatedResponse } from '@/types/api';

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
      const { data } = await axios.get<PaginatedResponse<ProductListItem>>('/products/data/admin', {
        params: filters,
      });
      return data;
    },
    placeholderData: keepPreviousData, // ✅ v5
    staleTime: 1000 * 60 * 2,
  });
};
