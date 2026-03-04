import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import type { ClientDetail } from '@/types/api';

/**
 * useSingleClient — Fetches a single client profile by UUID or for the current user.
 *
 * Module: Clients (independent).
 * Endpoint: GET /clients/data/admin/:uuid
 */
export function useSingleClient(uuid?: string) {
  return useQuery<ClientDetail, Error>({
    queryKey: ['client', uuid || 'me'],
    queryFn: async () => {
      const url = uuid ? `/clients/data/admin/${uuid}` : '/clients/data/me';
      const { data } = await axios.get<{ data: ClientDetail }>(url);
      return data.data;
    },
    enabled: !!uuid,
  });
}

// Alias for backward compatibility
export const useClient = useSingleClient;
