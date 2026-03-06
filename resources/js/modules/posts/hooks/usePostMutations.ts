import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios, { type AxiosError } from 'axios';
import { sileo } from 'sileo';
import type { CreatePostPayload, UpdatePostPayload } from '@/types/posts';

function getErrorMessage(err: AxiosError | unknown, defaultMsg: string): string {
  const axiosErr = err as AxiosError<{ message?: string }>;
  if (axiosErr?.response?.data?.message) {
    return axiosErr.response.data.message;
  }
  return (err as Error)?.message || defaultMsg;
}

export function usePostMutations() {
  const queryClient = useQueryClient();

  const createPost = useMutation({
    mutationFn: (payload: CreatePostPayload) => axios.post('/posts/data/admin', payload),
    onSuccess: () => {
      sileo.success({ title: 'Post created successfully' });
      queryClient.invalidateQueries({ queryKey: ['posts'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to create post') });
    },
  });

  const updatePost = useMutation({
    mutationFn: ({ uuid, payload }: { uuid: string; payload: UpdatePostPayload }) =>
      axios.put(`/posts/data/admin/${uuid}`, payload),
    onSuccess: (_, variables) => {
      sileo.success({ title: 'Post updated successfully' });
      queryClient.invalidateQueries({ queryKey: ['posts'] });
      queryClient.invalidateQueries({ queryKey: ['posts', variables.uuid] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to update post') });
    },
  });

  const deletePost = useMutation({
    mutationFn: (uuid: string) => axios.delete(`/posts/data/admin/${uuid}`),
    onSuccess: () => {
      sileo.success({ title: 'Post deleted successfully' });
      queryClient.invalidateQueries({ queryKey: ['posts'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to delete post') });
    },
  });

  const restorePost = useMutation({
    mutationFn: (uuid: string) => axios.patch(`/posts/data/admin/${uuid}/restore`),
    onSuccess: () => {
      sileo.success({ title: 'Post restored successfully' });
      queryClient.invalidateQueries({ queryKey: ['posts'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to restore post') });
    },
  });

  return {
    createPost,
    updatePost,
    deletePost,
    restorePost,
  };
}
