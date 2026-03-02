import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { StudentDetail } from '@/types/api';

/**
 * useSingleStudent — Fetches a single company profile by UUID or for the current user.
 */
export const useSingleStudent = (uuid?: string) => {
  return useQuery({
    queryKey: ['student', uuid || 'me'],
    queryFn: async () => {
      // Backend controller: show(Request $request, ?string $uuid = null)
      // If uuid is null, it uses $request->user()?->uuid
      const url = uuid ? `/student/data/admin/${uuid}` : '/student/data/me';
      const { data } = await axios.get<{ data: StudentDetail }>(url);
      return data.data;
    },
    enabled: !!uuid || true, // Always fetch if no uuid (me), or if uuid exists
  });
};

// Alias for backward compatibility if needed, but we prefer useSingleStudent
export const useStudent = useSingleStudent;
