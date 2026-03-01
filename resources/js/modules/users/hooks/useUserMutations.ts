import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { CreateUserPayload, UpdateUserPayload } from '@/types/users';

/**
 * useUserMutations — Provides mutations for creating, updating, and status management.
 */
export const useUserMutations = () => {
  const queryClient = useQueryClient();

  const createUser = useMutation({
    mutationFn: (payload: CreateUserPayload) => axios.post('/users/data/admin', payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  });

  const updateUser = useMutation({
    mutationFn: ({ uuid, payload }: { uuid: string; payload: UpdateUserPayload }) =>
      axios.put(`/users/data/admin/${uuid}`, payload),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      queryClient.invalidateQueries({ queryKey: ['users', variables.uuid] });
    },
  });

  const deleteUser = useMutation({
    mutationFn: (uuid: string) => axios.delete(`/users/data/admin/${uuid}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  });

  const restoreUser = useMutation({
    mutationFn: (uuid: string) => axios.patch(`/users/data/admin/${uuid}/restore`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  });

  const suspendUser = useMutation({
    mutationFn: (uuid: string) => axios.post(`/users/data/admin/${uuid}/suspend`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  });

  const activateUser = useMutation({
    mutationFn: (uuid: string) => axios.post(`/users/data/admin/${uuid}/activate`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  });

  return {
    createUser,
    updateUser,
    deleteUser,
    restoreUser,
    suspendUser,
    activateUser,
  };
};
