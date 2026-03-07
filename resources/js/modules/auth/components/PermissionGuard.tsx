/* ══════════════════════════════════════════════════════════════════
  PermissionGuard — Conditional rendering by permission
  Per ARQUITECTURE-REACT-INERTIA.md — modules/auth/components/
  ══════════════════════════════════════════════════════════════════ */
import { useAuthorization } from '@/modules/auth/hooks/useAuthorization';

interface PermissionGuardProps {
  /** Required permission(s) — user must have at least one */
  permissions?: string[];
  /** Content to render if authorized */
  children: React.ReactNode;
  /** Optional fallback when unauthorized (defaults to null) */
  fallback?: React.ReactNode;
}

export function PermissionGuard({
  permissions,
  children,
  fallback = null,
}: PermissionGuardProps): React.JSX.Element | null {
  const { can } = useAuthorization();

  if (!can({ permissions })) {
    return fallback as React.JSX.Element | null;
  }

  return children as React.JSX.Element;
}
