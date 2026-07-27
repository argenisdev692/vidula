import { useMutation } from '@pinia/colada';
import { apiFetch } from '@/lib/http';
import type { GithubRepo } from '../types';

/**
 * Non-Inertia JSON call for GitHub repo listing (POST /resume-studio/github/repos).
 * Server state lives in Pinia Colada — not mirrored into a Pinia store.
 */
export function useGithubReposMutation() {
  return useMutation({
    mutation: (input: { github_username: string; selected_repos?: string[] }) =>
      apiFetch<{ data: GithubRepo[] }>('POST', '/resume-studio/github/repos', input),
  });
}
