import * as React from 'react';
import {
  createColumnHelper,
  type OnChangeFn,
  type RowSelectionState,
} from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { Eye, Pencil, Trash2, CheckCircle } from 'lucide-react';
import { DataTable } from '@/shadcn/data-table';
import { RestoreConfirmModal } from '@/shadcn/RestoreConfirmModal';
import { formatDateShort } from '@/common/helpers/formatDate';
import { usePostMutations } from '@/modules/posts/hooks/usePostMutations';
import type { PostListItem } from '@/types/posts';

interface PostsTableProps {
  data: PostListItem[];
  isPending: boolean;
  isError?: boolean;
  onDelete: (uuid: string, title: string) => void;
  rowSelection: RowSelectionState;
  onRowSelectionChange: OnChangeFn<RowSelectionState>;
}

const columnHelper = createColumnHelper<PostListItem>();

function statusClasses(status: PostListItem['post_status']): string {
  if (status === 'published') return 'bg-emerald-500/12 text-emerald-500 border-emerald-500/20';
  if (status === 'scheduled') return 'bg-amber-500/12 text-amber-500 border-amber-500/20';
  if (status === 'archived') return 'bg-slate-500/12 text-slate-400 border-slate-500/20';
  return 'bg-sky-500/12 text-sky-500 border-sky-500/20';
}

export default function PostsTable({
  data,
  isPending,
  isError = false,
  onDelete,
  rowSelection,
  onRowSelectionChange,
}: PostsTableProps): React.JSX.Element {
  const { restorePost } = usePostMutations();
  const [pendingRestore, setPendingRestore] = React.useState<{ uuid: string; title: string } | null>(null);

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
    columnHelper.accessor('post_title', {
      header: 'Post',
      cell: (info) => {
        const post = info.row.original;
        return (
          <div className="text-left">
            <p className="text-sm font-semibold text-(--text-primary) line-clamp-1">{post.post_title}</p>
            <p className="text-[11px] text-(--text-muted) mt-1 line-clamp-1">/{post.post_title_slug}</p>
          </div>
        );
      },
    }),
    columnHelper.accessor('category_name', {
      header: 'Category',
      cell: (info) => (
        <span className="text-sm text-(--text-secondary)">{info.getValue() || 'Uncategorized'}</span>
      ),
    }),
    columnHelper.accessor('post_status', {
      header: 'Status',
      cell: (info) => (
        <span className={`inline-flex rounded-full border px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide ${statusClasses(info.getValue())}`}>
          {info.getValue()}
        </span>
      ),
    }),
    columnHelper.accessor('published_at', {
      header: 'Published',
      cell: (info) => (
        <span className="text-sm text-(--text-muted)">{formatDateShort(info.getValue())}</span>
      ),
    }),
    columnHelper.accessor('created_at', {
      header: 'Created',
      cell: (info) => (
        <span className="text-sm text-(--text-muted)">{formatDateShort(info.getValue())}</span>
      ),
    }),
    columnHelper.display({
      id: 'actions',
      header: 'Actions',
      cell: (info) => {
        const post = info.row.original;
        const isDeleted = !!post.deleted_at;

        return (
          <div className="flex items-center justify-end gap-2 pr-4">
            <Link
              href={`/posts/${post.uuid}`}
              className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
              title="View Post"
            >
              <Eye size={16} />
            </Link>

            {!isDeleted && (
              <Link
                href={`/posts/${post.uuid}/edit`}
                className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
                title="Edit Post"
              >
                <Pencil size={16} />
              </Link>
            )}

            {isDeleted ? (
              <button
                onClick={() => setPendingRestore({ uuid: post.uuid, title: post.post_title })}
                className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--accent-success) shadow-sm transition-colors"
                title="Restore Post"
              >
                <CheckCircle size={16} />
              </button>
            ) : (
              <button
                onClick={() => onDelete(post.uuid, post.post_title)}
                className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--accent-error) shadow-sm transition-colors"
                title="Delete Post"
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
        columns={columns as never}
        data={data}
        isLoading={isPending}
        isError={isError}
        noDataMessage="No posts found"
        rowSelection={rowSelection}
        onRowSelectionChange={onRowSelectionChange}
        getRowId={(row: PostListItem) => row.uuid}
      />

      <RestoreConfirmModal
        open={pendingRestore !== null}
        entityLabel={pendingRestore?.title ?? ''}
        onConfirm={async () => {
          if (!pendingRestore) return;
          await restorePost.mutateAsync(pendingRestore.uuid);
          setPendingRestore(null);
        }}
        onCancel={() => setPendingRestore(null)}
        isRestoring={restorePost.isPending}
      />
    </>
  );
}
