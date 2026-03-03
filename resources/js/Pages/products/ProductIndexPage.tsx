import * as React from 'react';
import { Link, Head, useRemember, router } from '@inertiajs/react';
import { useQueryClient } from '@tanstack/react-query';
import { type RowSelectionState } from '@tanstack/react-table';
import AppLayout from '@/pages/layouts/AppLayout';
import { useProducts } from '@/modules/products/hooks/useProducts';
import { useProductMutations } from '@/modules/products/hooks/useProductMutations';
import ProductTable from './components/ProductTable';
import { DataTableBulkActions } from '@/shadcn/DataTableBulkActions';
import { DeleteConfirmModal } from '@/shadcn/DeleteConfirmModal';
import { DataTableDateRangeFilter } from '@/common/data-table/DataTableDateRangeFilter';
import { ExportButton } from '@/common/export/ExportButton';
import type { ProductFilters } from '@/types/api';
import { Plus, Search, ChevronLeft, ChevronRight, Package } from 'lucide-react';

function buildPageWindow(current: number, last: number): number[] {
  const start = Math.max(1, Math.min(current - 2, last - 4));
  const end   = Math.min(last, start + 4);
  return Array.from({ length: end - start + 1 }, (_, i) => start + i);
}

export default function ProductIndexPage(): React.JSX.Element {
  const [filters, setFilters] = useRemember<ProductFilters>({ page: 1, perPage: 15 }, 'product-filters');
  const [search, setSearch]   = React.useState<string>(filters.search ?? '');
  const [rowSelection, setRowSelection] = React.useState<RowSelectionState>({});
  const [pendingDelete, setPendingDelete] = React.useState<{ uuid: string; name: string } | null>(null);
  const [isDeletingBulk, setIsDeletingBulk] = React.useState<boolean>(false);

  const [isPendingExport, startExportTransition] = React.useTransition();
  const [, startSearchTransition] = React.useTransition();

  const queryClient = useQueryClient();

  // ── Data ──
  const { data, isPending, isError } = useProducts(filters);
  const { deleteProduct, restoreProduct } = useProductMutations();

  const productList = data?.data ?? [];
  const meta = data?.meta ?? { currentPage: 1, lastPage: 1, perPage: 15, total: 0 };
  const pageWindow = buildPageWindow(meta.currentPage, meta.lastPage);

  // ── Handlers ──
  function handleSearchChange(e: React.ChangeEvent<HTMLInputElement>): void {
    const value = e.target.value;
    setSearch(value);
    startSearchTransition(() => {
      setFilters(prev => ({ ...prev, search: value || undefined, page: 1 }));
    });
  }

  function handleDeleteClick(uuid: string, name: string): void {
    setPendingDelete({ uuid, name });
  }

  function handleConfirmSingleDelete(): void {
    if (!pendingDelete) return;
    deleteProduct.mutate(pendingDelete.uuid, {
      onSuccess: () => setPendingDelete(null),
    });
  }

  function handleExport(format: 'excel' | 'pdf'): void {
    startExportTransition(() => {
      const params = new URLSearchParams({ format });
      if (filters.search)   params.append('search', filters.search);
      if (filters.dateFrom) params.append('dateFrom', filters.dateFrom);
      if (filters.dateTo)   params.append('dateTo', filters.dateTo);
      window.open(`/products/data/admin/export?${params}`, '_blank');
    });
  }

  // ── Bulk ──
  const selectedUuids = Object.keys(rowSelection).filter(k => rowSelection[k]);

  function handleBulkDelete(): void {
    if (!selectedUuids.length) return;
    setIsDeletingBulk(true);
    router.post('/products/data/admin/bulk-delete', { uuids: selectedUuids }, {
      onSuccess: () => {
        setRowSelection({});
        queryClient.invalidateQueries({ queryKey: ['products'] });
      },
      onFinish: () => setIsDeletingBulk(false),
    });
  }

  function handleBulkRestore(): void {
    if (!selectedUuids.length) return;
    restoreProduct.mutate(selectedUuids as any, {
      onSuccess: () => setRowSelection({}),
    });
  }

  function goToPage(page: number): void {
    setFilters(prev => ({ ...prev, page }));
  }

  return (
    <>
      <Head title="Products" />
      <AppLayout>
        <div
          className="flex flex-col gap-6 animate-in fade-in slide-in-from-bottom-4 duration-500"
          style={{ fontFamily: 'var(--font-sans)' }}
        >

          {/* ── Header ── */}
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-4">
              <div
                className="flex h-12 w-12 items-center justify-center rounded-xl shadow-lg"
                style={{ background: 'color-mix(in srgb, var(--accent-primary) 15%, transparent)', border: '1px solid color-mix(in srgb, var(--accent-primary) 30%, transparent)' }}
              >
                <Package size={22} style={{ color: 'var(--accent-primary)' }} />
              </div>
              <div>
                <h1 className="text-2xl font-extrabold tracking-tight" style={{ color: 'var(--text-primary)' }}>
                  Products
                </h1>
                <p className="text-sm font-medium mt-0.5" style={{ color: 'var(--text-muted)' }}>
                  {meta.total} {meta.total === 1 ? 'product' : 'products'} in catalogue
                </p>
              </div>
            </div>

            <Link
              href="/products/create"
              className="btn-modern btn-modern-primary inline-flex items-center gap-2 px-5 py-2 font-bold shadow-sm"
            >
              <Plus size={16} />
              New Product
            </Link>
          </div>

          {/* ── Toolbar ── */}
          <div
            className="flex flex-col gap-3 rounded-xl px-4 py-3 sm:flex-row sm:items-center"
            style={{ background: 'var(--bg-card)', border: '1px solid var(--border-default)' }}
          >
            {/* Search */}
            <div className="flex flex-1 items-center gap-3">
              <Search size={14} style={{ color: 'var(--text-disabled)', flexShrink: 0 }} />
              <input
                type="text"
                value={search}
                onChange={handleSearchChange}
                placeholder="Search products by name, SKU..."
                className="flex-1 bg-transparent text-sm outline-none"
                style={{ color: 'var(--text-primary)', fontFamily: 'var(--font-sans)' }}
              />
            </div>

            <div className="flex items-center gap-3 flex-wrap">
              <div className="h-6 w-px hidden sm:block" style={{ background: 'var(--border-subtle)' }} />

              <select
                value={filters.status ?? ''}
                onChange={e => startSearchTransition(() =>
                  setFilters(p => ({ ...p, status: e.target.value || undefined, page: 1 }))
                )}
                className="rounded-lg px-3 py-1.5 text-sm outline-none transition-colors"
                style={{ background: 'var(--bg-card)', color: 'var(--text-primary)', border: '1px solid var(--border-default)' }}
              >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="deleted">Deleted</option>
              </select>

              <div className="h-6 w-px hidden sm:block" style={{ background: 'var(--border-subtle)' }} />

              <DataTableDateRangeFilter
                dateFrom={filters.dateFrom}
                dateTo={filters.dateTo}
                onChange={(range: { dateFrom?: string; dateTo?: string }) =>
                  setFilters(p => ({ ...p, ...range, page: 1 }))
                }
              />

              <div className="h-6 w-px hidden sm:block" style={{ background: 'var(--border-subtle)' }} />

              <ExportButton onExport={handleExport} isExporting={isPendingExport} />
            </div>
          </div>

          {/* ── Bulk Actions ── */}
          {selectedUuids.length > 0 && (
            <DataTableBulkActions
              count={selectedUuids.length}
              onDelete={handleBulkDelete}
              onRestore={handleBulkRestore}
              isDeleting={isDeletingBulk}
              isRestoring={restoreProduct.isPending}
            />
          )}

          {/* ── Table card ── */}
          <div className="card-modern shadow-xl overflow-hidden">
            <ProductTable
              data={productList}
              isLoading={isPending}
              isError={isError}
              onDelete={handleDeleteClick}
              rowSelection={rowSelection}
              onRowSelectionChange={setRowSelection}
            />

            {/* ── Pagination ── */}
            {meta.lastPage > 1 && (
              <div
                className="flex items-center justify-between px-4 py-3"
                style={{ borderTop: '1px solid var(--border-subtle)' }}
              >
                <p className="text-xs font-semibold" style={{ color: 'var(--text-secondary)' }}>
                  Page{' '}
                  <span style={{ color: 'var(--accent-primary)', fontWeight: 700 }}>{meta.currentPage}</span>
                  {' '}/ {meta.lastPage} •{' '}
                  <span style={{ color: 'var(--accent-primary)', fontWeight: 700 }}>{meta.total}</span>
                  {' '}Total
                </p>

                <div className="flex items-center gap-1">
                  <button
                    onClick={() => goToPage(meta.currentPage - 1)}
                    disabled={meta.currentPage <= 1}
                    className="flex h-8 w-8 items-center justify-center rounded-lg transition-all disabled:opacity-30"
                    style={{ color: 'var(--text-muted)', border: '1px solid var(--border-default)' }}
                    aria-label="Previous page"
                  >
                    <ChevronLeft size={14} />
                  </button>

                  {pageWindow.map(page => (
                    <button
                      key={page}
                      onClick={() => goToPage(page)}
                      className="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold transition-all"
                      style={page === meta.currentPage
                        ? { background: 'var(--accent-primary)', color: '#fff' }
                        : { color: 'var(--text-muted)', border: '1px solid var(--border-default)' }
                      }
                    >
                      {page}
                    </button>
                  ))}

                  <button
                    onClick={() => goToPage(meta.currentPage + 1)}
                    disabled={meta.currentPage >= meta.lastPage}
                    className="flex h-8 w-8 items-center justify-center rounded-lg transition-all disabled:opacity-30"
                    style={{ color: 'var(--text-muted)', border: '1px solid var(--border-default)' }}
                    aria-label="Next page"
                  >
                    <ChevronRight size={14} />
                  </button>
                </div>
              </div>
            )}
          </div>

        </div>

        <DeleteConfirmModal
          open={pendingDelete !== null}
          entityLabel={pendingDelete?.name ?? ''}
          onConfirm={handleConfirmSingleDelete}
          onCancel={() => setPendingDelete(null)}
          isDeleting={deleteProduct.isPending}
        />
      </AppLayout>
    </>
  );
}
