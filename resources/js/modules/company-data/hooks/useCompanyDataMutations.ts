import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { CreateCompanyDataDTO, UpdateCompanyDataDTO } from '@/types/api';

/**
 * useCompanyDataMutations — Mutations for updating company data.
 */
export const useCompanyDataMutations = () => {
  const queryClient = useQueryClient();

  const createCompanyData = useMutation({
    mutationFn: (payload: CreateCompanyDataDTO) => {
      return axios.post('/company-data/data/admin', payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const updateCompanyData = useMutation({
    mutationFn: ({ companyUuid, payload }: { companyUuid?: string; payload: UpdateCompanyDataDTO }) => {
      const url = companyUuid ? `/company-data/data/admin/${companyUuid}` : '/company-data/data/me';
      return axios.put(url, payload);
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['company-data', variables.companyUuid || 'me'] });
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const deleteCompanyData = useMutation({
    mutationFn: async (uuid: string | string[]): Promise<void> => {
      if (Array.isArray(uuid)) {
        await Promise.all(uuid.map((item) => axios.delete(`/company-data/data/admin/${item}`)));
        return;
      }

      await axios.delete(`/company-data/data/admin/${uuid}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const restoreCompanyData = useMutation({
    mutationFn: async (uuid: string | string[]): Promise<void> => {
      if (Array.isArray(uuid)) {
        await Promise.all(uuid.map((item) => axios.patch(`/company-data/data/admin/${item}/restore`)));
        return;
      }

      await axios.patch(`/company-data/data/admin/${uuid}/restore`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  return {
    createCompanyData,
    updateCompanyData,
    deleteCompanyData,
    restoreCompanyData,
  };
};
