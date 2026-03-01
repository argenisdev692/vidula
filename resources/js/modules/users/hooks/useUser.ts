import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { UserDetail } from '@/types/users';

/**
 * useUser — Fetches a single user by UUID.
 */
export const useUser = (uuid?: string) => {
  return useQuery({
    queryKey: ['users', uuid],
    queryFn: async () => {
      if (!uuid) return null;
      const { data } = await axios.get<{ data: UserDetail }>(`/users/data/admin/${uuid}`);
      return data.data;
    },
    enabled: !!uuid,
  });
};
