import * as React from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useUserMutations } from '@/modules/users/hooks/useUserMutations';
import type { UpdateUserPayload, UserDetail } from '@/types/users';

// ══════════════════════════════════════════════════════════════
// Icons
// ══════════════════════════════════════════════════════════════
const ic = {
  w: 16, h: 16, viewBox: '0 0 24 24', fill: 'none',
  stroke: 'currentColor', strokeWidth: 2,
  strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const,
};
const IconArrowLeft = () => <svg {...ic}><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>;

// ══════════════════════════════════════════════════════════════
// Form Field
// ══════════════════════════════════════════════════════════════
interface FieldProps {
  label: string;
  name: string;
  value: string;
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
  type?: string;
  required?: boolean;
  error?: string;
}

function Field({ label, name, value, onChange, type = 'text', required = false, error }: FieldProps): React.JSX.Element {
  return (
    <div className="space-y-1.5">
      <label
        htmlFor={name}
        className="block text-[12px] font-semibold uppercase tracking-wider"
        style={{ color: 'var(--text-muted)' }}
      >
        {label} {required && <span style={{ color: 'var(--accent-error)' }}>*</span>}
      </label>
      <input
        id={name}
        name={name}
        type={type}
        value={value}
        onChange={onChange}
        className="w-full rounded-lg px-3 py-2.5 text-sm outline-none transition-all"
        style={{
          background: 'var(--bg-surface)',
          border: error ? '1px solid var(--accent-error)' : '1px solid var(--border-default)',
          color: 'var(--text-primary)',
          fontFamily: 'var(--font-sans)',
        }}
      />
      {error && <p className="text-[11px]" style={{ color: 'var(--accent-error)' }}>{error}</p>}
    </div>
  );
}

// ══════════════════════════════════════════════════════════════
// Props
// ══════════════════════════════════════════════════════════════
interface UserEditPageProps {
  user: UserDetail;
}

// ══════════════════════════════════════════════════════════════
// UserEditPage
// ══════════════════════════════════════════════════════════════
export default function UserEditPage({ user }: UserEditPageProps): React.JSX.Element {
  const { updateUser } = useUserMutations();
  const [form, setForm] = React.useState<UpdateUserPayload>({
    name: user.name,
    email: user.email ?? '',
    last_name: user.last_name ?? '',
    username: user.username ?? '',
    phone: user.phone ?? '',
    address: user.address ?? '',
    city: user.city ?? '',
    state: user.state ?? '',
    country: user.country ?? '',
    zip_code: user.zip_code ?? '',
  });
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  function handleChange(e: React.ChangeEvent<HTMLInputElement>): void {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors((prev) => ({ ...prev, [name]: '' }));
  }

  function validate(): boolean {
    const errs: Record<string, string> = {};
    if (form.name !== undefined && !form.name.trim()) errs.name = 'Name is required';
    if (form.email !== undefined && form.email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email))
      errs.email = 'Invalid email format';
    setErrors(errs);
    return Object.keys(errs).length === 0;
  }

  async function handleSubmit(e: React.FormEvent): Promise<void> {
    e.preventDefault();
    if (!validate()) return;

    updateUser.mutate({ uuid: user.uuid, payload: form }, {
      onSuccess: () => {
        router.visit(`/users/${user.uuid}`);
      },
      onError: (err: any) => {
        if (err.response?.data?.errors) {
          const serverErrors: Record<string, string> = {};
          for (const [key, msgs] of Object.entries(err.response.data.errors)) {
            serverErrors[key] = (msgs as string[])[0] ?? '';
          }
          setErrors(serverErrors);
        }
      }
    });
  }

  return (
    <AppLayout>
      <div style={{ fontFamily: 'var(--font-sans)' }}>
        {/* ── Header ── */}
        <div className="mb-6 flex items-center gap-3">
          <Link
            href={`/users/${user.uuid}`}
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
              Edit User
            </h1>
            <p className="text-sm" style={{ color: 'var(--text-muted)' }}>
              {user.full_name}
            </p>
          </div>
        </div>

        {/* ── Form Card ── */}
        <form onSubmit={(e) => void handleSubmit(e)}>
          <div
            className="rounded-xl p-6"
            style={{
              background: 'var(--bg-card)',
              border: '1px solid var(--border-default)',
            }}
          >
            <h2 className="mb-4 text-sm font-semibold" style={{ color: 'var(--text-secondary)' }}>
              Personal Information
            </h2>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="First Name" name="name" value={form.name ?? ''} onChange={handleChange} required error={errors.name} />
              <Field label="Last Name" name="last_name" value={form.last_name ?? ''} onChange={handleChange} />
              <Field label="Email" name="email" value={form.email ?? ''} onChange={handleChange} type="email" error={errors.email} />
              <Field label="Username" name="username" value={form.username ?? ''} onChange={handleChange} />
              <Field label="Phone" name="phone" value={form.phone ?? ''} onChange={handleChange} />
            </div>

            <h2 className="mb-4 mt-8 text-sm font-semibold" style={{ color: 'var(--text-secondary)' }}>
              Address
            </h2>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="sm:col-span-2">
                <Field label="Address" name="address" value={form.address ?? ''} onChange={handleChange} />
              </div>
              <Field label="City" name="city" value={form.city ?? ''} onChange={handleChange} />
              <Field label="State" name="state" value={form.state ?? ''} onChange={handleChange} />
              <Field label="Country" name="country" value={form.country ?? ''} onChange={handleChange} />
              <Field label="Zip Code" name="zip_code" value={form.zip_code ?? ''} onChange={handleChange} />
            </div>
          </div>

          {/* ── Actions ── */}
          <div className="mt-4 flex justify-end gap-3">
            <Link
              href={`/users/${user.uuid}`}
              className="rounded-lg px-4 py-2.5 text-sm font-medium transition-all"
              style={{
                color: 'var(--text-muted)',
                border: '1px solid var(--border-default)',
              }}
            >
              Cancel
            </Link>
            <button
              type="submit"
              disabled={updateUser.isPending}
              className="rounded-lg px-5 py-2.5 text-sm font-semibold transition-all disabled:opacity-50"
              style={{
                background: 'linear-gradient(135deg, var(--color-aqua), var(--color-aqua-dark))',
                color: '#ffffff',
                boxShadow: '0 2px 8px color-mix(in srgb, var(--color-aqua) 30%, transparent)',
              }}
            >
              {updateUser.isPending ? 'Saving...' : 'Save Changes'}
            </button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
