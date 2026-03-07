import * as React from 'react';
import { Head, Link, useRemember } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Search, ShieldCheck } from 'lucide-react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { useAuthorization } from '@/modules/auth/hooks/useAuthorization';
import { useRoles } from '@/modules/roles/hooks/useRoles';
import { useRoleMutations } from '@/modules/roles/hooks/useRoleMutations';
import { DeleteConfirmModal } from '@/common/data-table/DeleteConfirmModal';
import { ExportButton } from '@/common/export/ExportButton';
import type { RoleFilters, RoleListItem } from '@/types/roles';
import RolesTable from './components/RolesTable';

export default function RolesIndexPage(): React.JSX.Element {
  const { hasPermission } = useAuthorization();
  const [filters, setFilters] = useRemember<RoleFilters>({ page: 1, per_page: 15, guard_name: 'web' }, 'roles-filters');
  const [search, setSearch] = React.useState<string>(filters.search ?? '');
  const [pendingDelete, setPendingDelete] = React.useState<{ uuid: string; name: string } | null>(null);
  const canCreateRoles = hasPermission('CREATE_ROLES');
  const canDeleteRoles = hasPermission('DELETE_ROLES');
  const [isPendingExport, startExportTransition] = React.useTransition();
  const [, startSearchTransition] = React.useTransition();

  const { data, isPending, isError } = useRoles(filters);
  const roles = data?.data ?? [];
  const meta = data?.meta ?? { currentPage: 1, lastPage: 1, perPage: 15, total: 0 };
  const [optimisticRoles, removeOptimisticRole] = React.useOptimistic(
    roles,
    (currentState: RoleListItem[], deletedUuid: string) => currentState.filter((role) => role.uuid !== deletedUuid),
  );

  const { deleteRole } = useRoleMutations();

  function handleSearchChange(event: React.ChangeEvent<HTMLInputElement>): void {
    const value = event.target.value;
    setSearch(value);
    startSearchTransition(() => {
      setFilters((previous) => ({ ...previous, search: value || undefined, page: 1 }));
    });
  }

  function handleDeleteClick(uuid: string, name: string): void {
    setPendingDelete({ uuid, name });
  }

  async function handleConfirmDelete(): Promise<void> {
    if (!pendingDelete) {
      return;
    }

    React.startTransition(async () => {
      removeOptimisticRole(pendingDelete.uuid);

      try {
        await deleteRole.mutateAsync(pendingDelete.uuid);
        setPendingDelete(null);
      } catch {
      }
    });
  }

  async function handleExport(format: 'excel' | 'pdf'): Promise<void> {
    startExportTransition(() => {
      const params = new URLSearchParams();
      if (filters.search) params.append('search', filters.search);
      if (filters.guard_name) params.append('guard_name', filters.guard_name);
      params.append('format', format);
      window.open(`/roles/data/admin/export?${params.toString()}`, '_blank');
    });
  }

  function goToPage(page: number): void {
    setFilters((previous) => ({ ...previous, page }));
  }

  const pageWindow = React.useMemo(() => {
    const total = meta.lastPage;
    const current = meta.currentPage;
    const half = 2;
    let start = Math.max(1, current - half);
    const end = Math.min(total, start + 4);
    start = Math.max(1, end - 4);

    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
  }, [meta.currentPage, meta.lastPage]);

  return (
    <>
      <Head title="Roles" />
      <AppLayout>
        <PermissionGuard permissions={['VIEW_ROLES']}>
          <div className="flex flex-col gap-6 animate-in fade-in duration-300">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h1 className="text-3xl font-extrabold tracking-tight text-(--text-primary)">Roles & Access</h1>
                <p className="mt-1 text-sm font-medium text-(--text-muted)">
                  Manage authorization groups — <span className="text-(--accent-primary)">{meta.total} records found</span>
                </p>
              </div>
              {canCreateRoles && (
                <Link
                  href="/roles/create"
                  prefetch
                  className="btn-modern btn-modern-primary px-5 py-2.5 font-bold shadow-lg transition-all hover:shadow-xl"
                >
                  <ShieldCheck size={16} />
                  New Role
                </Link>
              )}
            </div>

            <div className="card flex flex-col items-center gap-3 px-5 py-4 shadow-sm sm:flex-row">
              <div className="group flex w-full flex-1 items-center gap-3">
                <Search size={18} className="text-(--text-disabled) transition-colors group-focus-within:text-(--accent-primary)" />
                <input
                  type="text"
                  value={search}
                  onChange={handleSearchChange}
                  placeholder="Filter by role name..."
                  className="flex-1 bg-transparent text-sm text-(--text-primary) outline-none placeholder:text-(--text-disabled)"
                />
              </div>

              <div className="flex w-full items-center gap-4 sm:w-auto">
                <div className="hidden h-8 w-px bg-(--border-subtle) sm:block" />

                <select
                  value={filters.guard_name ?? 'web'}
                  onChange={(event) => {
                    startSearchTransition(() => {
                      setFilters((previous) => ({
                        ...previous,
                        guard_name: event.target.value,
                        page: 1,
                      }));
                    });
                  }}
                  className="rounded-lg border border-(--border-default) bg-transparent px-2 py-1 text-sm text-(--text-primary) outline-none transition-colors focus:border-(--accent-primary)"
                >
                  <option value="web">Web</option>
                  <option value="sanctum">Sanctum</option>
                </select>

                <div className="hidden h-8 w-px bg-(--border-subtle) sm:block" />

                <ExportButton onExport={handleExport} isExporting={isPendingExport} />
              </div>
            </div>

            <div className="card overflow-hidden shadow-xl">
              <RolesTable
                data={optimisticRoles}
                isPending={isPending}
                isError={isError}
                onDelete={handleDeleteClick}
              />

              {meta.lastPage > 1 && (
                <div
                  className="flex items-center justify-between border-t border-(--border-subtle) px-6 py-4"
                  style={{ background: 'color-mix(in srgb, var(--bg-hover) 28%, transparent)' }}
                >
                  <span className="text-xs font-semibold" style={{ color: 'var(--text-secondary)' }}>
                    Page <span style={{ color: 'var(--accent-primary)', fontWeight: 700 }}>{meta.currentPage}</span> / {meta.lastPage}
                  </span>
                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={() => goToPage(meta.currentPage - 1)}
                      disabled={meta.currentPage <= 1}
                      className="flex h-9 w-9 items-center justify-center rounded-xl border border-(--border-default) bg-(--bg-card) text-(--text-muted) transition-all hover:bg-(--bg-hover) disabled:pointer-events-none disabled:opacity-30"
                    >
                      <ChevronLeft size={18} />
                    </button>
                    <div className="mx-2 flex items-center gap-1">
                      {pageWindow.map((page) => (
                        <button
                          key={page}
                          type="button"
                          onClick={() => goToPage(page)}
                          className={`h-9 w-9 rounded-xl text-xs font-bold transition-all ${meta.currentPage === page ? 'shadow-lg' : 'text-(--text-muted) hover:bg-(--bg-hover)'}`}
                          style={meta.currentPage === page ? { background: 'var(--accent-primary)', color: 'var(--text-primary)' } : undefined}
                        >
                          {page}
                        </button>
                      ))}
                    </div>
                    <button
                      type="button"
                      onClick={() => goToPage(meta.currentPage + 1)}
                      disabled={meta.currentPage >= meta.lastPage}
                      className="flex h-9 w-9 items-center justify-center rounded-xl border border-(--border-default) bg-(--bg-card) text-(--text-muted) transition-all hover:bg-(--bg-hover) disabled:pointer-events-none disabled:opacity-30"
                    >
                      <ChevronRight size={18} />
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </PermissionGuard>

        {canDeleteRoles && (
          <DeleteConfirmModal
            open={pendingDelete !== null}
            entityLabel={pendingDelete?.name ?? ''}
            onConfirm={handleConfirmDelete}
            onCancel={() => setPendingDelete(null)}
            isDeleting={deleteRole.isPending}
          />
        )}
      </AppLayout>
    </>
  );
}
