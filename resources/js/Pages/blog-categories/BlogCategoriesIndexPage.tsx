import * as React from 'react';
import { Link, Head, useRemember, router } from '@inertiajs/react';
import { useQueryClient } from '@tanstack/react-query';
import { type RowSelectionState } from '@tanstack/react-table';
import AppLayout from '@/pages/layouts/AppLayout';
import { useBlogCategories } from '@/modules/blog-categories/hooks/useBlogCategories';
import { useBlogCategoryMutations } from '@/modules/blog-categories/hooks/useBlogCategoryMutations';
import BlogCategoriesTable from './components/BlogCategoriesTable';
import { DataTableBulkActions } from '@/shadcn/DataTableBulkActions';
import { DeleteConfirmModal } from '@/shadcn/DeleteConfirmModal';
import { DataTableDateRangeFilter } from '@/common/data-table/DataTableDateRangeFilter';
import { ExportButton } from '@/common/export/ExportButton';
import type { BlogCategoryFilters, BlogCategoryListItem } from '@/types/blog-categories';
import { Search, ChevronLeft, ChevronRight, FolderPlus } from 'lucide-react';

/**
 * BlogCategoriesIndexPage — React 19 + TanStack Query v5 + TanStack Table v8
 * - useTransition: search/export non-blocking
 * - useOptimistic: instant visual delete feedback
 */
