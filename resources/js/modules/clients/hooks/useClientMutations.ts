import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { CreateClientDTO, UpdateClientDTO } from '@/types/api';

/**
 * useClientMutations — Mutations for updating company data.
 */
export const useClientMutations = () => {
  const queryClient = useQueryClient();

  const createClient = useMutation({
    mutationFn: (payload: CreateClientDTO) => {
      return axios.post('/client/data/admin', payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const updateClient = useMutation({
    mutationFn: ({ userUuid, payload }: { userUuid?: string; payload: UpdateClientDTO }) => {
      const url = userUuid ? `/client/data/admin/${userUuid}` : '/client/data/me';
      return axios.put(url, payload);
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['client', variables.userUuid || 'me'] });
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const deleteClient = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.delete(`/client/data/admin/${uuids}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const restoreClient = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.patch(`/client/data/admin/${uuids}/restore`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  return {
    createClient,
    updateClient,
    deleteClient,
    restoreClient,
  };
};
