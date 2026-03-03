import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import type { StudentDetail } from '@/types/api';

/**
 * useStudent — Fetches a single student profile by UUID.
 * Per §6: single-object API, explicit types, queryKey convention.
 */
export function useStudent(uuid?: string) {
  return useQuery<StudentDetail, Error>({
    queryKey: ['student', uuid],
    queryFn: async () => {
      const { data } = await axios.get<{ data: StudentDetail }>(
        `/students/data/admin/${uuid}`
      );
      return data.data;
    },
    enabled: !!uuid,
  });
}
