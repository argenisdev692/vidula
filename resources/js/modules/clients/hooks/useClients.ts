import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axios from 'axios';
import type { ClientListItem, ClientFilters, PaginatedResponse } from '@/types/api';

/**
 * useClients — Fetches a paginated list of clients.
 *
 * Module: Clients (independent).
 * Endpoint: GET /clients/data/admin
 * QueryKey: ['clients', filters]
 */
export function useClients(filters: ClientFilters) {
  return useQuery<PaginatedResponse<ClientListItem>, Error>({
    queryKey: ['clients', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<ClientListItem>>('/clients/data/admin', {
        params: filters,
      });
      return data;
    },
    placeholderData: keepPreviousData, // ✅ v5 — imported function
    staleTime: 1000 * 60 * 2,
  });
}
