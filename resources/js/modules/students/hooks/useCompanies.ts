import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { StudentListItem, StudentFilters, PaginatedResponse } from '@/types/api';

/**
 * useCompanies — Fetches a paginated list of company profiles.
 */
export const useCompanies = (filters: StudentFilters) => {
  return useQuery({
    queryKey: ['companies', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<StudentListItem>>('/student/data/admin', {
        params: filters
      });
      return data;
    },
    placeholderData: (previousData) => previousData,
  });
};
