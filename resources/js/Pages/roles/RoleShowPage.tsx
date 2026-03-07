import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { DeleteConfirmModal } from '@/common/data-table/DeleteConfirmModal';
import { formatDateShort } from '@/common/helpers/formatDate';
import { useRoleMutations } from '@/modules/roles/hooks/useRoleMutations';
import type { RolePageProps } from '@/types/roles';

function InfoRow({ label, value }: { label: string; value: string | number | null | undefined }): React.JSX.Element {
  const displayValue = value ?? '—';

  return (
    <div className="grid grid-cols-3 gap-4 border-b border-(--border-subtle) py-3">
      <dt className="text-sm font-medium text-(--text-muted)">{label}</dt>
      <dd className="col-span-2 text-sm font-medium text-(--text-primary)">{displayValue}</dd>
    </div>
  );
}

export default function RoleShowPage({ role }: RolePageProps): React.JSX.Element {
  const { deleteRole } = useRoleMutations();
  const [pendingDelete, setPendingDelete] = React.useState<boolean>(false);

  async function handleConfirmDelete(): Promise<void> {
    React.startTransition(async () => {
      try {
        await deleteRole.mutateAsync(role.uuid);
        router.visit('/roles');
      } catch {
      }
    });
  }

  return (
    <>
      <Head title={`Role — ${role.name}`} />
      <AppLayout>
        <PermissionGuard permissions={['VIEW_ROLES']}>
          <div className="mx-auto max-w-5xl" style={{ fontFamily: 'var(--font-sans)' }}>
            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div className="flex items-center gap-3">
                <Link
                  href="/roles"
                  prefetch
                  className="flex h-9 w-9 items-center justify-center rounded-lg border border-(--border-default) bg-(--bg-card) text-(--text-muted) transition-all hover:bg-(--bg-hover)"
                >
                  <ArrowLeft size={16} />
                </Link>
                <div>
                  <h1 className="text-xl font-bold text-(--text-primary)">Role Details</h1>
                  <p className="text-sm text-(--text-muted)">#{role.uuid}</p>
                </div>
              </div>

              <div className="flex gap-3">
                <PermissionGuard permissions={['UPDATE_ROLES']}>
                  <Link
                    href={`/roles/${role.uuid}/edit`}
                    prefetch
                    className="btn-modern btn-modern-primary inline-flex items-center gap-2 px-4 py-2"
                  >
                    <Pencil size={16} /> Edit
                  </Link>
                </PermissionGuard>
                <PermissionGuard permissions={['DELETE_ROLES']}>
                  {role.name !== 'SUPER_ADMIN' && (
                    <button
                      type="button"
                      onClick={() => setPendingDelete(true)}
                      className="btn-modern btn-modern-danger inline-flex items-center gap-2 px-4 py-2"
                    >
                      <Trash2 size={16} /> Delete
                    </button>
                  )}
                </PermissionGuard>
              </div>
            </div>

            <div className="card-modern p-6 shadow-lg">
              <h3 className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-(--text-secondary)">General</h3>
              <dl>
                <InfoRow label="Name" value={role.name} />
                <InfoRow label="Guard" value={role.guard_name} />
                <InfoRow label="Assigned Users" value={role.users_count} />
                <InfoRow label="Created" value={formatDateShort(role.created_at)} />
                <InfoRow label="Updated" value={formatDateShort(role.updated_at)} />
              </dl>

              <h3 className="mb-3 mt-6 text-[11px] font-semibold uppercase tracking-wider text-(--text-secondary)">Permissions</h3>
              <div className="flex flex-wrap gap-2">
                {role.permissions.length > 0 ? (
                  role.permissions.map((permission) => (
                    <span
                      key={permission}
                      className="rounded-full border border-(--border-default) bg-(--bg-card) px-3 py-1 text-xs font-medium text-(--text-secondary)"
                    >
                      {permission}
                    </span>
                  ))
                ) : (
                  <span className="text-sm text-(--text-muted)">No permissions assigned.</span>
                )}
              </div>
            </div>
          </div>

          <DeleteConfirmModal
            open={pendingDelete}
            entityLabel={role.name}
            onConfirm={handleConfirmDelete}
            onCancel={() => setPendingDelete(false)}
            isDeleting={deleteRole.isPending}
          />
        </PermissionGuard>
      </AppLayout>
    </>
  );
}
