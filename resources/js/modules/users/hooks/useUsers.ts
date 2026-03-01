import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { UserFilters, UserListItem, PaginatedResponse } from '@/types/users';

/**
 * useUsers — Returns a list of users filtered by the provided filters.
 */
export const useUsers = (filters: UserFilters = {}) => {
  return useQuery({
    queryKey: ['users', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<UserListItem>>('/users/data/admin', {
        params: filters,
      });
      return data;
    },
  });
};
