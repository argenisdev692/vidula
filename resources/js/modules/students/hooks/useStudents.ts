import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axios from 'axios';
import type { StudentListItem, StudentFilters, PaginatedResponse } from '@/types/api';

/**
 * useStudents — Paginated list of students.
 * Per §6: keepPreviousData (v5), explicit generics, staleTime for list.
 */
export function useStudents(filters: StudentFilters) {
  return useQuery<PaginatedResponse<StudentListItem>, Error>({
    queryKey: ['students', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<StudentListItem>>(
        '/students/data/admin',
        { params: filters }
      );
      return data;
    },
    placeholderData: keepPreviousData,
    staleTime: 1000 * 60 * 2,
  });
}
