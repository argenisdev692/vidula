import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios, { type AxiosError } from 'axios';
import { sileo } from 'sileo';
import type { CreateBlogCategoryPayload, UpdateBlogCategoryPayload } from '@/types/blog-categories';

/**
 * Helper to safely extract the best error message from Axios.
 */
function getErrorMessage(err: AxiosError | unknown, defaultMsg: string): string {
  const axiosErr = err as AxiosError<{ message?: string }>;
  if (axiosErr?.response?.data?.message) {
    return axiosErr.response.data.message;
  }
  return (err as Error)?.message || defaultMsg;
}

/**
 * useBlogCategoryMutations — Provides create/update/delete/restore mutations.
 */
export function useBlogCategoryMutations() {
  const queryClient = useQueryClient();

  const createBlogCategory = useMutation({
    mutationFn: (payload: CreateBlogCategoryPayload) =>
      axios.post('/blog-categories/data/admin', payload),
    onSuccess: () => {
      sileo.success({ title: 'Category created successfully' });
      queryClient.invalidateQueries({ queryKey: ['blogCategories'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to create category') });
    },
  });

  const updateBlogCategory = useMutation({
    mutationFn: ({ uuid, payload }: { uuid: string; payload: UpdateBlogCategoryPayload }) =>
      axios.put(`/blog-categories/data/admin/${uuid}`, payload),
    onSuccess: (_, variables) => {
      sileo.success({ title: 'Category updated successfully' });
      queryClient.invalidateQueries({ queryKey: ['blogCategories'] });
      queryClient.invalidateQueries({ queryKey: ['blogCategories', variables.uuid] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to update category') });
    },
  });

  const deleteBlogCategory = useMutation({
    mutationFn: (uuid: string) => axios.delete(`/blog-categories/data/admin/${uuid}`),
    onSuccess: () => {
      sileo.success({ title: 'Category deleted successfully' });
      queryClient.invalidateQueries({ queryKey: ['blogCategories'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to delete category') });
    },
  });

  const restoreBlogCategory = useMutation({
    mutationFn: (uuid: string) => axios.patch(`/blog-categories/data/admin/${uuid}/restore`),
    onSuccess: () => {
      sileo.success({ title: 'Category restored successfully' });
      queryClient.invalidateQueries({ queryKey: ['blogCategories'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to restore category') });
    },
  });

  return {
    createBlogCategory,
    updateBlogCategory,
    deleteBlogCategory,
    restoreBlogCategory,
  };
}
