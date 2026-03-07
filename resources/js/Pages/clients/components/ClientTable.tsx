import * as React from 'react';
import { createColumnHelper, type RowSelectionState, type OnChangeFn } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { DataTable } from '@/shadcn/data-table';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import type { ClientListItem } from '@/modules/clients/types';
import { formatDateShort } from '@/common/helpers/formatDate';
import { UserSquare2, Eye, Pencil, Trash2, CheckCircle } from 'lucide-react';

interface ClientTableProps {
  data: ClientListItem[];
  isPending: boolean;
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
  isPending,
  isError,
  onDelete,
  onRestoreClick,
  rowSelection,
  onRowSelectionChange,
}: ClientTableProps): React.JSX.Element {
  const columns = React.useMemo(() => [
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
    columnHelper.accessor('client_name', {
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
                {item.client_name}
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
            <PermissionGuard permissions={['VIEW_CLIENTS']}>
              <Link
                href={`/clients/${item.uuid}`}
                className="btn-action btn-action-view"
                title="View"
                aria-label={`View ${item.client_name}`}
              >
                <Eye size={14} />
              </Link>
            </PermissionGuard>
            {!isDeleted ? (
              <>
                <PermissionGuard permissions={['UPDATE_CLIENTS']}>
                  <Link
                    href={`/clients/${item.uuid}/edit`}
                    className="btn-action btn-action-edit"
                    title="Edit"
                    aria-label={`Edit ${item.client_name}`}
                  >
                    <Pencil size={14} />
                  </Link>
                </PermissionGuard>
                <PermissionGuard permissions={['DELETE_CLIENTS']}>
                  <button
                    onClick={() => onDelete(item.uuid, item.client_name)}
                    className="btn-action btn-action-delete"
                    title="Delete"
                    aria-label={`Delete ${item.client_name}`}
                  >
                    <Trash2 size={14} />
                  </button>
                </PermissionGuard>
              </>
            ) : (
              <PermissionGuard permissions={['UPDATE_CLIENTS']}>
                <button
                  onClick={() => onRestoreClick?.(item.uuid, item.client_name)}
                  className="btn-action btn-action-restore"
                  title="Restore"
                  aria-label={`Restore ${item.client_name}`}
                >
                  <CheckCircle size={14} />
                </button>
              </PermissionGuard>
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
      isPending={isPending}
      isError={isError}
      noDataMessage="No clients found"
      rowSelection={rowSelection}
      onRowSelectionChange={onRowSelectionChange}
      getRowId={(row) => row.uuid}
    />
  );
}
