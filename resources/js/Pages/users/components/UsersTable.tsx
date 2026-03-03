import * as React from 'react';
import { 
  createColumnHelper, 
  type ColumnDef, 
  type RowSelectionState, 
  type OnChangeFn,
  type SortingState,
  type ColumnFiltersState,
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  flexRender,
} from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import UserStatusBadge from '@/modules/users/components/UserStatusBadge';
import type { UserListItem } from '@/types/users';
import { useUserMutations } from '@/modules/users/hooks/useUserMutations';
import { formatDateShort } from '@/common/helpers/formatDate';

import { Eye, Pencil, Trash2, CheckCircle, ArrowUpDown } from 'lucide-react';

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
  
  // TanStack Table v8: Local state for sorting and filtering
  const [sorting, setSorting] = React.useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>([]);

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
      header: ({ column }) => (
        <button
          onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
          className="flex items-center gap-2 hover:text-(--accent-primary) transition-colors"
        >
          User
          <ArrowUpDown size={14} />
        </button>
      ),
      enableSorting: true,
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
              <p className="text-sm font-semibold leading-tight text-gray-900 dark:text-gray-100">
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
      header: ({ column }) => (
        <button
          onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
          className="flex items-center gap-2 hover:text-(--accent-primary) transition-colors"
        >
          Email / Phone
          <ArrowUpDown size={14} />
        </button>
      ),
      enableSorting: true,
      enableColumnFilter: true,
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
      header: ({ column }) => (
        <button
          onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
          className="flex items-center gap-2 hover:text-(--accent-primary) transition-colors"
        >
          Created
          <ArrowUpDown size={14} />
        </button>
      ),
      enableSorting: true,
      sortingFn: 'datetime',
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
          <div className="flex items-center justify-end gap-1.5">
            <Link
               href={`/users/${user.uuid}`}
               className="btn-action btn-action-view"
               title="View Profile"
            >
               <Eye size={14} />
            </Link>
            
            {!isDeleted && (
              <Link
                 href={`/users/${user.uuid}/edit`}
                 className="btn-action btn-action-edit"
                 title="Edit User"
              >
                 <Pencil size={14} />
              </Link>
            )}

            {isDeleted ? (
              <button
                  onClick={() => restoreUser.mutate(user.uuid)}
                  className="btn-action btn-action-restore"
                  title="Restore User"
                  disabled={restoreUser.isPending}
              >
                  <CheckCircle size={14} />
              </button>
            ) : (
              <button
                 onClick={() => onDelete(user.uuid, user.full_name, user.email)}
                 className="btn-action btn-action-delete"
                 title="Delete User"
              >
                 <Trash2 size={14} />
              </button>
            )}
          </div>
        );
      },
    }),
  ], [onDelete, initials, restoreUser]);

  // TanStack Table v8: getRowId usa uuid estable (no índice del array)
  const table = useReactTable({
    data,
    columns,
    getRowId: (row) => row.uuid,
    state: {
      rowSelection,
      sorting,
      columnFilters,
    },
    onRowSelectionChange,
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    enableRowSelection: true,
    enableSorting: true,
    enableColumnFilters: true,
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-(--accent-primary)" />
      </div>
    );
  }

  if (isError) {
    return (
      <div className="flex items-center justify-center py-12 text-(--accent-error)">
        Error loading users
      </div>
    );
  }

  if (data.length === 0) {
    return (
      <div className="flex items-center justify-center py-12 text-(--text-muted)">
        No users found
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full">
        <thead>
          {table.getHeaderGroups().map(headerGroup => (
            <tr key={headerGroup.id} className="border-b border-(--border-subtle)">
              {headerGroup.headers.map(header => (
                <th 
                  key={header.id} 
                  className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-(--text-muted)"
                >
                  {header.isPlaceholder
                    ? null
                    : flexRender(header.column.columnDef.header, header.getContext())}
                </th>
              ))}
            </tr>
          ))}
        </thead>
        <tbody>
          {table.getRowModel().rows.map(row => (
            <tr 
              key={row.id}
              className="border-b border-(--border-subtle) hover:bg-(--bg-hover) transition-colors"
            >
              {row.getVisibleCells().map(cell => (
                <td key={cell.id} className="px-6 py-4">
                  {flexRender(cell.column.columnDef.cell, cell.getContext())}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
