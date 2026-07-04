import { computed, type ComputedRef } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types/inertia';
import type { PermissionInput } from '@/modules/auth/types';

interface UseAuthorization {
    permissions: ComputedRef<string[]>;
    roles: ComputedRef<string[]>;
    primaryRole: ComputedRef<string>;
    hasPermission: (required?: PermissionInput, requireAll?: boolean) => boolean;
    hasRole: (role: string) => boolean;
}

function toArray(input?: PermissionInput): string[] {
    if (input === undefined) {
        return [];
    }
    return Array.isArray(input) ? [...input] : [input as string];
}

/**
 * Permission/role helpers backed by Inertia shared props (Spatie permission).
 * UI authorization is ALWAYS permission-based (never role-based) except for the
 * cosmetic `primaryRole` label — see OWASP/SKILL.md.
 */
export function useAuthorization(): UseAuthorization {
    const page = usePage<SharedProps>();

    const permissions = computed<string[]>(() => page.props.auth.permissions);
    const roles = computed<string[]>(() => page.props.auth.roles);
    const primaryRole = computed<string>(() => roles.value[0] ?? 'User');

    function hasPermission(required?: PermissionInput, requireAll = false): boolean {
        const needed = toArray(required);
        // No requirement → always allowed (public/authenticated-only UI).
        if (needed.length === 0) {
            return true;
        }
        const held = permissions.value;
        return requireAll
            ? needed.every((p) => held.includes(p))
            : needed.some((p) => held.includes(p));
    }

    function hasRole(role: string): boolean {
        return roles.value.includes(role);
    }

    return { permissions, roles, primaryRole, hasPermission, hasRole };
}
