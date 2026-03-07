import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { PremiumField } from '@/common/form/PremiumField';
import { useRoleMutations } from '@/modules/roles/hooks/useRoleMutations';
import type { PermissionOption, RolePageProps, UpdateRolePayload } from '@/types/roles';

export default function RoleEditPage({ role }: RolePageProps): React.JSX.Element {
  const [form, setForm] = React.useState<UpdateRolePayload>({
    name: role.name,
    guard_name: role.guard_name,
    permissions: role.permissions,
  });
  const [errors, setErrors] = React.useState<Record<string, string>>({});
  const { updateRole } = useRoleMutations();

  function handleChange(event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>): void {
    const { name, value } = event.target;
    setForm((previous) => ({
      ...previous,
      [name]: value,
      ...(name === 'guard_name' ? { permissions: [] } : {}),
    }));

    if (errors[name]) {
      setErrors((previous) => ({ ...previous, [name]: '' }));
    }

    if (name === 'guard_name' && errors.permissions) {
      setErrors((previous) => ({ ...previous, permissions: '' }));
    }
  }

  function handlePermissionToggle(permissionName: string): void {
    setForm((previous) => {
      const currentPermissions = previous.permissions ?? [];

      return {
        ...previous,
        permissions: currentPermissions.includes(permissionName)
          ? currentPermissions.filter((permission) => permission !== permissionName)
          : [...currentPermissions, permissionName],
      };
    });
  }

  async function handleSubmit(event: React.FormEvent): Promise<void> {
    event.preventDefault();

    updateRole.mutate(
      { uuid: role.uuid, payload: form },
      {
        onSuccess: () => {
          router.visit(`/roles/${role.uuid}`);
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
      },
    );
  }

  const currentGuard = form.guard_name ?? role.guard_name;
  const visiblePermissions = React.useMemo(
    () => role.available_permissions.filter((permission) => permission.guard_name === currentGuard),
    [currentGuard, role.available_permissions],
  );

  return (
    <>
      <Head title={`Edit Role — ${role.name}`} />
      <AppLayout>
        <PermissionGuard permissions={['UPDATE_ROLES']}>
          <form onSubmit={handleSubmit} className="mx-auto flex max-w-5xl flex-col gap-8 animate-in fade-in duration-300">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <Link
                  href={`/roles/${role.uuid}`}
                  prefetch
                  className="flex h-10 w-10 items-center justify-center rounded-xl border border-(--border-default) bg-(--bg-card) text-(--text-muted) shadow-sm transition-all hover:bg-(--bg-hover) hover:text-(--accent-primary)"
                >
                  <ArrowLeft size={20} />
                </Link>
                <div>
                  <h1 className="text-2xl font-bold tracking-tight text-(--text-primary)">Edit Role</h1>
                  <p className="text-sm text-(--text-muted)">{role.name}</p>
                </div>
              </div>

              <button
                type="submit"
                disabled={updateRole.isPending}
                className="btn-modern btn-modern-primary flex items-center gap-2 px-6 py-2.5 shadow-lg transition-all hover:shadow-xl disabled:opacity-50"
              >
                {updateRole.isPending ? (
                  <span className="animate-pulse">Saving...</span>
                ) : (
                  <>
                    <Save size={18} />
                    <span className="font-bold">Save Changes</span>
                  </>
                )}
              </button>
            </div>

            <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
              <div className="space-y-6 lg:col-span-1">
                <div className="card-modern space-y-6 border border-(--border-default) p-8 shadow-xl">
                  <PremiumField
                    label="Role Name"
                    name="name"
                    value={form.name ?? ''}
                    onChange={handleChange}
                    required
                    error={errors.name}
                    placeholder="MANAGER"
                  />

                  <div className="flex flex-col gap-2">
                    <label className="text-[11px] font-bold uppercase tracking-widest text-(--text-secondary)">
                      Guard
                    </label>
                    <select
                      name="guard_name"
                      value={currentGuard}
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
                    <div className="h-8 w-1 rounded-full bg-(--accent-primary)" />
                    <h2 className="text-lg font-bold text-(--text-primary)">Permissions</h2>
                  </div>

                  <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                    {visiblePermissions.map((permission: PermissionOption) => {
                      const checked = (form.permissions ?? []).includes(permission.name);

                      return (
                        <label
                          key={permission.uuid}
                          className="flex cursor-pointer items-center gap-3 rounded-xl border border-(--border-default) bg-(--bg-card) px-4 py-3 transition-all hover:bg-(--bg-hover)"
                        >
                          <input
                            type="checkbox"
                            checked={checked}
                            onChange={() => handlePermissionToggle(permission.name)}
                            className="h-4 w-4 rounded border border-(--border-default) accent-(--accent-primary)"
                          />
                          <div className="flex flex-col">
                            <span className="text-sm font-medium text-(--text-primary)">{permission.name}</span>
                            <span className="text-[11px] uppercase tracking-wide text-(--text-muted)">{permission.guard_name}</span>
                          </div>
                        </label>
                      );
                    })}
                  </div>
                  {errors.permissions && <p className="mt-3 text-xs text-(--accent-error)">{errors.permissions}</p>}
                </div>
              </div>
            </div>
          </form>
        </PermissionGuard>
      </AppLayout>
    </>
  );
}