export default function BlogCategoriesIndexPage(): React.JSX.Element {
  const [filters, setFilters] = useRemember<BlogCategoryFilters>(
    { page: 1, per_page: 15 },
    'blog-categories-filters',
  );
  const [search, setSearch] = React.useState<string>(filters.search || '');
  const [rowSelection, setRowSelection] = React.useState<RowSelectionState>({});
  const [pendingDelete, setPendingDelete] = React.useState<{ uuid: string; name: string } | null>(null);
  const [isDeletingBulk, setIsDeletingBulk] = React.useState<boolean>(false);

  const [isPendingExport, startExportTransition] = React.useTransition();
  const [, startSearchTransition] = React.useTransition();

  const queryClient = useQueryClient();

  // ── Fetch blog categories via TanStack Query ──
  const { data, isPending, isError } = useBlogCategories(filters);
  const categories = data?.data ?? [];
  const meta = data?.meta ?? { currentPage: 1, lastPage: 1, perPage: 15, total: 0 };

  // React 19: useOptimistic for instant UI feedback
  const [optimisticCategories, setOptimisticCategories] = React.useOptimistic(
    categories,
    (state: BlogCategoryListItem[], deletedUuid: string) =>
      state.filter((c) => c.uuid !== deletedUuid),
  );

  const { deleteBlogCategory } = useBlogCategoryMutations();

  // ── Export ──
  function handleExport(format: 'excel' | 'pdf'): void {
    startExportTransition(() => {
      const params = new URLSearchParams();
      if (filters.search) params.append('search', filters.search);
      if (filters.date_from) params.append('date_from', filters.date_from);
      if (filters.date_to) params.append('date_to', filters.date_to);
      params.append('format', format);
      window.open(`/blog-categories/data/admin/export?${params.toString()}`, '_blank');
    });
  }

  // ── Search ──
  function handleSearchChange(e: React.ChangeEvent<HTMLInputElement>): void {
    const value = e.target.value;
    setSearch(value);
    startSearchTransition(() => {
      setFilters((prev) => ({ ...prev, search: value || undefined, page: 1 }));
    });
  }

  // ── Single Delete with Optimistic Update ──
  function handleDeleteClick(uuid: string, name: string): void {
    setPendingDelete({ uuid, name });
  }

  async function handleConfirmSingleDelete(): Promise<void> {
    if (!pendingDelete) return;

    React.startTransition(async () => {
      setOptimisticCategories(pendingDelete.uuid);
      try {
        await deleteBlogCategory.mutateAsync(pendingDelete.uuid);
        setPendingDelete(null);
      } catch {
        /* auto-reverts */
      }
    });
  }

  // ── Bulk Delete ──
  const selectedUuids = React.useMemo(
    () => Object.keys(rowSelection).filter((k) => rowSelection[k]),
    [rowSelection],
  );

  function handleBulkDelete(): void {
    if (selectedUuids.length === 0) return;
    setIsDeletingBulk(true);
    router.post(
      '/blog-categories/data/admin/bulk-delete',
      { uuids: selectedUuids },
      {
        onSuccess: () => {
          setRowSelection({});
          queryClient.invalidateQueries({ queryKey: ['blogCategories'] });
        },
        onFinish: () => setIsDeletingBulk(false),
      },
    );
  }

  // ── Pagination ──
  function goToPage(page: number): void {
    setFilters((prev) => ({ ...prev, page }));
  }

  const pageWindow = React.useMemo(() => {
    const total = meta.lastPage;
    const current = meta.currentPage;
    const half = 2;
    let start = Math.max(1, current - half);
    const end = Math.min(total, start + 4);
    start = Math.max(1, end - 4);
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
  }, [meta.currentPage, meta.lastPage]);

  return (
    <>
      <Head title="Blog Categories" />
      <AppLayout>
        <div className="flex flex-col gap-6 animate-in fade-in duration-300">
          {/* ── Header ── */}
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 className="text-3xl font-extrabold tracking-tight text-(--text-primary)">
                Blog Categories
              </h1>
              <p className="text-sm mt-1 text-(--text-muted) font-medium">
                Organize and manage blog content categories —{' '}
                <span className="text-(--accent-primary)">
                  {meta.total} {meta.total === 1 ? 'record' : 'records'} found
                </span>
              </p>
            </div>
            <Link
              href="/blog-categories/create"
              className="btn-modern btn-modern-primary px-5 py-2.5 font-bold shadow-lg hover:shadow-xl transition-all"
            >
              <FolderPlus size={16} />
              New Category
            </Link>
          </div>

          {/* ── Filters Bar ── */}
          <div className="flex flex-col items-center gap-3 rounded-2xl px-5 py-4 sm:flex-row glass-morphism border border-(--border-default) shadow-sm">
            <div className="flex flex-1 items-center gap-3 w-full group">
              <Search
                size={18}
                className="text-(--text-disabled) group-focus-within:text-(--accent-primary) transition-colors"
              />
              <input
                type="text"
                value={search}
                onChange={handleSearchChange}
                placeholder="Filter by name or description..."
                className="flex-1 bg-transparent text-sm outline-none placeholder:text-(--text-disabled) text-(--text-primary)"
              />
            </div>

            <div className="flex w-full items-center gap-4 sm:w-auto">
              <div className="h-8 w-px bg-(--border-subtle) hidden sm:block" />

              <select
                value={filters.status || ''}
                onChange={(e) =>
                  startSearchTransition(() =>
                    setFilters((p) => ({
                      ...p,
                      status: (e.target.value || undefined) as 'active' | 'deleted' | undefined,
                      page: 1,
                    })),
                  )
                }
                className="bg-transparent text-sm outline-none text-(--text-primary) border border-(--border-default) rounded-lg px-2 py-1 focus:border-(--accent-primary) transition-colors"
              >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="deleted">Deleted</option>
              </select>

              <div className="h-8 w-px bg-(--border-subtle) hidden sm:block" />

              <DataTableDateRangeFilter
                dateFrom={filters.date_from}
                dateTo={filters.date_to}
                onChange={(range) =>
                  setFilters((p) => ({
                    ...p,
                    date_from: range.dateFrom,
                    date_to: range.dateTo,
                    page: 1,
                  }))
                }
              />

              <div className="h-8 w-px bg-(--border-subtle) hidden sm:block" />

              <ExportButton onExport={handleExport} isExporting={isPendingExport} />
            </div>
          </div>

          {/* ── Bulk Actions ── */}
          {selectedUuids.length > 0 && (
            <DataTableBulkActions
              count={selectedUuids.length}
              onDelete={handleBulkDelete}
              isDeleting={isDeletingBulk}
            />
          )}

          {/* ── Table Card ── */}
          <div className="card-modern overflow-hidden border border-(--border-default) shadow-xl">
            <BlogCategoriesTable
              data={optimisticCategories}
              isPending={isPending}
              isError={isError}
              onDelete={handleDeleteClick}
              rowSelection={rowSelection}
              onRowSelectionChange={setRowSelection}
            />

            {/* ── Pagination ── */}
            {meta.lastPage > 1 && (
              <div className="flex items-center justify-between px-6 py-4 bg-black/5 dark:bg-white/5 border-t border-(--border-subtle)">
                <span className="text-xs font-semibold" style={{ color: 'var(--text-secondary)' }}>
                  Page{' '}
                  <span style={{ color: 'var(--accent-primary)', fontWeight: 700 }}>
                    {meta.currentPage}
                  </span>{' '}
                  / {meta.lastPage} •{' '}
                  <span style={{ color: 'var(--accent-primary)', fontWeight: 700 }}>
                    {meta.total}
                  </span>{' '}
                  Total
                </span>
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => goToPage(meta.currentPage - 1)}
                    disabled={meta.currentPage <= 1}
                    className="flex h-9 w-9 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) disabled:opacity-30 disabled:pointer-events-none transition-all"
                  >
                    <ChevronLeft size={18} />
                  </button>
                  <div className="flex items-center gap-1 mx-2">
                    {pageWindow.map((p) => (
                      <button
                        key={p}
                        onClick={() => goToPage(p)}
                        className={`h-9 w-9 rounded-xl text-xs font-bold transition-all ${
                          meta.currentPage === p
                            ? 'bg-(--accent-primary) text-white shadow-lg'
                            : 'hover:bg-(--bg-hover) text-(--text-muted)'
                        }`}
                      >
                        {p}
                      </button>
                    ))}
                  </div>
                  <button
                    onClick={() => goToPage(meta.currentPage + 1)}
                    disabled={meta.currentPage >= meta.lastPage}
                    className="flex h-9 w-9 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) disabled:opacity-30 disabled:pointer-events-none transition-all"
                  >
                    <ChevronRight size={18} />
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>

        <DeleteConfirmModal
          open={pendingDelete !== null}
          entityLabel={pendingDelete ? pendingDelete.name : ''}
          onConfirm={handleConfirmSingleDelete}
          onCancel={() => setPendingDelete(null)}
          isDeleting={deleteBlogCategory.isPending}
        />
      </AppLayout>
    </>
  );
}
