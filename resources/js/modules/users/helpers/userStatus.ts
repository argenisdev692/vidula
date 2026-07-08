import type { User, UserStatus } from '../types';

/**
 * Derives the visible lifecycle state of a user row from the lean, non-secret
 * columns the list exposes. The backend truth is `password === null ⇒ pending`,
 * but the password is `$hidden`; since the invite flow verifies the email at the
 * same moment it sets the password, `email_verified_at` is the accurate visible
 * proxy for "pending". Soft-deletion always wins.
 */
export function resolveUserStatus(user: Pick<User, 'deleted_at' | 'email_verified_at'>): UserStatus {
    if (user.deleted_at !== null) {
        return 'suspended';
    }
    if (user.email_verified_at === null) {
        return 'pending';
    }
    return 'active';
}

/** Presentation metadata per status — label + token-driven badge modifier class. */
export const USER_STATUS_META: Record<UserStatus, { label: string; className: string }> = {
    pending: { label: 'Pending', className: 'badge--pending' },
    active: { label: 'Active', className: 'badge--active' },
    suspended: { label: 'Suspended', className: 'badge--suspended' },
};
