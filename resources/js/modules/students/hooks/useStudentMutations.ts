import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios, { type AxiosError } from 'axios';
import { sileo } from 'sileo';
import type { CreateStudentDTO, UpdateStudentDTO } from '@/types/api';

/**
 * useStudentMutations — CRUD mutations for Students.
 * Per §6: TanStack Query v5, invalidateQueries on success.
 */
function getErrorMessage(err: AxiosError | any, defaultMsg: string): string {
  if (err?.response?.data?.message) {
    return err.response.data.message;
  }
  return err?.message || defaultMsg;
}

export function useStudentMutations() {
  const queryClient = useQueryClient();

  const createStudent = useMutation({
    mutationFn: (payload: CreateStudentDTO) =>
      axios.post('/students/data/admin', payload),
    onSuccess: () => {
      sileo.success({ title: 'Student created successfully' });
      queryClient.invalidateQueries({ queryKey: ['students'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to create student') });
    },
  });

  const updateStudent = useMutation({
    mutationFn: ({ uuid, payload }: { uuid: string; payload: UpdateStudentDTO }) =>
      axios.put(`/students/data/admin/${uuid}`, payload),
    onSuccess: (_, variables) => {
      sileo.success({ title: 'Student updated successfully' });
      queryClient.invalidateQueries({ queryKey: ['student', variables.uuid] });
      queryClient.invalidateQueries({ queryKey: ['students'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to update student') });
    },
  });

  const deleteStudent = useMutation({
    mutationFn: (uuid: string) =>
      axios.delete(`/students/data/admin/${uuid}`),
    onSuccess: () => {
      sileo.success({ title: 'Student deleted successfully' });
      queryClient.invalidateQueries({ queryKey: ['students'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to delete student') });
    },
  });

  const restoreStudent = useMutation({
    mutationFn: (uuid: string) =>
      axios.patch(`/students/data/admin/${uuid}/restore`),
    onSuccess: () => {
      sileo.success({ title: 'Student restored successfully' });
      queryClient.invalidateQueries({ queryKey: ['students'] });
    },
    onError: (err: AxiosError) => {
      sileo.error({ title: getErrorMessage(err, 'Failed to restore student') });
    },
  });

  return { createStudent, updateStudent, deleteStudent, restoreStudent };
}
