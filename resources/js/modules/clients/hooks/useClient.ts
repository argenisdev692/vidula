import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { ClientDetail } from '@/types/api';

/**
 * useSingleClient — Fetches a single company profile by UUID or for the current user.
 */
export const useSingleClient = (uuid?: string) => {
  return useQuery({
    queryKey: ['client', uuid || 'me'],
    queryFn: async () => {
      // Backend controller: show(Request $request, ?string $uuid = null)
      // If uuid is null, it uses $request->user()?->uuid
      const url = uuid ? `/clients/data/admin/${uuid}` : '/clients/data/me';
      const { data } = await axios.get<{ data: ClientDetail }>(url);
      return data.data;
    },
    enabled: !!uuid || true, // Always fetch if no uuid (me), or if uuid exists
  });
};

// Alias for backward compatibility if needed, but we prefer useSingleClient
export const useClient = useSingleClient;
