import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { StudentDetail } from '@/types/api';

/**
 * useSingleStudent — Fetches a single student profile by UUID or for the current user.
 */
export const useSingleStudent = (uuid?: string) => {
  return useQuery({
    queryKey: ['student', uuid || 'me'],
    queryFn: async () => {
      // Backend controller: show(Request $request, ?string $uuid = null)
      const url = uuid ? `/student/data/admin/${uuid}` : '/student/data/me';
      const { data } = await axios.get<{ data: StudentDetail }>(url);
      return data.data;
    },
    // Fetch when uuid is provided, or always (for "me")
    enabled: true,
  });
};

// Alias for backward compatibility
export const useStudent = useSingleStudent;
