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

// ── columnHelper outside component (§7) ──
const columnHelper = createColumnHelper<ClientListItem>();

export default function ClientTable({
  data,
  isLoading,
  isError,
  onDelete,
  onRestoreClick,
  rowSelection,
  onRowSelectionChange,
}: ClientTableProps): React.JSX.Element {
  const columns = React.useMemo<ColumnDef<ClientListItem, unknown>[]>(() => [
    columnHelper.display({
      id: 'select',
      header: ({ table }) => (
        <input
          type="checkbox"
          checked={table.getIsAllPageRowsSelected()}
          onChange={table.getToggleAllPageRowsSelectedHandler()}
          aria-label="Select all"
          className="h-4 w-4 rounded cursor-pointer"
          style={{ accentColor: 'var(--accent-primary)' }}
        />
      ),
      cell: ({ row }) => (
        <input
          type="checkbox"
          checked={row.getIsSelected()}
          onChange={row.getToggleSelectedHandler()}
          aria-label="Select row"
          className="h-4 w-4 rounded cursor-pointer"
          style={{ accentColor: 'var(--accent-primary)' }}
        />
      ),
    }),
    columnHelper.accessor('clientName', {
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
              <p className="truncate text-sm font-semibold uppercase" style={{ color: 'var(--text-primary)' }}>
                {item.clientName}
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
      cell: (info) => <span className="text-sm" style={{ color: 'var(--text-secondary)' }}>{info.getValue() ?? '—'}</span>,
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
            <Link
              href={`/clients/${item.uuid}`}
              className="btn-action btn-action-view"
              title="View"
            >
              <Eye size={14} />
            </Link>
            {!isDeleted ? (
              <>
                <Link
                  href={`/clients/${item.uuid}/edit`}
                  className="btn-action btn-action-edit"
                  title="Edit"
                >
                  <Pencil size={14} />
                </Link>
                <button
                  onClick={() => onDelete(item.uuid, item.clientName)}
                  className="btn-action btn-action-delete"
                  title="Delete"
                >
                  <Trash2 size={14} />
                </button>
              </>
            ) : (
              <button
                onClick={() => onRestoreClick?.(item.uuid, item.clientName)}
                className="btn-action btn-action-restore"
                title="Restore"
              >
                <CheckCircle size={14} />
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
