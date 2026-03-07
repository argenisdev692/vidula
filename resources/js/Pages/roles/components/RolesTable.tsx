import * as React from 'react';
import { createColumnHelper } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { DataTable } from '@/common/data-table/DataTable';
import { formatDateShort } from '@/common/helpers/formatDate';
import { useAuthorization } from '@/modules/auth/hooks/useAuthorization';
import type { RoleListItem } from '@/types/roles';

interface RolesTableProps {
  data: RoleListItem[];
  isPending: boolean;
  isError?: boolean;
  onDelete: (uuid: string, name: string) => void;
}

const columnHelper = createColumnHelper<RoleListItem>();

export default function RolesTable({
  data,
  isPending,
  isError = false,
  onDelete,
}: RolesTableProps): React.JSX.Element {
  const { hasPermission } = useAuthorization();
  const canViewRoles = hasPermission('VIEW_ROLES');
  const canUpdateRoles = hasPermission('UPDATE_ROLES');
  const canDeleteRoles = hasPermission('DELETE_ROLES');

  const columns = React.useMemo(() => [
    columnHelper.accessor('name', {
      header: 'Role',
      cell: (info) => {
        const role = info.row.original;

        return (
          <div className="flex flex-col gap-1 text-left">
            <span className="text-sm font-semibold text-(--text-primary)">{role.name}</span>
            <span className="text-[11px] uppercase tracking-wide text-(--text-muted)">{role.guard_name}</span>
          </div>
        );
      },
    }),
    columnHelper.accessor('permissions', {
      header: 'Permissions',
      cell: (info) => {
        const permissions = info.getValue();

        if (permissions.length === 0) {
          return <span className="text-sm text-(--text-muted)">—</span>;
        }

        return (
          <div className="flex flex-wrap gap-2">
            {permissions.map((permission) => (
              <span
                key={permission}
                className="rounded-full border border-(--border-default) bg-(--bg-card) px-2.5 py-1 text-[11px] font-medium text-(--text-secondary)"
              >
                {permission}
              </span>
            ))}
          </div>
        );
      },
    }),
    columnHelper.accessor('users_count', {
      header: 'Users',
      cell: (info) => (
        <span className="text-sm font-medium text-(--text-secondary)">{info.getValue()}</span>
      ),
    }),
    columnHelper.accessor('created_at', {
      header: 'Created',
      cell: (info) => (
        <span className="text-sm text-(--text-muted)">{formatDateShort(info.getValue())}</span>
      ),
    }),
    ...((canViewRoles || canUpdateRoles || canDeleteRoles)
      ? [
          columnHelper.display({
            id: 'actions',
            header: 'Actions',
            cell: (info) => {
              const role = info.row.original;

              return (
                <div className="flex items-center justify-end gap-2 pr-4">
                  {canViewRoles && (
                    <Link
                      href={`/roles/${role.uuid}`}
                      prefetch
                      className="rounded-md border border-(--border-default) bg-(--bg-card) p-1.5 text-(--text-secondary) shadow-sm transition-colors hover:bg-(--bg-hover)"
                      title="View role"
                      aria-label="View role"
                    >
                      <Eye size={16} />
                    </Link>
                  )}

                  {canUpdateRoles && (
                    <Link
                      href={`/roles/${role.uuid}/edit`}
                      prefetch
                      className="rounded-md border border-(--border-default) bg-(--bg-card) p-1.5 text-(--text-secondary) shadow-sm transition-colors hover:bg-(--bg-hover)"
                      title="Edit role"
                      aria-label="Edit role"
                    >
                      <Pencil size={16} />
                    </Link>
                  )}

                  {canDeleteRoles && role.name !== 'SUPER_ADMIN' && (
                    <button
                      type="button"
                      onClick={() => onDelete(role.uuid, role.name)}
                      className="rounded-md border border-(--border-default) bg-(--bg-card) p-1.5 text-(--accent-error) shadow-sm transition-colors hover:bg-(--bg-hover)"
                      title="Delete role"
                      aria-label="Delete role"
                    >
                      <Trash2 size={16} />
                    </button>
                  )}
                </div>
              );
            },
          }),
        ]
      : []),
  ], [canDeleteRoles, canUpdateRoles, canViewRoles, onDelete]);

  return (
    <DataTable
      columns={columns}
      data={data}
      isPending={isPending}
      isError={isError}
      noDataMessage="No roles found"
      getRowId={(row: RoleListItem) => row.uuid}
    />
  );
}
