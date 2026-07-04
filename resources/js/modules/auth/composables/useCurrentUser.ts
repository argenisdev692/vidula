import { computed, type ComputedRef } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { AuthUser, SharedProps } from '@/types/inertia';

interface UseCurrentUser {
    user: ComputedRef<AuthUser | null>;
    isAuthenticated: ComputedRef<boolean>;
    fullName: ComputedRef<string>;
    initials: ComputedRef<string>;
    profilePhotoUrl: ComputedRef<string | null>;
}

/**
 * Reads the authenticated user from Inertia shared props. The single accessor
 * for `auth.user` across the app — components never touch usePage() directly.
 */
export function useCurrentUser(): UseCurrentUser {
    const page = usePage<SharedProps>();

    const user = computed<AuthUser | null>(() => page.props.auth.user);
    const isAuthenticated = computed<boolean>(() => user.value !== null);
    const profilePhotoUrl = computed<string | null>(() => page.props.auth.profile_photo_url);

    const fullName = computed<string>(() => {
        const u = user.value;
        if (!u) {
            return '';
        }
        return `${u.first_name} ${u.last_name}`.trim();
    });

    const initials = computed<string>(() => {
        const u = user.value;
        if (!u) {
            return '?';
        }
        const first = u.first_name.charAt(0);
        const last = u.last_name.charAt(0);
        return (first + last).toUpperCase() || u.email.charAt(0).toUpperCase();
    });

    return { user, isAuthenticated, fullName, initials, profilePhotoUrl };
}
