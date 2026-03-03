import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import type { ClientListItem, ClientFilters, PaginatedResponse } from '@/types/api';

/**
 * useClients — Fetches a paginated list of clients.
 *
 * Módulo: Clients (independiente de CompanyData).
 * Endpoint: GET /clients/data/admin
 * QueryKey: ['clients', filters]
 */
export const useClients = (filters: ClientFilters) => {
  return useQuery({
    queryKey: ['clients', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<ClientListItem>>('/clients/data/admin', {
        params: filters,
      });
      return data;
    },
    placeholderData: (previousData) => previousData,
  });
};
