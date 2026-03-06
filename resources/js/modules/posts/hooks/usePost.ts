import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import type { PostDetail } from '@/types/posts';

export function usePost(uuid?: string) {
  return useQuery({
    queryKey: ['posts', uuid],
    queryFn: async () => {
      if (!uuid) return null;
      const { data } = await axios.get<{ data: PostDetail }>(`/posts/data/admin/${uuid}`);
      return data.data;
    },
    enabled: !!uuid,
  });
}
