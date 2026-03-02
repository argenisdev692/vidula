import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { CreateStudentDTO, UpdateStudentDTO } from '@/types/api';

/**
 * useStudentMutations — Mutations for updating company data.
 */
export const useStudentMutations = () => {
  const queryClient = useQueryClient();

  const createStudent = useMutation({
    mutationFn: (payload: CreateStudentDTO) => {
      return axios.post('/student/data/admin', payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const updateStudent = useMutation({
    mutationFn: ({ userUuid, payload }: { userUuid?: string; payload: UpdateStudentDTO }) => {
      const url = userUuid ? `/student/data/admin/${userUuid}` : '/student/data/me';
      return axios.put(url, payload);
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['student', variables.userUuid || 'me'] });
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const deleteStudent = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.delete(`/student/data/admin/${uuids}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  const restoreStudent = useMutation({
    mutationFn: (uuid: string | string[]) => {
      const uuids = Array.isArray(uuid) ? uuid.join(',') : uuid;
      return axios.patch(`/student/data/admin/${uuids}/restore`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });

  return {
    createStudent,
    updateStudent,
    deleteStudent,
    restoreStudent,
  };
};
