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
    mutationFn: ({ userUuid, payload }: { userUuid?: string; payload: UpdateCompanyDataDTO }) => {
      const url = userUuid ? `/company-data/data/admin/${userUuid}` : '/company-data/data/me';
      return axios.put(url, payload);
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['company-data', variables.userUuid || 'me'] });
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const deleteCompanyData = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.delete(`/company-data/data/admin/${uuids}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const restoreCompanyData = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.patch(`/company-data/data/admin/${uuids}/restore`);
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
