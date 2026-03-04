import * as React from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useUserMutations } from '@/modules/users/hooks/useUserMutations';
import { PremiumField } from '@/shadcn/PremiumField';
import type { UpdateUserPayload, UserDetail } from '@/types/users';
import { ArrowLeft, Save } from 'lucide-react';

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
      onError: (err: Error) => {
        const axiosErr = err as { response?: { data?: { errors?: Record<string, string[]> } } };
        if (axiosErr.response?.data?.errors) {
          const serverErrors: Record<string, string> = {};
          for (const [key, msgs] of Object.entries(axiosErr.response.data.errors)) {
            serverErrors[key] = msgs[0] ?? '';
          }
          setErrors(serverErrors);
        }
      }
    });
  }

  return (
    <>
      <Head title={`Edit — ${user.full_name}`} />
      <AppLayout>
        <div className="max-w-4xl mx-auto flex flex-col gap-8 animate-in fade-in duration-300">
          {/* ── Header ── */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <Link
                href={`/users/${user.uuid}`}
                className="flex h-10 w-10 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) hover:text-(--accent-primary) transition-all shadow-sm"
              >
                <ArrowLeft size={20} />
              </Link>
              <div>
                <h1 className="text-2xl font-bold tracking-tight text-(--text-primary)">
                  Edit User
                </h1>
                <p className="text-sm text-(--text-muted)">
                  {user.full_name}
                </p>
              </div>
            </div>

            <button
              onClick={(e) => void handleSubmit(e as unknown as React.FormEvent)}
              disabled={updateUser.isPending}
              className="btn-modern-primary flex items-center gap-2 px-6 py-2.5 shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
            >
              {updateUser.isPending ? (
                <span className="animate-pulse">Saving...</span>
              ) : (
                <>
                  <Save size={18} />
                  <span className="font-bold">Save Changes</span>
                </>
              )}
            </button>
          </div>

          {/* ── Form Body ── */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div className="space-y-6">
              <div className="card-modern p-8 space-y-8 shadow-xl border border-(--border-default)">
                <div className="flex items-center gap-3">
                  <div className="h-8 w-1 bg-(--accent-primary) rounded-full" />
                  <h2 className="text-lg font-bold text-(--text-primary)">Personal Information</h2>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <PremiumField label="First Name" name="name" value={form.name ?? ''} onChange={handleChange} required error={errors.name} placeholder="John" />
                  <PremiumField label="Last Name" name="last_name" value={form.last_name ?? ''} onChange={handleChange} placeholder="Doe" />
                  <div className="md:col-span-2">
                    <PremiumField label="Email Address" name="email" type="email" value={form.email ?? ''} onChange={handleChange} error={errors.email} placeholder="john.doe@example.com" />
                  </div>
                  <PremiumField label="Username" name="username" value={form.username ?? ''} onChange={handleChange} placeholder="johndoe" />
                  <PremiumField label="Phone Number" name="phone" value={form.phone ?? ''} onChange={handleChange} placeholder="+1 (555) 000-0000" />
                </div>
              </div>
            </div>

            <div className="space-y-6">
              <div className="card-modern p-8 space-y-8 shadow-xl border border-(--border-default)">
                <div className="flex items-center gap-3">
                  <div className="h-8 w-1 bg-(--accent-secondary) rounded-full" />
                  <h2 className="text-lg font-bold text-(--text-primary)">Address</h2>
                </div>

                <div className="grid grid-cols-1 gap-6">
                  <PremiumField label="Address" name="address" value={form.address ?? ''} onChange={handleChange} placeholder="123 Main St" />
                  <div className="grid grid-cols-2 gap-6">
                    <PremiumField label="City" name="city" value={form.city ?? ''} onChange={handleChange} placeholder="New York" />
                    <PremiumField label="State" name="state" value={form.state ?? ''} onChange={handleChange} placeholder="NY" />
                  </div>
                  <div className="grid grid-cols-2 gap-6">
                    <PremiumField label="Country" name="country" value={form.country ?? ''} onChange={handleChange} placeholder="United States" />
                    <PremiumField label="Zip Code" name="zip_code" value={form.zip_code ?? ''} onChange={handleChange} placeholder="10001" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}
