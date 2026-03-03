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

// ── columnHelper outside component (performance) ──
const columnHelper = createColumnHelper<ClientListItem>();

export default function ClientTable({
  data,
  isLoading,
  isError,
  onDelete,
  onRestoreClick,
  rowSelection,
  onRowSelectionChange,
}: ClientTableProps) {
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
    columnHelper.accessor('companyName', {
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
              <p className="truncate text-sm font-semibold uppercase text-gray-900 dark:text-gray-100">
                {item.companyName}
              </p>
            </div>
          </div>
        );
      },
    }),
    columnHelper.accessor('nif', {
      header: 'NIF/CIF',
      cell: (info) => <span className="text-sm font-medium uppercase" style={{ color: 'var(--text-secondary)' }}>{info.getValue() ?? '—'}</span>,
    }),
    columnHelper.accessor('email', {
      header: 'Email',
      cell: (info) => <span className="text-sm font-medium" style={{ color: 'var(--text-primary)' }}>{info.getValue() ?? '—'}</span>,
    }),
    columnHelper.accessor('phone', {
      header: 'Phone',
      cell: (info) => <span className="text-sm text-(--text-secondary)">{info.getValue() ?? '—'}</span>,
    }),
    columnHelper.accessor('createdAt', {
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
        const isDeleted = !!item.deletedAt;
        return (
          <div className="flex items-center justify-end gap-2 pr-4">
            <Link href={`/clients/${item.uuid}`} className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors" title="View">
              <Eye size={16} />
            </Link>
            {!isDeleted ? (
              <>
                <Link href={`/clients/${item.uuid}/edit`} className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors" title="Edit">
                  <Pencil size={16} />
                </Link>
                <button
                  onClick={() => onDelete(item.uuid, item.companyName)}
                  className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-red-500/10 text-(--accent-error) shadow-sm transition-colors"
                  title="Delete"
                >
                  <Trash2 size={16} />
                </button>
              </>
            ) : (
              // Deleted row: Provide restore button
              <button
                onClick={() => onRestoreClick?.(item.uuid, item.companyName)}
                className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-green-500/10 text-(--accent-success) shadow-sm transition-colors"
                title="Restore"
              >
                <CheckCircle size={16} />
              </button>
            )}
          </div>
        );
      },
    }),
  ], [onDelete, onRestoreClick]);

  return (
    <DataTable
      columns={columns}
      data={data}
      isLoading={isLoading}
      isError={isError}
      noDataMessage="No clients found"
      rowSelection={rowSelection}
      onRowSelectionChange={onRowSelectionChange}
      getRowId={(row) => row.uuid}
    />
  );
}
