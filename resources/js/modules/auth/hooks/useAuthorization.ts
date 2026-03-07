import { useCurrentUser } from './useCurrentUser';

interface AuthorizationRequirements {
  permissions?: readonly string[];
}

export interface AuthorizationResult {
  userPermissions: string[];
  hasPermission: (permission: string) => boolean;
  hasAnyPermission: (permissions: readonly string[]) => boolean;
  can: (requirements: AuthorizationRequirements) => boolean;
}

export function useAuthorization(): AuthorizationResult {
  const user = useCurrentUser();
  const userPermissions = user?.permissions ?? [];

  function hasPermission(permission: string): boolean {
    return userPermissions.includes(permission);
  }

  function hasAnyPermission(permissions: readonly string[]): boolean {
    return permissions.some((permission) => hasPermission(permission));
  }

  function can({ permissions }: AuthorizationRequirements): boolean {
    if (!user) {
      return false;
    }

    if (permissions && permissions.length > 0 && !hasAnyPermission(permissions)) {
      return false;
    }

    return true;
  }

  return {
    userPermissions,
    hasPermission,
    hasAnyPermission,
    can,
  };
}
