import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axios from 'axios';
import { CompanyDataListItem, CompanyDataFilters, PaginatedResponse } from '@/types/api';

/**
 * useCompanies — Fetches a paginated list of company profiles.
 */
export const useCompanies = (filters: CompanyDataFilters) => {
  return useQuery({
    queryKey: ['companies', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<CompanyDataListItem>>('/company-data/data/admin', {
        params: filters
      });
      return data;
    },
    placeholderData: keepPreviousData,
  });
};
