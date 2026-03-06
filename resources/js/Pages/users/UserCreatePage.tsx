import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { useUserMutations } from '@/modules/users/hooks/useUserMutations';
import { PremiumField } from '@/common/form/PremiumField';
import type { CreateUserPayload } from '@/types/users';
import { ArrowLeft, Save } from 'lucide-react';

export default function UserCreatePage(): React.JSX.Element {
  const [form, setForm] = React.useState<CreateUserPayload>({
    name: '',
    last_name: '',
    email: '',
    phone: '',
    role: 'USER',
  });
  
  const [errors, setErrors] = React.useState<Record<string, string>>({});
  const { createUser } = useUserMutations();

  function handleChange(e: React.ChangeEvent<HTMLInputElement>): void {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors((prev) => ({ ...prev, [name]: '' }));
  }

  async function handleSubmit(e: React.FormEvent): Promise<void> {
    e.preventDefault();
    
    createUser.mutate(form, {
      onSuccess: () => {
        router.visit('/users');
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
    <AppLayout>
      <Head title="Create Platform User" />
      <PermissionGuard permissions={['CREATE_USERS']}>
      <form onSubmit={handleSubmit} className="max-w-4xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-300">
        
        {/* ── Header ── */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/users"
              prefetch
              className="flex h-10 w-10 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) hover:text-(--accent-primary) transition-all shadow-sm"
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1 className="text-2xl font-bold tracking-tight text-(--text-primary)">New User Account</h1>
              <p className="text-sm text-(--text-muted)">Register a new member in the platform</p>
            </div>
          </div>

          <button
            type="submit"
            disabled={createUser.isPending}
            className="btn-modern btn-modern-primary flex items-center gap-2 px-6 py-2.5 shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
          >
            {createUser.isPending ? (
              <span className="animate-pulse">Creating...</span>
            ) : (
              <>
                <Save size={18} />
                <span className="font-bold">Save User</span>
              </>
            )}
          </button>
        </div>

        {/* ── Form Body ── */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div className="lg:col-span-2 space-y-6">
                <div className="card-modern p-8 space-y-8 shadow-xl border border-(--border-default)">
                    <div className="flex items-center gap-3">
                        <div className="h-8 w-1 bg-(--accent-primary) rounded-full" />
                        <h2 className="text-lg font-bold text-(--text-primary)">Identity & Access</h2>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <PremiumField 
                            label="First Name" 
                            name="name" 
                            value={form.name} 
                            onChange={handleChange} 
                            required 
                            error={errors.name} 
                            placeholder="John"
                        />
                        <PremiumField 
                            label="Last Name" 
                            name="last_name" 
                            value={form.last_name} 
                            onChange={handleChange} 
                            required 
                            error={errors.last_name} 
                            placeholder="Doe"
                        />
                        <div className="md:col-span-2">
                            <PremiumField 
                                label="Email Address" 
                                name="email" 
                                type="email"
                                value={form.email} 
                                onChange={handleChange} 
                                required 
                                error={errors.email} 
                                placeholder="john.doe@example.com"
                            />
                        </div>
                        <PremiumField 
                            label="Phone Number" 
                            name="phone" 
                            value={form.phone || ''} 
                            onChange={handleChange} 
                            error={errors.phone} 
                            placeholder="+1 (555) 000-0000"
                        />
                    </div>
                </div>
            </div>

            <div className="space-y-6">
                <div className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle)">
                    <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-muted) mb-4">Account Config</h3>
                    
                    <div className="space-y-4">
                        <div className="p-4 rounded-xl bg-(--bg-card) border border-(--border-default) shadow-inner">
                            <p className="text-xs text-(--text-muted) leading-relaxed">
                                Users created via admin panel will receive an email to set up their password and finalize their profile.
                            </p>
                        </div>

                        <div className="flex flex-col gap-2">
                             <label className="text-[11px] font-bold uppercase tracking-widest text-(--text-secondary)">
                               Assign Role <span className="text-(--accent-error)">*</span>
                             </label>
                             <select 
                                name="role"
                                value={form.role}
                                onChange={(e) => setForm(p => ({ ...p, role: e.target.value }))}
                                className="w-full rounded-xl px-4 py-3 bg-(--bg-card) border border-(--border-default) text-sm outline-none focus:ring-2 focus:ring-(--accent-primary) transition-all"
                             >
                                 <option value="USER">User</option>
                                 <option value="ADMIN">Admin</option>
                                 <option value="SUPER_ADMIN">Super Admin</option>
                                 <option value="GUEST">Guest</option>
                             </select>
                             {errors.role && (
                               <p className="text-xs text-(--accent-error) mt-1">{errors.role}</p>
                             )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </form>
      </PermissionGuard>
    </AppLayout>
  );
}
