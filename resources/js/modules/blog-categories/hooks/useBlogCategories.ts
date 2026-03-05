import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axios from 'axios';
import type { BlogCategoryFilters, BlogCategoryListItem, PaginatedResponse } from '@/types/blog-categories';

/**
 * useBlogCategories — Returns a paginated list of blog categories.
 *
 * Uses `placeholderData: keepPreviousData` (TanStack Query v5)
 * and `staleTime: 2 min` for optimal UX.
 */
export function useBlogCategories(filters: BlogCategoryFilters = {}) {
  return useQuery<PaginatedResponse<BlogCategoryListItem>, Error>({
    queryKey: ['blogCategories', filters],
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<BlogCategoryListItem>>(
        '/blog-categories/data/admin',
        { params: filters },
      );
      return data;
    },
    placeholderData: keepPreviousData,
    staleTime: 1000 * 60 * 2,
  });
}
