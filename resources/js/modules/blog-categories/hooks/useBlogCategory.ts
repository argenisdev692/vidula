import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import type { BlogCategoryDetail } from '@/types/blog-categories';

/**
 * useBlogCategory — Fetches a single blog category by UUID.
 */
export function useBlogCategory(uuid?: string) {
  return useQuery({
    queryKey: ['blogCategories', uuid],
    queryFn: async () => {
      if (!uuid) return null;
      const { data } = await axios.get<{ data: BlogCategoryDetail }>(
        `/blog-categories/data/admin/${uuid}`,
      );
      return data.data;
    },
    enabled: !!uuid,
  });
}
