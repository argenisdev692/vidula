import * as React from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import UserStatusBadge from '@/modules/users/components/UserStatusBadge';
import { useUserMutations } from '@/modules/users/hooks/useUserMutations';
import { DeleteConfirmModal } from '@/shadcn/DeleteConfirmModal';
import { formatDateShort } from '@/common/helpers/formatDate';
import type { UserDetail } from '@/types/users';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';

// ══════════════════════════════════════════════════════════════
// Info Row
// ══════════════════════════════════════════════════════════════
function InfoRow({ label, value }: { label: string; value: string | null | undefined }): React.JSX.Element {
  return (
    <div className="grid grid-cols-3 gap-4 py-3 border-b border-(--border-subtle)">
      <dt className="text-sm font-medium text-(--text-muted)">{label}</dt>
      <dd className="col-span-2 text-sm font-medium text-(--text-primary)">{value || '—'}</dd>
    </div>
  );
}

// ══════════════════════════════════════════════════════════════
// Props
// ══════════════════════════════════════════════════════════════
interface UserShowPageProps {
  user: UserDetail;
}

// ══════════════════════════════════════════════════════════════
// UserShowPage
// ══════════════════════════════════════════════════════════════
export default function UserShowPage({ user }: UserShowPageProps): React.JSX.Element {
  const { deleteUser } = useUserMutations();
  const [pendingDelete, setPendingDelete] = React.useState<boolean>(false);

  async function handleConfirmDelete(): Promise<void> {
    React.startTransition(async () => {
      try {
        await deleteUser.mutateAsync(user.uuid);
        router.visit('/users');
      } catch {
        // Error handled by mutation's onError toast
      }
    });
  }

  const initialsStr = `${user.name[0] ?? ''}${user.last_name?.[0] ?? ''}`.toUpperCase();

  return (
    <>
      <Head title={`User — ${user.full_name}`} />
      <AppLayout>
        <div style={{ fontFamily: 'var(--font-sans)' }}>
          {/* ── Header ── */}
          <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3">
              <Link
                href="/users"
                className="flex h-9 w-9 items-center justify-center rounded-lg border border-(--border-default) bg-(--bg-card) text-(--text-muted) hover:bg-(--bg-hover) transition-all"
              >
                <ArrowLeft size={16} />
              </Link>
              <div>
                <h1 className="text-xl font-bold text-(--text-primary)">
                  User Details
                </h1>
                <p className="text-sm text-(--text-muted)">
                  #{user.uuid}
                </p>
              </div>
            </div>

            <div className="flex gap-3">
              <Link
                href={`/users/${user.uuid}/edit`}
                className="btn-modern btn-modern-primary px-4 py-2 inline-flex items-center gap-2"
              >
                <Pencil size={16} /> Edit
              </Link>
              <button
                onClick={() => setPendingDelete(true)}
                className="btn-modern btn-modern-danger px-4 py-2 inline-flex items-center gap-2"
              >
                <Trash2 size={16} /> Delete
              </button>
            </div>
          </div>

          {/* ── User Profile Card ── */}
          <div className="card-modern shadow-lg p-6">
            {/* Avatar + name header */}
            <div className="mb-6 flex items-center gap-4">
              {user.profile_photo_path ? (
                <img
                  src={user.profile_photo_path}
                  alt={user.full_name}
                  className="h-16 w-16 rounded-xl object-cover"
                />
              ) : (
                <div
                  className="flex h-16 w-16 items-center justify-center rounded-xl text-lg font-bold"
                  style={{
                    background: 'var(--grad-primary)',
                    color: 'var(--text-primary)',
                  }}
                >
                  {initialsStr}
                </div>
              )}
              <div>
                <h2 className="text-lg font-bold text-(--text-primary)">
                  {user.full_name}
                </h2>
                {user.username && (
                  <p className="text-sm text-(--text-muted)">@{user.username}</p>
                )}
                <div className="mt-1">
                  <UserStatusBadge status={user.status} />
                </div>
              </div>
            </div>

            {/* ── Personal Info ── */}
            <h3 className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-(--text-secondary)">
              Personal Information
            </h3>
            <dl>
              <InfoRow label="First Name" value={user.name} />
              <InfoRow label="Last Name" value={user.last_name} />
              <InfoRow label="Email" value={user.email} />
              <InfoRow label="Username" value={user.username} />
              <InfoRow label="Phone" value={user.phone} />
            </dl>

            {/* ── Address ── */}
            <h3 className="mb-2 mt-6 text-[11px] font-semibold uppercase tracking-wider text-(--text-secondary)">
              Address
            </h3>
            <dl>
              <InfoRow label="Address" value={user.address} />
              <InfoRow label="City" value={user.city} />
              <InfoRow label="State" value={user.state} />
              <InfoRow label="Country" value={user.country} />
              <InfoRow label="Zip Code" value={user.zip_code} />
            </dl>

            {/* ── Metadata ── */}
            <h3 className="mb-2 mt-6 text-[11px] font-semibold uppercase tracking-wider text-(--text-secondary)">
              Metadata
            </h3>
            <dl>
              <InfoRow label="Created" value={formatDateShort(user.created_at)} />
              <InfoRow label="Updated" value={formatDateShort(user.updated_at)} />
            </dl>
          </div>
        </div>

        <DeleteConfirmModal
          open={pendingDelete}
          entityLabel={user.full_name}
          onConfirm={handleConfirmDelete}
          onCancel={() => setPendingDelete(false)}
          isDeleting={deleteUser.isPending}
        />
      </AppLayout>
    </>
  );
}
