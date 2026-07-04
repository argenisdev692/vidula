import type { AuthUser } from '@/types/inertia';

export type { AuthUser };

/** A single permission requirement — one name or a set to match against. */
export type PermissionInput = string | readonly string[];
