import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios, { type AxiosError } from 'axios';
import { sileo } from 'sileo';
import type { CreateUserPayload, UpdateUserPayload } from '@/types/users';

/**
 * useUserMutations — Provides mutations for CRUD + status management.
 *
 * Every mutation includes:
 * - onSuccess → toast.success() + queryClient.invalidateQueries()
 * - onError → toast.error()
 */
/**
 * Helper to safely extract the best error message from Axios
 */
function getErrorMessage(err: AxiosError | any, defaultMsg: string): string {
  if (err?.response?.data?.message) {
    return err.response.data.message;
  }
  return err?.message || defaultMsg;
}

export function useUserMutations() {
  const queryClient = useQueryClient();

  const createUser = useMutation({
    mutationFn: (payload: CreateUserPayload) => axios.post('/users/data/admin', payload),
    onSuccess: () => {
      sileo.success({ title: 'User created successfully' });
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to create user') });
    },
  });

  const updateUser = useMutation({
    mutationFn: ({ uuid, payload }: { uuid: string; payload: UpdateUserPayload }) =>
      axios.put(`/users/data/admin/${uuid}`, payload),
    onSuccess: (_, variables) => {
      sileo.success({ title: 'User updated successfully' });
      queryClient.invalidateQueries({ queryKey: ['users'] });
      queryClient.invalidateQueries({ queryKey: ['users', variables.uuid] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to update user') });
    },
  });

  const deleteUser = useMutation({
    mutationFn: (uuid: string) => axios.delete(`/users/data/admin/${uuid}`),
    onSuccess: () => {
      sileo.success({ title: 'User deleted successfully' });
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to delete user') });
    },
  });

  const restoreUser = useMutation({
    mutationFn: (uuid: string) => axios.patch(`/users/data/admin/${uuid}/restore`),
    onSuccess: () => {
      sileo.success({ title: 'User restored successfully' });
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to restore user') });
    },
  });

  const suspendUser = useMutation({
    mutationFn: (uuid: string) => axios.post(`/users/data/admin/${uuid}/suspend`),
    onSuccess: () => {
      sileo.success({ title: 'User suspended' });
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to suspend user') });
    },
  });

  const activateUser = useMutation({
    mutationFn: (uuid: string) => axios.post(`/users/data/admin/${uuid}/activate`),
    onSuccess: () => {
      sileo.success({ title: 'User activated' });
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to activate user') });
    },
  });

  return {
    createUser,
    updateUser,
    deleteUser,
    restoreUser,
    suspendUser,
    activateUser,
  };
}
