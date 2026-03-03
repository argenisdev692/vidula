import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import type { CreateStudentDTO, UpdateStudentDTO } from '@/types/api';

/**
 * useStudentMutations — CRUD mutations for Students.
 * Per §6: TanStack Query v5, invalidateQueries on success.
 */
export function useStudentMutations() {
  const queryClient = useQueryClient();

  const createStudent = useMutation({
    mutationFn: (payload: CreateStudentDTO) =>
      axios.post('/students/data/admin', payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['students'] });
    },
  });

  const updateStudent = useMutation({
    mutationFn: ({ uuid, payload }: { uuid: string; payload: UpdateStudentDTO }) =>
      axios.put(`/students/data/admin/${uuid}`, payload),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['student', variables.uuid] });
      queryClient.invalidateQueries({ queryKey: ['students'] });
    },
  });

  const deleteStudent = useMutation({
    mutationFn: (uuid: string) =>
      axios.delete(`/students/data/admin/${uuid}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['students'] });
    },
  });

  const restoreStudent = useMutation({
    mutationFn: (uuid: string) =>
      axios.patch(`/students/data/admin/${uuid}/restore`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['students'] });
    },
  });

  return { createStudent, updateStudent, deleteStudent, restoreStudent };
}
