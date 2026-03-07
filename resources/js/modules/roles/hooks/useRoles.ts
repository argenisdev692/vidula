import { keepPreviousData, useQuery } from '@tanstack/react-query';
import axios from 'axios';
import type { PaginatedResponse, RoleFilters, RoleListItem } from '@/types/roles';

export function useRoles(filters: RoleFilters = {}) {
  return useQuery<PaginatedResponse<RoleListItem>, Error>({
    queryKey: ['roles', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<RoleListItem>>('/roles/data/admin', {
        params: filters,
      });

      return data;
    },
    placeholderData: keepPreviousData,
    staleTime: 1000 * 60 * 2,
  });
}
