import * as React from 'react';
import { Link, Head, useRemember, router } from '@inertiajs/react';
import { useQueryClient } from '@tanstack/react-query';
import { type RowSelectionState } from '@tanstack/react-table';
import AppLayout from '@/pages/layouts/AppLayout';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { useUsers } from '@/modules/users/hooks/useUsers';
import { useUserMutations } from '@/modules/users/hooks/useUserMutations';
import UsersTable from './components/UsersTable';
import { DataTableBulkActions } from '@/common/data-table/DataTableBulkActions';
import { DeleteConfirmModal } from '@/common/data-table/DeleteConfirmModal';
import { DataTableDateRangeFilter } from '@/common/data-table/DataTableDateRangeFilter';
import { ExportButton } from '@/common/export/ExportButton';
import type { UserFilters, UserListItem, UserStatus } from '@/types/users';
import { Search, ChevronLeft, ChevronRight, UserPlus } from 'lucide-react';

/**
 * UsersIndexPage — React 19 + TanStack Query v5 + TanStack Table v8
 * - useTransition: search/export no-bloqueantes
 * - useOptimistic: feedback instantáneo en eliminaciones (dentro de startTransition ✅)
 */
export default function UsersIndexPage(): React.JSX.Element {
  const [filters, setFilters] = useRemember<UserFilters>({ page: 1, per_page: 15 }, 'users-filters');
  const [search, setSearch] = React.useState<string>(filters.search || '');
  const [rowSelection, setRowSelection] = React.useState<RowSelectionState>({});
  const [pendingDelete, setPendingDelete] = React.useState<{ uuid: string; name: string; email: string } | null>(null);
  const [isDeletingBulk, setIsDeletingBulk] = React.useState<boolean>(false);

  // React 19: useTransition for non-blocking updates
  const [isPendingExport, startExportTransition] = React.useTransition();
  const [, startSearchTransition] = React.useTransition();

  const queryClient = useQueryClient();

  // ── Fetch users via TanStack Query ──
  const { data, isPending, isError } = useUsers(filters);
  const users = data?.data ?? [];
  const meta = data?.meta ?? { currentPage: 1, lastPage: 1, perPage: 15, total: 0 };

  // React 19: useOptimistic for instant UI feedback
  const [optimisticUsers, setOptimisticUsers] = React.useOptimistic(
    users,
    (state: UserListItem[], deletedUuid: string) => state.filter(u => u.uuid !== deletedUuid)
  );

  const { deleteUser } = useUserMutations();

  // ── Export ──
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

  // ── Search ──
  function handleSearchChange(e: React.ChangeEvent<HTMLInputElement>): void {
    const value = e.target.value;
    setSearch(value);
    startSearchTransition(() => {
      setFilters((prev) => ({ ...prev, search: value || undefined, page: 1 }));
    });
  }

  // ── Single Delete with Optimistic Update ──
  function handleDeleteClick(uuid: string, name: string, email: string): void {
    setPendingDelete({ uuid, name, email });
  }

  async function handleConfirmSingleDelete(): Promise<void> {
    if (!pendingDelete) return;

    // React 19: useOptimistic dentro de startTransition async
    React.startTransition(async () => {
      setOptimisticUsers(pendingDelete.uuid);
      try {
        await deleteUser.mutateAsync(pendingDelete.uuid);
        setPendingDelete(null);
      } catch {
      }
    });
  }

  // ── Bulk Delete ──
  const selectedUuids = React.useMemo(() =>
    Object.keys(rowSelection).filter((k) => rowSelection[k]),
    [rowSelection]
  );

  function handleBulkDelete(): void {
    if (selectedUuids.length === 0) return;
    setIsDeletingBulk(true);
    router.post('/users/data/admin/bulk-delete', { uuids: selectedUuids }, {
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

  const pageWindow = React.useMemo(() => {
    const total = meta.lastPage;
    const current = meta.currentPage;
    const half = 2;
    let start = Math.max(1, current - half);
    const end = Math.min(total, start + 4);
    start = Math.max(1, end - 4);
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
  }, [meta.currentPage, meta.lastPage]);

  const initials = React.useCallback((name: string, lastName: string): string => {
    if (!name && !lastName) return 'U';
    const f = (name || '').trim().charAt(0).toUpperCase();
    const l = (lastName || '').trim().charAt(0).toUpperCase();
    return f && l ? f + l : f || l || 'U';
  }, []);

  return (
    <>
      <Head title="System Users" />
      <AppLayout>
      <PermissionGuard permissions={['VIEW_USERS']}>
      <div className="flex flex-col gap-6 animate-in fade-in duration-300">
        {/* ── Header ── */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-3xl font-extrabold tracking-tight text-(--text-primary)">
              System Users
            </h1>
            <p className="text-sm mt-1 text-(--text-muted) font-medium">
              Oversee and manage platform accounts — <span className="text-(--accent-primary)">{meta.total} {meta.total === 1 ? 'record' : 'records'} found</span>
            </p>
          </div>
          <PermissionGuard permissions={['CREATE_USERS']}>
            <Link
              href="/users/create"
              prefetch
              className="btn-modern btn-modern-primary px-5 py-2.5 font-bold shadow-lg hover:shadow-xl transition-all"
            >
              <UserPlus size={16} />
              New User
            </Link>
          </PermissionGuard>
        </div>

        {/* ── Filters Bar ── */}
        <div className="card flex flex-col items-center gap-3 px-5 py-4 sm:flex-row shadow-sm">
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
              onChange={(e) => startSearchTransition(() => setFilters(p => ({ ...p, status: (e.target.value || undefined) as UserStatus | undefined, page: 1 })))}
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

        {/* ── Bulk Actions ── */}
        {selectedUuids.length > 0 && (
          <DataTableBulkActions
            count={selectedUuids.length}
            onDelete={handleBulkDelete}
            isDeleting={isDeletingBulk}
          />
        )}

        {/* ── Table Card ── */}
        <div className="card overflow-hidden shadow-xl">
          <UsersTable
            data={optimisticUsers}
            isPending={isPending}
            isError={isError}
            onDelete={handleDeleteClick}
            initials={initials}
            rowSelection={rowSelection}
            onRowSelectionChange={setRowSelection}
          />

          {/* ── Pagination ── */}
          {meta.lastPage > 1 && (
            <div
              className="flex items-center justify-between px-6 py-4 border-t border-(--border-subtle)"
              style={{ background: 'color-mix(in srgb, var(--bg-hover) 28%, transparent)' }}
            >
              <span className="text-xs font-semibold" style={{ color: 'var(--text-secondary)' }}>
                Page <span style={{ color: 'var(--accent-primary)', fontWeight: 700 }}>{meta.currentPage}</span> / {meta.lastPage} • <span style={{ color: 'var(--accent-primary)', fontWeight: 700 }}>{meta.total}</span> Total
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
                  {pageWindow.map(p => (
                    <button
                      key={p}
                      onClick={() => goToPage(p)}
                      className={`h-9 w-9 rounded-xl text-xs font-bold transition-all ${meta.currentPage === p ? 'shadow-lg' : 'hover:bg-(--bg-hover) text-(--text-muted)'}`}
                      style={meta.currentPage === p ? { background: 'var(--accent-primary)', color: 'var(--text-primary)' } : undefined}
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
      </PermissionGuard>
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
