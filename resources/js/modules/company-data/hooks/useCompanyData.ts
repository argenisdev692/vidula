import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { CompanyDataDetail } from '@/types/api';

/**
 * useSingleCompanyData — Fetches a single company profile by company UUID or for the current user.
 */
export const useSingleCompanyData = (companyUuid?: string) => {
  return useQuery({
    queryKey: ['company-data', companyUuid || 'me'],
    queryFn: async () => {
      const url = companyUuid ? `/company-data/data/admin/${companyUuid}` : '/company-data/data/me';
      const { data } = await axios.get<{ data: CompanyDataDetail }>(url);
      return data.data;
    },
    enabled: companyUuid !== '',
  });
};

// Alias for backward compatibility if needed, but we prefer useSingleCompanyData
export const useCompanyData = useSingleCompanyData;
