import * as React from 'react';
import { createColumnHelper, type ColumnDef, type RowSelectionState, type OnChangeFn } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { DataTable } from '@/shadcn/data-table';
import type { ClientListItem } from '@/types/api';
import { formatDateShort } from '@/common/helpers/formatDate';
import { UserSquare2, Eye, Pencil, Trash2, CheckCircle } from 'lucide-react';

interface ClientTableProps {
  data: ClientListItem[];
  isLoading: boolean;
  isError: boolean;
  onDelete: (uuid: string, name: string) => void;
  onRestoreClick?: (uuid: string, name: string) => void;
  rowSelection: RowSelectionState;
  onRowSelectionChange: OnChangeFn<RowSelectionState>;
}

export default function ClientTable({
  data,
  isLoading,
  isError,
  onDelete,
  onRestoreClick,
  rowSelection,
  onRowSelectionChange,
}: ClientTableProps) {
  const columnHelper = createColumnHelper<ClientListItem>();

  const columns = React.useMemo<ColumnDef<ClientListItem, any>[]>(() => [
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
    columnHelper.accessor('name', {
      header: 'Client',
      cell: (info) => {
        const item = info.row.original;
        return (
          <div className="flex items-center justify-start gap-3 text-left">
            <div
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
              style={{
                background: 'color-mix(in srgb, var(--accent-info) 15%, transparent)',
                color: 'var(--accent-info)',
              }}
            >
              <UserSquare2 size={16} />
            </div>
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                {item.name}
              </p>
              {item.company && (
                <p className="truncate text-[11px]" style={{ color: 'var(--text-secondary)' }}>
                  {item.company}
                </p>
              )}
            </div>
          </div>
        );
      },
    }),
    columnHelper.accessor('email', {
      header: 'Email',
      cell: (info) => <span className="text-sm font-medium" style={{ color: 'var(--text-primary)' }}>{info.getValue() ?? '—'}</span>,
    }),
    columnHelper.accessor('phone', {
      header: 'Phone',
      cell: (info) => <span className="text-sm text-(--text-secondary)">{info.getValue() ?? '—'}</span>,
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
        const item = info.row.original;
        const isDeleted = !!item.deleted_at;
        return (
          <div className="flex items-center justify-end gap-2 pr-4">
            <Link href={`/clients/${item.id}`} className="p-1.5 rounded-md hover:bg-(--bg-hover) text-(--text-secondary) transition-colors" title="View">
              <Eye size={16} />
            </Link>
            {!isDeleted ? (
              <>
                <Link href={`/clients/${item.id}/edit`} className="p-1.5 rounded-md hover:bg-(--bg-hover) text-(--text-secondary) transition-colors" title="Edit">
                  <Pencil size={16} />
                </Link>
                <button
                  onClick={() => onDelete(item.id, item.name)}
                  className="p-1.5 rounded-md hover:bg-(--bg-hover) text-(--accent-error) transition-colors"
                  title="Delete"
                >
                  <Trash2 size={16} />
                </button>
              </>
            ) : (
              // Deleted row: Provide restore button
              <button
                onClick={() => onRestoreClick?.(item.id, item.name)}
                className="p-1.5 rounded-md hover:bg-(--bg-hover) text-(--accent-success) transition-colors"
                title="Restore"
              >
                <CheckCircle size={16} />
              </button>
            )}
          </div>
        );
      },
    }),
  ], [columnHelper, onDelete, onRestoreClick]);

  return (
    <DataTable
      columns={columns}
      data={data}
      isLoading={isLoading}
      isError={isError}
      noDataMessage="No clients found"
      rowSelection={rowSelection}
      onRowSelectionChange={onRowSelectionChange}
    />
  );
}
