import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

/**
 * useProductMutations — Mutations for Products module.
 * QueryKey: ['products'] — matches useProducts hook.
 */
export const useProductMutations = () => {
  const queryClient = useQueryClient();

  const createProduct = useMutation({
    mutationFn: (payload: Record<string, unknown>) => {
      return axios.post('/products/data/admin', payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] });
    },
  });

  const updateProduct = useMutation({
    mutationFn: ({ userUuid, payload }: { userUuid?: string; payload: Record<string, unknown> }) => {
      const url = userUuid ? `/products/data/admin/${userUuid}` : '/products/data/me';
      return axios.put(url, payload);
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['product', variables.userUuid || 'me'] });
      queryClient.invalidateQueries({ queryKey: ['products'] });
    },
  });

  const deleteProduct = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.delete(`/products/data/admin/${uuids}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] });
    },
  });

  const restoreProduct = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.patch(`/products/data/admin/${uuids}/restore`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] });
    },
  });

  return {
    createProduct,
    updateProduct,
    deleteProduct,
    restoreProduct,
  };
};

