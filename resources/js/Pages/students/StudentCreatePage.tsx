import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { useStudentMutations } from '@/modules/students/hooks/useStudentMutations';
import { PremiumField } from '@/common/form/PremiumField';
import type { CreateStudentDTO, StudentStatus } from '@/types/api';
import { ArrowLeft, Save, User, FileText } from 'lucide-react';

/**
 * StudentCreatePage — Create a new student.
 * Uses standard form with FormData API and TanStack Query mutations.
 */
export default function StudentCreatePage(): React.JSX.Element {
  const { createStudent } = useStudentMutations();
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>): Promise<void> {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);

    const payload: CreateStudentDTO = {
      name: formData.get('name') as string,
      email: (formData.get('email') as string) || null,
      phone: (formData.get('phone') as string) || null,
      dni: (formData.get('dni') as string) || null,
      birthDate: (formData.get('birth_date') as string) || null,
      address: (formData.get('address') as string) || null,
      notes: (formData.get('notes') as string) || null,
      status: (formData.get('status') as StudentStatus) || 'DRAFT',
      active: formData.get('active') === 'on',
    };

    try {
      await createStudent.mutateAsync(payload);
      router.visit('/students');
    } catch (err: unknown) {
      const error = err as { response?: { data?: { errors?: Record<string, string> } } };
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      }
    }
  }

  const isPending = createStudent.isPending;

  return (
    <AppLayout>
      <Head title="New Student" />
      <PermissionGuard permissions={['CREATE_STUDENTS']}>
      <div className="max-w-4xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-12">

        {/* ── Header ── */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/students"
              className="flex h-10 w-10 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) hover:text-(--accent-primary) transition-all shadow-sm"
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1 className="text-2xl font-bold tracking-tight text-(--text-primary)">New Student</h1>
              <p className="text-sm text-(--text-muted)">Register a new student profile</p>
            </div>
          </div>

          <button
            type="submit"
            form="student-create-form"
            disabled={isPending}
            className="btn-modern btn-modern-primary flex items-center gap-2 px-6 py-2.5 rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
          >
            {isPending ? (
              <span className="animate-pulse">Creating...</span>
            ) : (
              <>
                <Save size={18} />
                <span className="font-bold">Save Student</span>
              </>
            )}
          </button>
        </div>

        {/* Global Error */}
        {errors.general && (
          <div className="p-4 rounded-xl border border-(--accent-error) bg-(--accent-error)/10">
            <p className="text-sm text-(--accent-error)">{errors.general}</p>
          </div>
        )}

        {/* ── Form Body ── */}
        <form id="student-create-form" onSubmit={handleSubmit}>
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* ── Left Column: Main Info ── */}
            <div className="lg:col-span-2 space-y-6">
              <div className="card-modern p-8 space-y-8 shadow-xl border border-(--border-default)">
                <div className="flex items-center gap-3">
                  <User className="text-(--accent-primary)" size={24} />
                  <h2 className="text-lg font-bold text-(--text-primary)">Personal Information</h2>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="md:col-span-2">
                    <PremiumField
                      label="Full Name"
                      name="name"
                      required
                      error={errors.name}
                      placeholder="John Doe"
                    />
                  </div>
                  <PremiumField
                    label="Email"
                    name="email"
                    type="email"
                    error={errors.email}
                    placeholder="student@example.com"
                  />
                  <PremiumField
                    label="Phone"
                    name="phone"
                    error={errors.phone}
                    placeholder="+1 (555) 000-0000"
                  />
                  <PremiumField
                    label="DNI / ID Number"
                    name="dni"
                    error={errors.dni}
                    placeholder="12345678A"
                  />
                  <PremiumField
                    label="Birth Date"
                    name="birth_date"
                    type="date"
                    error={errors['birth_date']}
                  />
                  <div className="md:col-span-2">
                    <PremiumField
                      label="Address"
                      name="address"
                      error={errors.address}
                      isTextArea
                      placeholder="123 Main Street, City, Country"
                    />
                  </div>
                </div>
              </div>

              <div className="card-modern p-8 space-y-8 shadow-xl border border-(--border-default)">
                <div className="flex items-center gap-3">
                  <FileText className="text-(--accent-primary)" size={24} />
                  <h2 className="text-lg font-bold text-(--text-primary)">Additional Details</h2>
                </div>
                <PremiumField
                  label="Notes"
                  name="notes"
                  error={errors.notes}
                  isTextArea
                  placeholder="Any additional information about the student..."
                />
              </div>
            </div>

            {/* ── Right Column: Sidebar ── */}
            <div className="space-y-6">
              <div className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle)">
                <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-secondary) mb-4">Status & Visibility</h3>

                <div className="space-y-4">
                  <div className="flex flex-col gap-2">
                    <label className="text-[11px] font-bold uppercase tracking-widest text-(--text-secondary)">
                      Status
                    </label>
                    <select
                      name="status"
                      defaultValue="DRAFT"
                      className="w-full rounded-xl px-4 py-3 text-sm outline-none bg-(--bg-card) border border-(--border-default) text-(--text-primary) focus:ring-2 focus:ring-(--accent-primary)"
                    >
                      <option value="DRAFT">Draft</option>
                      <option value="ACTIVE">Active</option>
                      <option value="INACTIVE">Inactive</option>
                      <option value="GRADUATED">Graduated</option>
                      <option value="SUSPENDED">Suspended</option>
                    </select>
                  </div>

                  <div className="flex items-center justify-between px-4 py-3 rounded-xl bg-(--bg-card) border border-(--border-default)">
                    <label htmlFor="active" className="text-sm font-medium text-(--text-primary) cursor-pointer">
                      Active
                    </label>
                    <input
                      type="checkbox"
                      id="active"
                      name="active"
                      defaultChecked={true}
                      className="h-5 w-5 rounded accent-(--accent-primary) cursor-pointer"
                    />
                  </div>
                </div>
              </div>

              <div className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle)">
                <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-secondary) mb-4">Quick Tips</h3>
                <ul className="space-y-2 text-xs text-(--text-muted)">
                  <li className="flex items-start gap-2">
                    <span className="text-(--accent-primary) mt-0.5">•</span>
                    <span>Student name is the only required field</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-(--accent-primary) mt-0.5">•</span>
                    <span>Email and DNI must be unique if provided</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-(--accent-primary) mt-0.5">•</span>
                    <span>Students start as Draft status by default</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </form>
      </div>
      </PermissionGuard>
    </AppLayout>
  );
}
