import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axios from 'axios';
import type { UserFilters, UserListItem, PaginatedResponse } from '@/types/users';

/**
 * useUsers — Returns a paginated list of users filtered by the provided filters.
 *
 * Uses `placeholderData: keepPreviousData` (TanStack Query v5)
 * and `staleTime: 2 min` for optimal UX.
 */
export function useUsers(filters: UserFilters = {}) {
  return useQuery<PaginatedResponse<UserListItem>, Error>({
    queryKey: ['users', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<UserListItem>>('/users/data/admin', {
        params: filters,
      });
      return data;
    },
    placeholderData: keepPreviousData,
    staleTime: 1000 * 60 * 2,
  });
}
