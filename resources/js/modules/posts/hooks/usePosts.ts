import { keepPreviousData, useQuery } from '@tanstack/react-query';
import axios from 'axios';
import type { PaginatedResponse, PostFilters, PostListItem } from '@/types/posts';

export function usePosts(filters: PostFilters = {}) {
  return useQuery<PaginatedResponse<PostListItem>, Error>({
    queryKey: ['posts', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<PostListItem>>('/posts/data/admin', {
        params: filters,
      });
      return data;
    },
    placeholderData: keepPreviousData,
    staleTime: 1000 * 60 * 2,
  });
}
