import * as React from 'react';
import {
  createColumnHelper,
  type RowSelectionState,
  type OnChangeFn,
} from '@tanstack/react-table';
import { DataTable } from '@/shadcn/data-table';
import { Link } from '@inertiajs/react';
import { RestoreConfirmModal } from '@/shadcn/RestoreConfirmModal';
import { useBlogCategoryMutations } from '@/modules/blog-categories/hooks/useBlogCategoryMutations';
import { formatDateShort } from '@/common/helpers/formatDate';
import type { BlogCategoryListItem } from '@/types/blog-categories';
import { Eye, Pencil, Trash2, CheckCircle } from 'lucide-react';

interface BlogCategoriesTableProps {
  data: BlogCategoryListItem[];
  isPending: boolean;
  isError?: boolean;
  onDelete: (uuid: string, name: string) => void;
  rowSelection: RowSelectionState;
  onRowSelectionChange: OnChangeFn<RowSelectionState>;
}

// ── columnHelper outside component (§7 rule 1) ──
const columnHelper = createColumnHelper<BlogCategoryListItem>();

export default function BlogCategoriesTable({
  data,
  isPending,
  isError = false,
  onDelete,
  rowSelection,
  onRowSelectionChange,
}: BlogCategoriesTableProps): React.JSX.Element {
  const { restoreBlogCategory } = useBlogCategoryMutations();
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
    columnHelper.accessor('blog_category_name', {
      header: 'Category',
      cell: (info) => {
        const cat = info.row.original;
        return (
          <div className="flex items-center gap-3 text-left">
            <div
              className="flex h-9 w-9 items-center justify-center rounded-lg text-[11px] font-bold shadow-sm"
              style={{
                background: 'var(--grad-primary)',
                color: 'var(--text-primary)',
              }}
            >
              {(cat.blog_category_name?.[0] ?? 'C').toUpperCase()}
            </div>
            <div>
              <p className="text-sm font-semibold capitalize leading-tight text-(--text-primary)">
                {cat.blog_category_name}
              </p>
              {cat.blog_category_description && (
                <p className="text-[11px] mt-0.5 text-(--text-muted) line-clamp-1">
                  {cat.blog_category_description}
                </p>
              )}
            </div>
          </div>
        );
      },
    }),
    columnHelper.accessor('blog_category_description', {
      header: 'Description',
      cell: (info) => (
        <span className="text-sm text-(--text-secondary) line-clamp-2">
          {info.getValue() || '—'}
        </span>
      ),
    }),
    columnHelper.accessor('created_at', {
      header: 'Created',
      cell: (info) => (
        <span className="text-sm text-(--text-muted)">
          {formatDateShort(info.getValue())}
        </span>
      ),
    }),
    columnHelper.display({
      id: 'actions',
      header: 'Actions',
      cell: (info) => {
        const cat = info.row.original;
        const isDeleted = !!cat.deleted_at;

        return (
          <div className="flex items-center justify-end gap-2 pr-4">
            <Link
              href={`/blog-categories/${cat.uuid}`}
              className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
              title="View Category"
            >
              <Eye size={16} />
            </Link>

            {!isDeleted && (
              <Link
                href={`/blog-categories/${cat.uuid}/edit`}
                className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
                title="Edit Category"
              >
                <Pencil size={16} />
              </Link>
            )}

            {isDeleted ? (
              <button
                onClick={() => setPendingRestore({ uuid: cat.uuid, name: cat.blog_category_name })}
                className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--accent-success) shadow-sm transition-colors"
                title="Restore Category"
              >
                <CheckCircle size={16} />
              </button>
            ) : (
              <button
                onClick={() => onDelete(cat.uuid, cat.blog_category_name)}
                className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--accent-error) shadow-sm transition-colors"
                title="Delete Category"
              >
                <Trash2 size={16} />
              </button>
            )}
          </div>
        );
      },
    }),
  ], [onDelete]);

  return (
    <>
      <DataTable
        /* eslint-disable-next-line @typescript-eslint/no-explicit-any */
        columns={columns as any}
        data={data}
        isLoading={isPending}
        isError={isError}
        noDataMessage="No blog categories found"
        rowSelection={rowSelection}
        onRowSelectionChange={onRowSelectionChange}
        getRowId={(row: BlogCategoryListItem) => row.uuid}
      />

      <RestoreConfirmModal
        open={pendingRestore !== null}
        entityLabel={pendingRestore?.name ?? ''}
        onConfirm={async () => {
          if (!pendingRestore) return;
          await restoreBlogCategory.mutateAsync(pendingRestore.uuid);
          setPendingRestore(null);
        }}
        onCancel={() => setPendingRestore(null)}
        isRestoring={restoreBlogCategory.isPending}
      />
    </>
  );
}
