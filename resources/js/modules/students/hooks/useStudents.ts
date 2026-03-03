import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { StudentListItem, StudentFilters, PaginatedResponse } from '@/types/api';

/**
 * useStudents — Fetches a paginated list of student profiles.
 */
export const useStudents = (filters: StudentFilters) => {
  return useQuery({
    queryKey: ['students', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<StudentListItem>>('/students/data/admin', {
        params: filters
      });
      return data;
    },
    placeholderData: (previousData) => previousData,
  });
};
