import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { ProductListItem, ProductFilters, PaginatedResponse } from '@/types/api';

/**
 * useCompanies — Fetches a paginated list of company profiles.
 */
export const useCompanies = (filters: ProductFilters) => {
  return useQuery({
    queryKey: ['companies', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<ProductListItem>>('/product/data/admin', {
        params: filters
      });
      return data;
    },
    placeholderData: (previousData) => previousData,
  });
};
