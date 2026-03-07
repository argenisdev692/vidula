import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import type { PermissionDetail } from '@/types/permissions';

export function usePermission(uuid?: string) {
  return useQuery<PermissionDetail | null, Error>({
    queryKey: ['permissions', uuid],
    queryFn: async () => {
      if (!uuid) {
        return null;
      }

      const { data } = await axios.get<{ data: PermissionDetail }>(`/permissions/data/admin/${uuid}`);
      return data.data;
    },
    enabled: Boolean(uuid),
  });
}
