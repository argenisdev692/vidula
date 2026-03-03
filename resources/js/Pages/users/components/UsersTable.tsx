import * as React from 'react';
import { 
  createColumnHelper, 
  type ColumnDef, 
  type RowSelectionState, 
  type OnChangeFn,
} from '@tanstack/react-table';
import { DataTable } from '@/shadcn/data-table';
import { Link } from '@inertiajs/react';
import UserStatusBadge from '@/modules/users/components/UserStatusBadge';
import type { UserListItem } from '@/types/users';
import { useUserMutations } from '@/modules/users/hooks/useUserMutations';
import { formatDateShort } from '@/common/helpers/formatDate';

import { Eye, Pencil, Trash2, CheckCircle } from 'lucide-react';

interface UsersTableProps {
  data: UserListItem[];
  isLoading: boolean;
  isError?: boolean;
  onDelete: (uuid: string, name: string, email: string) => void;
  initials: (name: string, lastName: string) => string;
  rowSelection: RowSelectionState;
  onRowSelectionChange: OnChangeFn<RowSelectionState>;
}

// ── columnHelper outside component (performance) ──
const columnHelper = createColumnHelper<UserListItem>();

export default function UsersTable({
  data,
  isLoading,
  isError = false,
  onDelete,
  initials,
  rowSelection,
  onRowSelectionChange,
}: UsersTableProps) {
  const { restoreUser } = useUserMutations();
  
  const columns = React.useMemo<ColumnDef<UserListItem, any>[]>(() => [
    columnHelper.display({
      id: 'select',
      header: ({ table }) => (
        <input
          type="checkbox"
          checked={table.getIsAllPageRowsSelected()}
          onChange={table.getToggleAllPageRowsSelectedHandler()}
          aria-label="Select all"
          className="h-4 w-4 rounded border-gray-300 accent-(--accent-primary) cursor-pointer"
        />
      ),
      cell: ({ row }) => (
        <input
          type="checkbox"
          checked={row.getIsSelected()}
          onChange={row.getToggleSelectedHandler()}
          aria-label="Select row"
          className="h-4 w-4 rounded border-gray-300 accent-(--accent-primary) cursor-pointer"
        />
      ),
    }),
    columnHelper.accessor('full_name', {
      header: 'User',
      cell: (info) => {
        const user = info.row.original;
        return (
          <div className="flex items-center gap-3 text-left">
            {user.profile_photo_path ? (
              <img
                src={user.profile_photo_path}
                alt={user.full_name}
                className="h-9 w-9 rounded-lg object-cover border border-(--border-default)"
              />
            ) : (
              <div
                className="flex h-9 w-9 items-center justify-center rounded-lg text-[11px] font-bold shadow-sm"
                style={{
                  background: 'var(--grad-primary)',
                  color: '#ffffff',
                }}
              >
                {initials(user.name, user.last_name)}
              </div>
            )}
            <div>
              <p className="text-sm font-semibold uppercase leading-tight text-gray-900 dark:text-gray-100">
                {user.full_name}
              </p>
              {user.username && (
                <p className="text-[11px] mt-0.5" style={{ color: 'var(--text-disabled)' }}>
                  @{user.username}
                </p>
              )}
            </div>
          </div>
        );
      },
    }),
    columnHelper.accessor('email', {
      header: 'Email / Phone',
      cell: (info) => {
          const user = info.row.original;
          return (
              <div className="flex flex-col">
                  <span className="text-sm" style={{ color: 'var(--text-secondary)' }}>{user.email || '—'}</span>
                  <span className="text-[11px]" style={{ color: 'var(--text-muted)' }}>{user.phone || ''}</span>
              </div>
          );
      }
    }),
    columnHelper.accessor('status', {
      header: 'Status',
      cell: (info) => <UserStatusBadge status={info.getValue()} />,
    }),
    columnHelper.accessor('created_at', {
      header: 'Created',
      cell: (info) => {
        const val = info.getValue() as string | undefined;
        return (
          <span className="text-sm" style={{ color: 'var(--text-muted)' }}>
            {formatDateShort(val)}
          </span>
        );
      },
    }),
    columnHelper.display({
      id: 'actions',
      header: 'Actions',
      cell: (info) => {
        const user = info.row.original;
        const isDeleted = !!user.deleted_at;
        
        return (
          <div className="flex items-center justify-end gap-2 pr-4">
            <Link
               href={`/users/${user.uuid}`}
               className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
               title="View Profile"
            >
               <Eye size={16} />
            </Link>
            
            {!isDeleted && (
              <Link
                 href={`/users/${user.uuid}/edit`}
                 className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
                 title="Edit User"
              >
                 <Pencil size={16} />
              </Link>
            )}

            {isDeleted ? (
              <button
                  onClick={() => restoreUser.mutate(user.uuid)}
                  className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-green-500/10 text-(--accent-success) shadow-sm transition-colors"
                  title="Restore User"
                  disabled={restoreUser.isPending}
              >
                  <CheckCircle size={16} />
              </button>
            ) : (
              <button
                 onClick={() => onDelete(user.uuid, user.full_name, user.email)}
                 className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-red-500/10 text-(--accent-error) shadow-sm transition-colors"
                 title="Delete User"
              >
                 <Trash2 size={16} />
              </button>
            )}
          </div>
        );
      },
    }),
  ], [onDelete, initials, restoreUser]);

  return (
    <DataTable
      columns={columns}
      data={data}
      isLoading={isLoading}
      isError={isError}
      noDataMessage="No users found"
      rowSelection={rowSelection}
      onRowSelectionChange={onRowSelectionChange}
      getRowId={(row: UserListItem) => row.uuid}
    />
  );
}
