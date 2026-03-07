import { keepPreviousData, useQuery } from '@tanstack/react-query';
import axios from 'axios';
import type { PaginatedResponse, PermissionFilters, PermissionListItem } from '@/types/permissions';

export function usePermissions(filters: PermissionFilters = {}) {
  return useQuery<PaginatedResponse<PermissionListItem>, Error>({
    queryKey: ['permissions', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<PermissionListItem>>('/permissions/data/admin', {
        params: filters,
      });

      return data;
    },
    placeholderData: keepPreviousData,
    staleTime: 1000 * 60 * 2,
  });
}
