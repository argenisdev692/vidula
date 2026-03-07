import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios, { type AxiosError } from 'axios';
import { sileo } from 'sileo';
import type { CreateRolePayload, UpdateRolePayload } from '@/types/roles';

function getErrorMessage(error: AxiosError | Error, defaultMessage: string): string {
  const axiosError = error as AxiosError<{ message?: string }>;
  return axiosError.response?.data?.message ?? error.message ?? defaultMessage;
}

export function useRoleMutations() {
  const queryClient = useQueryClient();

  const createRole = useMutation({
    mutationFn: (payload: CreateRolePayload) => axios.post('/roles/data/admin', payload),
    onSuccess: () => {
      sileo.success({ title: 'Role created successfully' });
      queryClient.invalidateQueries({ queryKey: ['roles'] });
    },
    onError: (error: AxiosError | Error) => {
      sileo.error({ title: getErrorMessage(error, 'Failed to create role') });
    },
  });

  const updateRole = useMutation({
    mutationFn: ({ uuid, payload }: { uuid: string; payload: UpdateRolePayload }) =>
      axios.put(`/roles/data/admin/${uuid}`, payload),
    onSuccess: async (_, variables) => {
      sileo.success({ title: 'Role updated successfully' });
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['roles'] }),
        queryClient.invalidateQueries({ queryKey: ['roles', variables.uuid] }),
      ]);
    },
    onError: (error: AxiosError | Error) => {
      sileo.error({ title: getErrorMessage(error, 'Failed to update role') });
    },
  });

  const deleteRole = useMutation({
    mutationFn: (uuid: string) => axios.delete(`/roles/data/admin/${uuid}`),
    onSuccess: () => {
      sileo.success({ title: 'Role deleted successfully' });
      queryClient.invalidateQueries({ queryKey: ['roles'] });
    },
    onError: (error: AxiosError | Error) => {
      sileo.error({ title: getErrorMessage(error, 'Failed to delete role') });
    },
  });

  return {
    createRole,
    updateRole,
    deleteRole,
  };
}
