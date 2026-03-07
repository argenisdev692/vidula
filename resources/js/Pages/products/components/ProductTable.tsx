import * as React from 'react';
import { createColumnHelper, type RowSelectionState, type OnChangeFn } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { DataTable } from '@/shadcn/data-table';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import ProductStatusBadge from '@/modules/products/components/ProductStatusBadge';
import type { ProductListItem } from '@/modules/products/types';
import { formatDateShort } from '@/common/helpers/formatDate';
import { Package, Eye, Pencil, Trash2, CheckCircle } from 'lucide-react';

interface ProductTableProps {
  data: ProductListItem[];
  isPending: boolean;
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
  isPending,
  isError,
  onDelete,
  onRestore,
  rowSelection,
  onRowSelectionChange,
}: ProductTableProps) {
  const columns = React.useMemo(() => [
    columnHelper.display({
      id: 'select',
      header: ({ table }) => (
        <input
          type="checkbox"
          checked={table.getIsAllPageRowsSelected()}
          onChange={table.getToggleAllPageRowsSelectedHandler()}
          aria-label="Select all"
          className="h-4 w-4 cursor-pointer rounded"
          style={{ accentColor: 'var(--accent-primary)', border: '1px solid var(--border-default)' }}
        />
      ),
      cell: ({ row }) => (
        <input
          type="checkbox"
          checked={row.getIsSelected()}
          onChange={row.getToggleSelectedHandler()}
          aria-label="Select row"
          className="h-4 w-4 cursor-pointer rounded"
          style={{ accentColor: 'var(--accent-primary)', border: '1px solid var(--border-default)' }}
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
        const item = info.row.original;
        return <ProductStatusBadge status={item.deleted_at ? 'deleted' : 'active'} />;
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
              href={`/products/${item.uuid}`} 
              className="btn-action btn-action-view" 
              style={{ fontFamily: 'var(--font-sans)' }}
              aria-label={`View ${item.title}`}
              title="View"
            >
              <IconEye />
            </Link>
            {!isDeleted ? (
              <>
                <PermissionGuard permissions={['UPDATE_PRODUCTS']}>
                  <Link 
                    href={`/products/${item.uuid}/edit`} 
                    className="btn-action btn-action-edit" 
                    style={{ fontFamily: 'var(--font-sans)' }}
                    aria-label={`Edit ${item.title}`}
                    title="Edit"
                  >
                    <IconPencil />
                  </Link>
                </PermissionGuard>
                <PermissionGuard permissions={['DELETE_PRODUCTS']}>
                  <button
                    onClick={() => onDelete(item.uuid, item.title)}
                    className="btn-action btn-action-delete"
                    style={{ fontFamily: 'var(--font-sans)' }}
                    aria-label={`Delete ${item.title}`}
                    title="Delete"
                  >
                    <IconTrash />
                  </button>
                </PermissionGuard>
              </>
            ) : (
              <PermissionGuard permissions={['RESTORE_PRODUCTS']}>
                <button
                  onClick={() => onRestore?.(item.uuid, item.title)}
                  className="btn-action btn-action-restore"
                  style={{ fontFamily: 'var(--font-sans)' }}
                  aria-label={`Restore ${item.title}`}
                  title="Restore"
                >
                  <IconRestore />
                </button>
              </PermissionGuard>
            )}
          </div>
        );
      },
    }),
  ], [onDelete, onRestore]);

  return (
    <DataTable
      columns={columns}
      data={data}
      isLoading={isPending}
      isError={isError}
      noDataMessage="No products found"
      rowSelection={rowSelection}
      onRowSelectionChange={onRowSelectionChange}
      getRowId={(row) => row.uuid} 
    />
  );
}
