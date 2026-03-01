import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { CompanyDataDetail } from '@/types/api';

/**
 * useSingleCompanyData — Fetches a single company profile by UUID or for the current user.
 */
export const useSingleCompanyData = (uuid?: string) => {
  return useQuery({
    queryKey: ['company-data', uuid || 'me'],
    queryFn: async () => {
      // Backend controller: show(Request $request, ?string $uuid = null)
      // If uuid is null, it uses $request->user()?->uuid
      const url = uuid ? `/company-data/data/admin/${uuid}` : '/company-data/data/me';
      const { data } = await axios.get<{ data: CompanyDataDetail }>(url);
      return data.data;
    },
    enabled: !!uuid || true, // Always fetch if no uuid (me), or if uuid exists
  });
};

// Alias for backward compatibility if needed, but we prefer useSingleCompanyData
export const useCompanyData = useSingleCompanyData;
