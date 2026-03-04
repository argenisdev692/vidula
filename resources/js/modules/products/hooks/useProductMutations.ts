import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios, { type AxiosError } from 'axios';
import { sileo } from 'sileo';

/**
 * MANDATORY: Helper to safely extract the best error message from Axios
 * This prevents showing generic "Request failed with status code 422" messages.
 */
function getErrorMessage(err: AxiosError | any, defaultMsg: string): string {
  if (err?.response?.data?.message) {
      return err.response.data.message;
  }
  return err?.message || defaultMsg;
}

/**
 * useProductMutations — Mutations for Products module.
 * QueryKey: ['products'] — matches useProducts hook.
 */
export const useProductMutations = () => {
  const queryClient = useQueryClient();

  const createProduct = useMutation({
    mutationFn: (payload: any) => axios.post('/products/data/admin', payload),
    onSuccess: () => {
      sileo.success({ title: 'Product created successfully' });
      queryClient.invalidateQueries({ queryKey: ['products'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to create product') });
    },
  });

  const updateProduct = useMutation({
    mutationFn: ({ userUuid, payload }: { userUuid?: string; payload: any }) => {
      const url = userUuid ? `/products/data/admin/${userUuid}` : '/products/data/me';
      return axios.put(url, payload);
    },
    onSuccess: (_, variables) => {
      sileo.success({ title: 'Product updated successfully' });
      queryClient.invalidateQueries({ queryKey: ['products', variables.userUuid || 'me'] });
      queryClient.invalidateQueries({ queryKey: ['products'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to update product') });
    },
  });

  const deleteProduct = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.delete(`/products/data/admin/${uuids}`);
    },
    onSuccess: () => {
      sileo.success({ title: 'Product deleted successfully' });
      queryClient.invalidateQueries({ queryKey: ['products'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to delete product') });
    },
  });

  const restoreProduct = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.patch(`/products/data/admin/${uuids}/restore`);
    },
    onSuccess: () => {
      sileo.success({ title: 'Product restored successfully' });
      queryClient.invalidateQueries({ queryKey: ['products'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to restore product') });
    },
  });

  return {
    createProduct,
    updateProduct,
    deleteProduct,
    restoreProduct,
  };
};
