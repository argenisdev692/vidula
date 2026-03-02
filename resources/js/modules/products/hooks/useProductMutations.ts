import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { CreateProductDTO, UpdateProductDTO } from '@/types/api';

/**
 * useProductMutations — Mutations for updating company data.
 */
export const useProductMutations = () => {
  const queryClient = useQueryClient();

  const createProduct = useMutation({
    mutationFn: (payload: CreateProductDTO) => {
      return axios.post('/product/data/admin', payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const updateProduct = useMutation({
    mutationFn: ({ userUuid, payload }: { userUuid?: string; payload: UpdateProductDTO }) => {
      const url = userUuid ? `/product/data/admin/${userUuid}` : '/product/data/me';
      return axios.put(url, payload);
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['product', variables.userUuid || 'me'] });
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const deleteProduct = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.delete(`/product/data/admin/${uuids}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const restoreProduct = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.patch(`/product/data/admin/${uuids}/restore`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  return {
    createProduct,
    updateProduct,
    deleteProduct,
    restoreProduct,
  };
};
