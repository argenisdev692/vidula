import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { ClientListItem, ClientFilters, PaginatedResponse } from '@/types/api';

/**
 * useCompanies — Fetches a paginated list of company profiles.
 */
export const useCompanies = (filters: ClientFilters) => {
  return useQuery({
    queryKey: ['companies', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<ClientListItem>>('/client/data/admin', {
        params: filters
      });
      return data;
    },
    placeholderData: (previousData) => previousData,
  });
};
