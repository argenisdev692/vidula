import * as React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';
import { useStudent } from '@/modules/students/hooks/useStudent';
import { useStudentMutations } from '@/modules/students/hooks/useStudentMutations';
import { PremiumField } from '@/common/form/PremiumField';
import type { UpdateStudentDTO, StudentStatus } from '@/types/api';
import type { PageProps } from '@inertiajs/core';
import { ArrowLeft, Save, User, FileText } from 'lucide-react';

/**
 * StudentEditPage — Edit an existing student.
 * Reads `studentId` prop from Inertia (set by StudentPageController::edit()).
 */
export default function StudentEditPage(): React.JSX.Element {
  const { props } = usePage<PageProps & { studentId: string }>();
  const uuid = props.studentId;

  const { data: student, isPending: isLoadingStudent } = useStudent(uuid);
  const { updateStudent } = useStudentMutations();
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>): Promise<void> {
    e.preventDefault();
    if (!uuid) return;

    const formData = new FormData(e.currentTarget);

    const payload: UpdateStudentDTO = {
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
      await updateStudent.mutateAsync({ uuid, payload });
      router.visit('/students');
    } catch (err: unknown) {
      const error = err as { response?: { data?: { errors?: Record<string, string> } } };
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      }
    }
  }

  const isPending = updateStudent.isPending;

  if (isLoadingStudent) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] flex-col items-center justify-center gap-4">
          <div className="h-10 w-10 border-4 border-(--accent-primary) border-t-transparent rounded-full animate-spin" />
          <p className="text-sm font-medium text-(--text-disabled) animate-pulse">Loading student...</p>
        </div>
      </AppLayout>
    );
  }

  if (!student) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] flex-col items-center justify-center gap-4">
          <p className="text-sm font-medium text-(--accent-error)">Student not found</p>
          <Link href="/students" className="text-sm text-(--accent-primary) hover:underline">
            ← Back to Students
          </Link>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <Head title={`Edit Student | ${student.name}`} />
      <PermissionGuard permissions={['UPDATE_STUDENTS']}>
      <div className="max-w-4xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-12">

        {/* ── Header ── */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/students"
              className="flex h-10 w-10 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) transition-all shadow-sm"
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1 className="text-3xl font-extrabold tracking-tight text-(--text-primary)">
                Edit Student
              </h1>
              <p className="text-sm text-(--text-muted) font-medium">
                Update information for <span className="text-(--accent-primary)">{student.name}</span>
              </p>
            </div>
          </div>

          <button
            type="submit"
            form="student-edit-form"
            disabled={isPending}
            className="btn-modern btn-modern-primary flex items-center gap-2 px-8 py-3 rounded-xl shadow-xl hover:shadow-(--accent-primary)/20 transition-all font-bold disabled:opacity-50"
          >
            {isPending ? (
              <span className="animate-pulse">Saving...</span>
            ) : (
              <>
                <Save size={18} />
                Save Changes
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
        <form id="student-edit-form" onSubmit={handleSubmit}>
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* ── Left Column: Main Info ── */}
            <div className="lg:col-span-2 space-y-8">
              <section className="card-modern p-8 space-y-8 shadow-2xl border border-(--border-default)">
                <div className="flex items-center gap-3">
                  <User className="text-(--accent-primary)" size={24} />
                  <h2 className="text-xl font-bold text-(--text-primary)">Personal Information</h2>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="md:col-span-2">
                    <PremiumField
                      label="Full Name"
                      name="name"
                      defaultValue={student.name}
                      required
                      error={errors.name}
                      placeholder="John Doe"
                    />
                  </div>
                  <PremiumField
                    label="Email"
                    name="email"
                    type="email"
                    defaultValue={student.email || ''}
                    error={errors.email}
                    placeholder="student@example.com"
                  />
                  <PremiumField
                    label="Phone"
                    name="phone"
                    defaultValue={student.phone || ''}
                    error={errors.phone}
                    placeholder="+1 (555) 000-0000"
                  />
                  <PremiumField
                    label="DNI / ID Number"
                    name="dni"
                    defaultValue={student.dni || ''}
                    error={errors.dni}
                    placeholder="12345678A"
                  />
                  <PremiumField
                    label="Birth Date"
                    name="birth_date"
                    type="date"
                    defaultValue={student.birthDate || ''}
                    error={errors['birth_date']}
                  />
                  <div className="md:col-span-2">
                    <PremiumField
                      label="Address"
                      name="address"
                      defaultValue={student.address || ''}
                      error={errors.address}
                      isTextArea
                      placeholder="123 Main Street, City, Country"
                    />
                  </div>
                </div>
              </section>

              <section className="card-modern p-8 space-y-8 shadow-2xl border border-(--border-default)">
                <div className="flex items-center gap-3">
                  <FileText className="text-(--accent-primary)" size={24} />
                  <h2 className="text-xl font-bold text-(--text-primary)">Additional Details</h2>
                </div>
                <PremiumField
                  label="Notes"
                  name="notes"
                  defaultValue={student.notes || ''}
                  error={errors.notes}
                  isTextArea
                  placeholder="Any additional information about the student..."
                />
              </section>
            </div>

            {/* ── Right Column: Sidebar ── */}
            <div className="space-y-8">
              <section className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle) space-y-6">
                <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-secondary)">Status & Visibility</h3>

                <div className="space-y-4">
                  <div className="flex flex-col gap-2">
                    <label className="text-[11px] font-bold uppercase tracking-widest text-(--text-secondary)">
                      Status
                    </label>
                    <select
                      name="status"
                      defaultValue={student.status}
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
                      defaultChecked={student.active}
                      className="h-5 w-5 rounded accent-(--accent-primary) cursor-pointer"
                    />
                  </div>
                </div>
              </section>

              <section className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle) space-y-4">
                <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-secondary)">Metadata</h3>
                <div className="space-y-2 text-xs" style={{ color: 'var(--text-muted)' }}>
                  <div className="flex justify-between">
                    <span>UUID</span>
                    <span className="font-mono text-(--text-disabled)">{student.uuid.substring(0, 8)}...</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Created</span>
                    <span>{new Date(student.createdAt).toLocaleDateString()}</span>
                  </div>
                  {student.updatedAt && (
                    <div className="flex justify-between">
                      <span>Updated</span>
                      <span>{new Date(student.updatedAt).toLocaleDateString()}</span>
                    </div>
                  )}
                </div>
              </section>
            </div>
          </div>
        </form>
      </div>
      </PermissionGuard>
    </AppLayout>
  );
}
