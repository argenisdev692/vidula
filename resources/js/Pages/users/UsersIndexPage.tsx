import * as React from 'react';
import { Link, Head, useRemember, router } from '@inertiajs/react';
import { useQueryClient } from '@tanstack/react-query';
import { type RowSelectionState } from '@tanstack/react-table';
import AppLayout from '@/pages/layouts/AppLayout';
import { useUsers } from '@/modules/users/hooks/useUsers';
import { useUserMutations } from '@/modules/users/hooks/useUserMutations';
import UsersTable from './components/UsersTable';
import { DataTableBulkActions } from '@/shadcn/DataTableBulkActions';
import { DeleteConfirmModal } from '@/shadcn/DeleteConfirmModal';
import { DataTableDateRangeFilter } from '@/common/data-table/DataTableDateRangeFilter';
import { ExportButton } from '@/common/export/ExportButton';
import type { UserFilters } from '@/types/users';
import { Search, ChevronLeft, ChevronRight, UserPlus } from 'lucide-react';

/**
 * UsersIndexPage — Super-admin management for users.
 */
export default function UsersIndexPage(): React.JSX.Element {
  const [filters, setFilters] = useRemember<UserFilters>({ page: 1, per_page: 15 }, 'users-filters');
  const [search, setSearch] = React.useState<string>(filters.search || '');
  const [rowSelection, setRowSelection] = React.useState<RowSelectionState>({});
  const [pendingDelete, setPendingDelete] = React.useState<{ uuid: string; name: string; email: string } | null>(null);
  const [isDeletingBulk, setIsDeletingBulk] = React.useState<boolean>(false);
  
  const [isPendingExport, startExportTransition] = React.useTransition();
  const [, startSearchTransition] = React.useTransition();

  const queryClient = useQueryClient();

  // ── Fetch users via TanStack Query ──
  const { data, isPending, isError } = useUsers(filters);
  const users = data?.data ?? [];
  const meta = data?.meta ?? { currentPage: 1, lastPage: 1, perPage: 15, total: 0 };

  const { deleteUser } = useUserMutations();

  // ── Export function ──
  async function handleExport(format: 'excel' | 'pdf'): Promise<void> {
    startExportTransition(() => {
      const params = new URLSearchParams();
      if (filters.search) params.append('search', filters.search);
      if (filters.date_from) params.append('date_from', filters.date_from);
      if (filters.date_to) params.append('date_to', filters.date_to);
      params.append('format', format);

      window.open(`/users/data/admin/export?${params.toString()}`, '_blank');
    });
  }

  // ── Search Change ──
  function handleSearchChange(e: React.ChangeEvent<HTMLInputElement>): void {
    const value = e.target.value;
    setSearch(value);
    
    startSearchTransition(() => {
      setFilters((prev) => ({ ...prev, search: value || undefined, page: 1 }));
    });
  }

  // ── Single Actions ──
  function handleDeleteClick(uuid: string, name: string, email: string): void {
    setPendingDelete({ uuid, name, email });
  }

  async function handleConfirmSingleDelete(): Promise<void> {
    if (!pendingDelete) return;
    try {
      await deleteUser.mutateAsync(pendingDelete.uuid);
      setPendingDelete(null);
    } catch (err) {
      console.error('Failed to delete user', err);
    }
  }

  function handleBulkDelete(): void {
    const uuids = Object.keys(rowSelection).filter(k => rowSelection[k]);
    if (uuids.length === 0) return;
    
    setIsDeletingBulk(true);
    router.post('/users/data/admin/bulk-delete', { uuids }, {
      onSuccess: () => {
        setRowSelection({});
        queryClient.invalidateQueries({ queryKey: ['users'] });
      },
      onFinish: () => setIsDeletingBulk(false),
    });
  }

  // ── Pagination ──
  function goToPage(page: number): void {
    setFilters((prev) => ({ ...prev, page }));
  }

  const initials = React.useCallback((name: string, lastName: string): string => {
    if (!name && !lastName) return 'U';
    const f = (name || '').trim().charAt(0).toUpperCase();
    const l = (lastName || '').trim().charAt(0).toUpperCase();
    return f && l ? f + l : f || l || 'U';
  }, []);

  const selectedUuids = React.useMemo(() => 
    Object.keys(rowSelection).filter((k) => rowSelection[k]),
    [rowSelection]
  );

  return (
    <>
      <Head title="System Users" />
      <AppLayout>
      <div className="flex flex-col gap-6 animate-in fade-in duration-500">
        {/* ── Header ── */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-3xl font-extrabold tracking-tight text-(--text-primary)">
              System Users
            </h1>
            <p className="text-sm mt-1 text-(--text-muted) font-medium">
              Oversee and manage platform accounts — <span className="text-(--accent-primary)">{meta.total} users</span> recorded
            </p>
          </div>
          <Link
            href="/users/create"
            className="btn-modern-primary flex items-center gap-2 px-4 py-2 hover:scale-[1.02] active:scale-[0.98] transition-all"
          >
            <UserPlus size={18} />
            <span className="font-semibold">New User</span>
          </Link>
        </div>

        {/* ── Filters Bar ── */}
        <div className="flex flex-col items-center gap-3 rounded-2xl px-5 py-4 sm:flex-row glass-morphism border border-(--border-default) shadow-sm">
          <div className="flex flex-1 items-center gap-3 w-full group">
            <Search size={18} className="text-(--text-disabled) group-focus-within:text-(--accent-primary) transition-colors" />
            <input
              type="text"
              value={search}
              onChange={handleSearchChange}
              placeholder="Filter by name, email or identity..."
              className="flex-1 bg-transparent text-sm outline-none placeholder:text-(--text-disabled) text-(--text-primary)"
            />
          </div>

          <div className="flex w-full items-center gap-4 sm:w-auto">
            <div className="h-8 w-px bg-(--border-subtle) hidden sm:block" />
            
            <select
              value={filters.status || ''}
              onChange={(e) => startSearchTransition(() => setFilters(p => ({ ...p, status: (e.target.value || undefined) as any, page: 1 })))}
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
              onChange={(range) => setFilters(p => ({ 
                ...p, 
                date_from: range.dateFrom, 
                date_to: range.dateTo, 
                page: 1 
              }))}
            />

            <div className="h-8 w-px bg-(--border-subtle) hidden sm:block" />

            <ExportButton 
              onExport={handleExport} 
              isExporting={isPendingExport} 
            />
          </div>
        </div>

        {/* ── Bulk Actions Bar ── */}
        {selectedUuids.length > 0 && (
            <DataTableBulkActions
                count={selectedUuids.length}
                onDelete={handleBulkDelete}
                isDeleting={isDeletingBulk}
            />
        )}

        {/* ── Table Card ── */}
        <div className="card-modern overflow-hidden border border-(--border-default) shadow-xl">
          <UsersTable
            data={users}
            isLoading={isPending}
            isError={isError}
            onDelete={handleDeleteClick}
            initials={initials}
            rowSelection={rowSelection}
            onRowSelectionChange={setRowSelection}
          />

          {/* ── Pagination ── */}
          {meta.lastPage > 1 && (
            <div className="flex items-center justify-between px-6 py-4 bg-black/5 dark:bg-white/5 border-t border-(--border-subtle)">
              <span className="text-xs font-semibold text-(--text-disabled) uppercase tracking-wider">
                Page {meta.currentPage} / {meta.lastPage} • {meta.total} Total
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
                    {Array.from({ length: Math.min(5, meta.lastPage) }, (_, i) => {
                        const p = i + 1;
                        return (
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
                        );
                    })}
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
        entityLabel={pendingDelete ? `${pendingDelete.name} (${pendingDelete.email})` : ''}
        onConfirm={handleConfirmSingleDelete}
        onCancel={() => setPendingDelete(null)}
        isDeleting={deleteUser.isPending}
      />
      </AppLayout>
    </>
  );
}
