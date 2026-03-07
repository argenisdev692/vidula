import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, KeyRound, Save } from 'lucide-react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { PremiumField } from '@/common/form/PremiumField';
import { usePermissionMutations } from '@/modules/permissions/hooks/usePermissionMutations';
import type { CreatePermissionPayload, PermissionsCreatePageProps, RoleOption } from '@/types/permissions';

export default function PermissionCreatePage({ available_roles }: PermissionsCreatePageProps): React.JSX.Element {
  const [form, setForm] = React.useState<CreatePermissionPayload>({
    name: '',
    guard_name: 'web',
    roles: [],
  });
  const [errors, setErrors] = React.useState<Record<string, string>>({});
  const { createPermission } = usePermissionMutations();

  function handleChange(event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>): void {
    const { name, value } = event.target;
    setForm((previous) => ({
      ...previous,
      [name]: value,
      ...(name === 'guard_name' ? { roles: [] } : {}),
    }));

    if (errors[name]) {
      setErrors((previous) => ({ ...previous, [name]: '' }));
    }

    if (name === 'guard_name' && errors.roles) {
      setErrors((previous) => ({ ...previous, roles: '' }));
    }
  }

  function handleRoleToggle(roleName: string): void {
    setForm((previous) => ({
      ...previous,
      roles: previous.roles.includes(roleName)
        ? previous.roles.filter((role) => role !== roleName)
        : [...previous.roles, roleName],
    }));
  }

  async function handleSubmit(event: React.FormEvent): Promise<void> {
    event.preventDefault();

    createPermission.mutate(form, {
      onSuccess: () => {
        router.visit('/permissions');
      },
      onError: (error: Error) => {
        const axiosError = error as { response?: { data?: { errors?: Record<string, string[]> } } };
        if (axiosError.response?.data?.errors) {
          const serverErrors: Record<string, string> = {};
          for (const [key, messages] of Object.entries(axiosError.response.data.errors)) {
            serverErrors[key] = messages[0] ?? '';
          }
          setErrors(serverErrors);
        }
      },
    });
  }

  const visibleRoles = React.useMemo(
    () => available_roles.filter((role) => role.guard_name === form.guard_name),
    [available_roles, form.guard_name],
  );

  return (
    <AppLayout>
      <Head title="Create Permission" />
      <PermissionGuard permissions={['CREATE_PERMISSIONS']}>
        <form onSubmit={handleSubmit} className="mx-auto flex max-w-5xl flex-col gap-8 animate-in fade-in duration-300">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <Link
                href="/permissions"
                prefetch
                className="flex h-10 w-10 items-center justify-center rounded-xl border border-(--border-default) bg-(--bg-card) text-(--text-muted) shadow-sm transition-all hover:bg-(--bg-hover) hover:text-(--accent-primary)"
              >
                <ArrowLeft size={20} />
              </Link>
              <div>
                <h1 className="text-2xl font-bold tracking-tight text-(--text-primary)">New Permission</h1>
                <p className="text-sm text-(--text-muted)">Create a new action-level permission for the platform</p>
              </div>
            </div>

            <button
              type="submit"
              disabled={createPermission.isPending}
              className="btn-modern btn-modern-primary flex items-center gap-2 px-6 py-2.5 shadow-lg transition-all hover:shadow-xl disabled:opacity-50"
            >
              {createPermission.isPending ? (
                <span className="animate-pulse">Saving...</span>
              ) : (
                <>
                  <Save size={18} />
                  <span className="font-bold">Save Permission</span>
                </>
              )}
            </button>
          </div>

          <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-1">
              <div className="card-modern space-y-6 border border-(--border-default) p-8 shadow-xl">
                <PremiumField
                  label="Permission Name"
                  name="name"
                  value={form.name}
                  onChange={handleChange}
                  required
                  error={errors.name}
                  placeholder="EXPORT_REPORTS"
                />

                <div className="flex flex-col gap-2">
                  <label className="text-[11px] font-bold uppercase tracking-widest text-(--text-secondary)">
                    Guard
                  </label>
                  <select
                    name="guard_name"
                    value={form.guard_name}
                    onChange={handleChange}
                    className="w-full rounded-xl border border-(--border-default) bg-(--bg-card) px-4 py-3 text-sm text-(--text-primary) outline-none transition-all focus:ring-2 focus:ring-(--accent-primary)"
                  >
                    <option value="web">Web</option>
                    <option value="sanctum">Sanctum</option>
                  </select>
                  {errors.guard_name && <p className="text-xs text-(--accent-error)">{errors.guard_name}</p>}
                </div>
              </div>
            </div>

            <div className="space-y-6 lg:col-span-2">
              <div className="card-modern border border-(--border-default) p-8 shadow-xl">
                <div className="mb-6 flex items-center gap-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-(--bg-hover)">
                    <KeyRound size={16} className="text-(--accent-primary)" />
                  </div>
                  <h2 className="text-lg font-bold text-(--text-primary)">Assignable Roles</h2>
                </div>

                <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                  {visibleRoles.map((role: RoleOption) => {
                    const checked = form.roles.includes(role.name);

                    return (
                      <label
                        key={role.uuid}
                        className="flex cursor-pointer items-center gap-3 rounded-xl border border-(--border-default) bg-(--bg-card) px-4 py-3 transition-all hover:bg-(--bg-hover)"
                      >
                        <input
                          type="checkbox"
                          checked={checked}
                          onChange={() => handleRoleToggle(role.name)}
                          className="h-4 w-4 rounded border border-(--border-default) accent-(--accent-primary)"
                        />
                        <div className="flex flex-col">
                          <span className="text-sm font-medium text-(--text-primary)">{role.name}</span>
                          <span className="text-[11px] uppercase tracking-wide text-(--text-muted)">{role.guard_name}</span>
                        </div>
                      </label>
                    );
                  })}
                </div>
                {errors.roles && <p className="mt-3 text-xs text-(--accent-error)">{errors.roles}</p>}
              </div>
            </div>
          </div>
        </form>
      </PermissionGuard>
    </AppLayout>
  );
}
