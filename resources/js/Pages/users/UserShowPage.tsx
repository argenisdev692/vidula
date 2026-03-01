import * as React from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import UserStatusBadge from '@/modules/users/components/UserStatusBadge';
import { useUserMutations } from '@/modules/users/hooks/useUserMutations';
import type { UserDetail } from '@/types/users';

// ══════════════════════════════════════════════════════════════
// Icons
// ══════════════════════════════════════════════════════════════
const ic = {
  w: 16, h: 16, viewBox: '0 0 24 24', fill: 'none',
  stroke: 'currentColor', strokeWidth: 2,
  strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const,
};
const IconArrowLeft = () => <svg {...ic}><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>;
const IconEdit = () => <svg {...ic}><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>;
const IconTrash = () => <svg {...ic}><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>;

// ══════════════════════════════════════════════════════════════
// Info Row
// ══════════════════════════════════════════════════════════════
function InfoRow({ label, value }: { label: string; value: string | null | undefined }): React.JSX.Element {
  return (
    <div className="grid grid-cols-3 gap-4 py-3" style={{ borderBottom: '1px solid var(--border-subtle)' }}>
      <dt className="text-sm font-medium" style={{ color: 'var(--text-muted)' }}>{label}</dt>
      <dd className="col-span-2 text-sm font-medium text-gray-900 dark:text-gray-100">{value || '—'}</dd>
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

  async function handleDelete(): Promise<void> {
    if (!confirm(`Are you sure you want to delete "${user.full_name}"?`)) return;
    try {
      await deleteUser.mutateAsync(user.uuid);
      router.visit('/users');
    } catch (err) {
      console.error('Failed to delete user', err);
    }
  }

  const initialsStr = `${user.name[0] ?? ''}${user.last_name?.[0] ?? ''}`.toUpperCase();

  return (
    <AppLayout>
      <div style={{ fontFamily: 'var(--font-sans)' }}>
        {/* ── Header ── */}
        <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-3">
            <Link
              href="/users"
              className="flex h-9 w-9 items-center justify-center rounded-lg transition-all"
              style={{
                color: 'var(--text-muted)',
                border: '1px solid var(--border-default)',
                background: 'var(--bg-card)',
              }}
            >
              <IconArrowLeft />
            </Link>
            <div>
              <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">
                User Details
              </h1>
              <p className="text-sm" style={{ color: 'var(--text-muted)' }}>
                #{user.uuid}
              </p>
            </div>
          </div>

          <div className="flex gap-3">
            <Link
              href={`/users/${user.uuid}/edit`}
              className="btn-modern btn-modern-primary px-4 py-2"
            >
              <IconEdit /> Edit
            </Link>
            <button
              onClick={() => void handleDelete()}
              className="btn-modern btn-modern-danger px-4 py-2"
            >
              <IconTrash /> Delete
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
                  background: 'linear-gradient(135deg, var(--color-aqua), var(--color-aqua-dark))',
                  color: '#ffffff',
                }}
              >
                {initialsStr}
              </div>
            )}
            <div>
              <h2 className="text-lg font-bold text-gray-900 dark:text-gray-100">
                {user.full_name}
              </h2>
              {user.username && (
                <p className="text-sm" style={{ color: 'var(--text-muted)' }}>@{user.username}</p>
              )}
              <div className="mt-1">
                <UserStatusBadge status={user.status} />
              </div>
            </div>
          </div>

          {/* ── Personal Info ── */}
          <h3 className="mb-2 text-[11px] font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>
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
          <h3 className="mb-2 mt-6 text-[11px] font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>
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
          <h3 className="mb-2 mt-6 text-[11px] font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>
            Metadata
          </h3>
          <dl>
            <InfoRow label="Created" value={user.created_at ? new Date(user.created_at).toLocaleString() : null} />
            <InfoRow label="Updated" value={user.updated_at ? new Date(user.updated_at).toLocaleString() : null} />
          </dl>
        </div>
      </div>
    </AppLayout>
  );
}
