import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios, { type AxiosError } from 'axios';
import { sileo } from 'sileo';
import type { CreateClientDTO, UpdateClientDTO } from '@/types/api';

/**
 * Safely extract the best error message from Axios responses.
 */
function getErrorMessage(err: AxiosError<{ message?: string }> | Error, defaultMsg: string): string {
  if ('response' in err && err.response?.data?.message) {
    return err.response.data.message;
  }
  return err?.message || defaultMsg;
}

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
      sileo.success({ title: 'Client created successfully' });
      queryClient.invalidateQueries({ queryKey: ['clients'] });
    },
    onError: (err: AxiosError<{ message?: string }>) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to create client') });
    },
  });

  const updateClient = useMutation({
    mutationFn: ({ userUuid, payload }: { userUuid?: string; payload: UpdateClientDTO }) => {
      const url = userUuid ? `/clients/data/admin/${userUuid}` : '/clients/data/me';
      return axios.put(url, payload);
    },
    onSuccess: (_, variables) => {
      sileo.success({ title: 'Client updated successfully' });
      queryClient.invalidateQueries({ queryKey: ['client', variables.userUuid || 'me'] });
      queryClient.invalidateQueries({ queryKey: ['clients'] });
    },
    onError: (err: AxiosError<{ message?: string }>) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to update client') });
    },
  });

  const deleteClient = useMutation({
    mutationFn: (uuid: string) => {
      return axios.delete(`/clients/data/admin/${uuid}`);
    },
    onSuccess: () => {
      sileo.success({ title: 'Client deleted successfully' });
      queryClient.invalidateQueries({ queryKey: ['clients'] });
    },
    onError: (err: AxiosError<{ message?: string }>) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to delete client') });
    },
  });

  const restoreClient = useMutation({
    mutationFn: (uuid: string) => {
      return axios.patch(`/clients/data/admin/${uuid}/restore`);
    },
    onSuccess: () => {
      sileo.success({ title: 'Client restored successfully' });
      queryClient.invalidateQueries({ queryKey: ['clients'] });
    },
    onError: (err: AxiosError<{ message?: string }>) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to restore client') });
    },
  });

  return {
    createClient,
    updateClient,
    deleteClient,
    restoreClient,
  };
};
