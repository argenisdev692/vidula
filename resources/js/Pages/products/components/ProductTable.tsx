import * as React from 'react';
import { createColumnHelper, type ColumnDef, type RowSelectionState, type OnChangeFn } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { DataTable } from '@/shadcn/data-table';
import type { ProductListItem } from '@/types/api';
import { formatDateShort } from '@/common/helpers/formatDate';
import { Package, Eye, Pencil, Trash2, CheckCircle } from 'lucide-react';

interface ProductTableProps {
  data: ProductListItem[];
  isLoading: boolean;
  isError: boolean;
  onDelete: (uuid: string, name: string) => void;
  onRestore?: (uuid: string, name: string) => void;
  rowSelection: RowSelectionState;
  onRowSelectionChange: OnChangeFn<RowSelectionState>;
}

// ✅ columnHelper OUTSIDE component (module-level constant)
const columnHelper = createColumnHelper<ProductListItem>();

const IconEye = () => <Eye size={16} />;
const IconPencil = () => <Pencil size={16} />;
const IconTrash = () => <Trash2 size={16} />;
const IconRestore = () => <CheckCircle size={16} />;

export default function ProductTable({
  data,
  isLoading,
  isError,
  onDelete,
  onRestore,
  rowSelection,
  onRowSelectionChange,
}: ProductTableProps) {
  const columns = React.useMemo<ColumnDef<ProductListItem, any>[]>(() => [
    columnHelper.display({
      id: 'select',
      header: ({ table }) => (
        <input
          type="checkbox"
          checked={table.getIsAllPageRowsSelected()}
          onChange={table.getToggleAllPageRowsSelectedHandler()}
          aria-label="Select all"
          className="h-4 w-4 rounded border-gray-300 cursor-pointer"
          style={{ accentColor: 'var(--accent-primary)' }}
        />
      ),
      cell: ({ row }) => (
        <input
          type="checkbox"
          checked={row.getIsSelected()}
          onChange={row.getToggleSelectedHandler()}
          aria-label="Select row"
          className="h-4 w-4 rounded border-gray-300 cursor-pointer"
          style={{ accentColor: 'var(--accent-primary)' }}
        />
      ),
    }),
    columnHelper.accessor('title', {
      header: 'Product',
      cell: (info) => {
        const item = info.row.original;
        return (
          <div className="flex items-center justify-start gap-3 text-left">
            <div
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
              style={{
                background: 'color-mix(in srgb, var(--accent-primary) 15%, transparent)',
                color: 'var(--accent-primary)',
              }}
            >
              <Package size={16} />
            </div>
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
                {item.title}
              </p>
              <p className="truncate text-[11px] uppercase tracking-wider" style={{ color: 'var(--text-secondary)' }}>
                {item.type}
              </p>
            </div>
          </div>
        );
      },
    }),
    columnHelper.accessor('price', {
      header: 'Price',
      cell: (info) => {
        const item = info.row.original;
        return <span className="text-sm font-medium" style={{ color: 'var(--text-primary)' }}>{item.price} {item.currency}</span>;
      },
    }),
    columnHelper.accessor('status', {
      header: 'Status',
      cell: (info) => {
        const status = info.getValue() ?? '—';
        return <span className="badge badge-primary">{status}</span>;
      },
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
            <Link 
              href={`/products/${item.id}`} 
              className="p-1.5 rounded-md border shadow-sm transition-colors hover:bg-(--bg-hover)" 
              style={{ 
                borderColor: 'var(--border-default)', 
                background: 'var(--bg-card)', 
                color: 'var(--text-secondary)' 
              }}
              title="View"
            >
              <IconEye />
            </Link>
            {!isDeleted ? (
              <>
                <Link 
                  href={`/products/${item.id}/edit`} 
                  className="p-1.5 rounded-md border shadow-sm transition-colors hover:bg-(--bg-hover)" 
                  style={{ 
                    borderColor: 'var(--border-default)', 
                    background: 'var(--bg-card)', 
                    color: 'var(--text-secondary)' 
                  }}
                  title="Edit"
                >
                  <IconPencil />
                </Link>
                <button
                  onClick={() => onDelete(item.id, item.title)}
                  className="p-1.5 rounded-md border shadow-sm transition-colors hover:bg-(--bg-hover)"
                  style={{ 
                    borderColor: 'var(--border-default)', 
                    background: 'var(--bg-card)', 
                    color: 'var(--accent-error)' 
                  }}
                  title="Delete"
                >
                  <IconTrash />
                </button>
              </>
            ) : (
              <button
                onClick={() => onRestore?.(item.id, item.title)}
                className="p-1.5 rounded-md border shadow-sm transition-colors hover:bg-(--bg-hover)"
                style={{
                  borderColor: 'var(--border-default)',
                  background: 'var(--bg-card)',
                  color: 'var(--accent-success)'
                }}
                title="Restore"
              >
                <IconRestore />
              </button>
            )}
          </div>
        );
      },
    }),
  ], [onDelete, onRestore]); // ✅ columnHelper NOT in deps

  return (
    <DataTable
      columns={columns}
      data={data}
      isLoading={isLoading}
      isError={isError}
      noDataMessage="No products found"
      rowSelection={rowSelection}
      onRowSelectionChange={onRowSelectionChange}
      getRowId={(row) => row.id} // ✅ required for stable IDs
    />
  );
}
