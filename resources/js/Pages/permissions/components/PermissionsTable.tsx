import * as React from 'react';
import { createColumnHelper } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { DataTable } from '@/common/data-table/DataTable';
import { formatDateShort } from '@/common/helpers/formatDate';
import { useAuthorization } from '@/modules/auth/hooks/useAuthorization';
import type { PermissionListItem } from '@/types/permissions';

interface PermissionsTableProps {
  data: PermissionListItem[];
  isPending: boolean;
  isError?: boolean;
  onDelete: (uuid: string, name: string) => void;
}

const columnHelper = createColumnHelper<PermissionListItem>();

export default function PermissionsTable({
  data,
  isPending,
  isError = false,
  onDelete,
}: PermissionsTableProps): React.JSX.Element {
  const { hasPermission } = useAuthorization();
  const canViewPermissions = hasPermission('VIEW_PERMISSIONS');
  const canUpdatePermissions = hasPermission('UPDATE_PERMISSIONS');
  const canDeletePermissions = hasPermission('DELETE_PERMISSIONS');

  const columns = React.useMemo(() => [
    columnHelper.accessor('name', {
      header: 'Permission',
      cell: (info) => {
        const permission = info.row.original;

        return (
          <div className="flex flex-col gap-1 text-left">
            <span className="text-sm font-semibold text-(--text-primary)">{permission.name}</span>
            <span className="text-[11px] uppercase tracking-wide text-(--text-muted)">{permission.guard_name}</span>
          </div>
        );
      },
    }),
    columnHelper.accessor('roles', {
      header: 'Roles',
      cell: (info) => {
        const roles = info.getValue();

        if (roles.length === 0) {
          return <span className="text-sm text-(--text-muted)">—</span>;
        }

        return (
          <div className="flex flex-wrap gap-2">
            {roles.map((role) => (
              <span
                key={role}
                className="rounded-full border border-(--border-default) bg-(--bg-card) px-2.5 py-1 text-[11px] font-medium text-(--text-secondary)"
              >
                {role}
              </span>
            ))}
          </div>
        );
      },
    }),
    columnHelper.accessor('roles_count', {
      header: 'Roles Count',
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
    ...((canViewPermissions || canUpdatePermissions || canDeletePermissions)
      ? [
          columnHelper.display({
            id: 'actions',
            header: 'Actions',
            cell: (info) => {
              const permission = info.row.original;

              return (
                <div className="flex items-center justify-end gap-2 pr-4">
                  {canViewPermissions && (
                    <Link
                      href={`/permissions/${permission.uuid}`}
                      prefetch
                      className="rounded-md border border-(--border-default) bg-(--bg-card) p-1.5 text-(--text-secondary) shadow-sm transition-colors hover:bg-(--bg-hover)"
                      title="View permission"
                      aria-label="View permission"
                    >
                      <Eye size={16} />
                    </Link>
                  )}

                  {canUpdatePermissions && (
                    <Link
                      href={`/permissions/${permission.uuid}/edit`}
                      prefetch
                      className="rounded-md border border-(--border-default) bg-(--bg-card) p-1.5 text-(--text-secondary) shadow-sm transition-colors hover:bg-(--bg-hover)"
                      title="Edit permission"
                      aria-label="Edit permission"
                    >
                      <Pencil size={16} />
                    </Link>
                  )}

                  {canDeletePermissions && (
                    <button
                      type="button"
                      onClick={() => onDelete(permission.uuid, permission.name)}
                      className="rounded-md border border-(--border-default) bg-(--bg-card) p-1.5 text-(--accent-error) shadow-sm transition-colors hover:bg-(--bg-hover)"
                      title="Delete permission"
                      aria-label="Delete permission"
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
  ], [canDeletePermissions, canUpdatePermissions, canViewPermissions, onDelete]);

  return (
    <DataTable
      columns={columns}
      data={data}
      isPending={isPending}
      isError={isError}
      noDataMessage="No permissions found"
      getRowId={(row: PermissionListItem) => row.uuid}
    />
  );
}
