import * as React from 'react';
import {
  createColumnHelper,
  type RowSelectionState,
  type OnChangeFn,
} from '@tanstack/react-table';
import { DataTable } from '@/shadcn/data-table';
import { Link } from '@inertiajs/react';
import { RestoreConfirmModal } from '@/shadcn/RestoreConfirmModal';
import UserStatusBadge from '@/modules/users/components/UserStatusBadge';
import type { UserListItem } from '@/types/users';
import { useUserMutations } from '@/modules/users/hooks/useUserMutations';
import { formatDateShort } from '@/common/helpers/formatDate';
import { Eye, Pencil, Trash2, CheckCircle } from 'lucide-react';

interface UsersTableProps {
  data: UserListItem[];
  isPending: boolean;
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
  isPending,
  isError = false,
  onDelete,
  initials,
  rowSelection,
  onRowSelectionChange,
}: UsersTableProps): React.JSX.Element {
  const { restoreUser } = useUserMutations();
  const [pendingRestore, setPendingRestore] = React.useState<{ uuid: string; name: string } | null>(null);

  const columns = React.useMemo(() => [
    columnHelper.display({
      id: 'select',
      header: ({ table }) => (
        <input
          type="checkbox"
          checked={table.getIsAllPageRowsSelected()}
          onChange={table.getToggleAllPageRowsSelectedHandler()}
          aria-label="Select all"
          className="h-4 w-4 rounded border border-(--border-default) accent-(--accent-primary) cursor-pointer"
        />
      ),
      cell: ({ row }) => (
        <input
          type="checkbox"
          checked={row.getIsSelected()}
          onChange={row.getToggleSelectedHandler()}
          aria-label="Select row"
          className="h-4 w-4 rounded border border-(--border-default) accent-(--accent-primary) cursor-pointer"
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
                  color: 'var(--text-primary)',
                }}
              >
                {initials(user.name, user.last_name)}
              </div>
            )}
            <div>
              <p className="text-sm font-semibold capitalize leading-tight text-(--text-primary)">
                {user.full_name}
              </p>
              {user.username && (
                <p className="text-[11px] mt-0.5 text-(--text-muted)">
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
                  <span className="text-sm text-(--text-secondary)">{user.email || '—'}</span>
                  <span className="text-[11px] text-(--text-muted)">{user.phone || ''}</span>
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
          <span className="text-sm text-(--text-muted)">
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
                  onClick={() => setPendingRestore({ uuid: user.uuid, name: user.full_name })}
                  className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--accent-success) shadow-sm transition-colors"
                  title="Restore User"
              >
                  <CheckCircle size={16} />
              </button>
            ) : (
              <button
                 onClick={() => onDelete(user.uuid, user.full_name, user.email)}
                 className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--accent-error) shadow-sm transition-colors"
                 title="Delete User"
              >
                 <Trash2 size={16} />
              </button>
            )}
          </div>
        );
      },
    }),
  ], [onDelete, initials]);

  return (
    <>
      <DataTable
        /* eslint-disable-next-line @typescript-eslint/no-explicit-any */
        columns={columns as any}
        data={data}
        isLoading={isPending}
        isError={isError}
        noDataMessage="No users found"
        rowSelection={rowSelection}
        onRowSelectionChange={onRowSelectionChange}
        getRowId={(row: UserListItem) => row.uuid}
      />

      <RestoreConfirmModal
        open={pendingRestore !== null}
        entityLabel={pendingRestore?.name ?? ''}
        onConfirm={async () => {
          if (!pendingRestore) return;
          await restoreUser.mutateAsync(pendingRestore.uuid);
          setPendingRestore(null);
        }}
        onCancel={() => setPendingRestore(null)}
        isRestoring={restoreUser.isPending}
      />
    </>
  );
}
