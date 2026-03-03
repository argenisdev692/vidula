import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import type { CreateClientDTO, UpdateClientDTO } from '@/types/api';

/**
 * useClientMutations — Mutations for client data.
 * queryKey: ['clients'] — matches useClients hook.
 */
export const useClientMutations = () => {
  const queryClient = useQueryClient();

  const createClient = useMutation({
    mutationFn: (payload: CreateClientDTO) => {
      return axios.post('/clients/data/admin', payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['clients'] });
    },
  });

  const updateClient = useMutation({
    mutationFn: ({ userUuid, payload }: { userUuid?: string; payload: UpdateClientDTO }) => {
      const url = userUuid ? `/clients/data/admin/${userUuid}` : '/clients/data/me';
      return axios.put(url, payload);
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['client', variables.userUuid || 'me'] });
      queryClient.invalidateQueries({ queryKey: ['clients'] });
    },
  });

  const deleteClient = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.delete(`/clients/data/admin/${uuids}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['clients'] });
    },
  });

  const restoreClient = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.patch(`/clients/data/admin/${uuids}/restore`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['clients'] });
    },
  });

  return {
    createClient,
    updateClient,
    deleteClient,
    restoreClient,
  };
};
