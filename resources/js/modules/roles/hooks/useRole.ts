import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import type { RoleDetail } from '@/types/roles';

export function useRole(uuid?: string) {
  return useQuery<RoleDetail | null, Error>({
    queryKey: ['roles', uuid],
    queryFn: async () => {
      if (!uuid) {
        return null;
      }

      const { data } = await axios.get<{ data: RoleDetail }>(`/roles/data/admin/${uuid}`);
      return data.data;
    },
    enabled: Boolean(uuid),
  });
}
