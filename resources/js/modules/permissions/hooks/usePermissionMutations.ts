import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios, { type AxiosError } from 'axios';
import { sileo } from 'sileo';
import type { CreatePermissionPayload, UpdatePermissionPayload } from '@/types/permissions';

function getErrorMessage(error: AxiosError | Error, defaultMessage: string): string {
  const axiosError = error as AxiosError<{ message?: string }>;
  return axiosError.response?.data?.message ?? error.message ?? defaultMessage;
}

export function usePermissionMutations() {
  const queryClient = useQueryClient();

  const createPermission = useMutation({
    mutationFn: (payload: CreatePermissionPayload) => axios.post('/permissions/data/admin', payload),
    onSuccess: () => {
      sileo.success({ title: 'Permission created successfully' });
      queryClient.invalidateQueries({ queryKey: ['permissions'] });
    },
    onError: (error: AxiosError | Error) => {
      sileo.error({ title: getErrorMessage(error, 'Failed to create permission') });
    },
  });

  const updatePermission = useMutation({
    mutationFn: ({ uuid, payload }: { uuid: string; payload: UpdatePermissionPayload }) =>
      axios.put(`/permissions/data/admin/${uuid}`, payload),
    onSuccess: async (_, variables) => {
      sileo.success({ title: 'Permission updated successfully' });
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['permissions'] }),
        queryClient.invalidateQueries({ queryKey: ['permissions', variables.uuid] }),
      ]);
    },
    onError: (error: AxiosError | Error) => {
      sileo.error({ title: getErrorMessage(error, 'Failed to update permission') });
    },
  });

  const deletePermission = useMutation({
    mutationFn: (uuid: string) => axios.delete(`/permissions/data/admin/${uuid}`),
    onSuccess: () => {
      sileo.success({ title: 'Permission deleted successfully' });
      queryClient.invalidateQueries({ queryKey: ['permissions'] });
    },
    onError: (error: AxiosError | Error) => {
      sileo.error({ title: getErrorMessage(error, 'Failed to delete permission') });
    },
  });

  return {
    createPermission,
    updatePermission,
    deletePermission,
  };
}
