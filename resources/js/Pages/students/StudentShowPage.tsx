import * as React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useStudent } from '@/modules/students/hooks/useStudent';
import StudentStatusBadge from '@/modules/students/components/StudentStatusBadge';
import type { PageProps } from '@inertiajs/core';
import {
  ArrowLeft, Pencil, Mail, Phone, MapPin,
  User, Calendar, CreditCard
} from 'lucide-react';

/**
 * StudentShowPage — View a single student's details.
 * Reads `studentId` prop from Inertia (set by StudentPageController::show()).
 */
export default function StudentShowPage(): React.JSX.Element {
  const { props } = usePage<PageProps & { studentId: string }>();
  const uuid = props.studentId;

  const { data: student, isPending, isError } = useStudent(uuid);

  if (isPending) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] items-center justify-center">
          <div className="h-10 w-10 border-4 border-(--accent-primary) border-t-transparent rounded-full animate-spin" />
        </div>
      </AppLayout>
    );
  }

  if (isError || !student) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] flex-col items-center justify-center gap-4">
          <p style={{ color: 'var(--accent-error)' }}>Failed to load student profile.</p>
          <Link href="/students" className="text-sm text-(--accent-primary) hover:underline">
            ← Back to Students
          </Link>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <Head title={`${student.name} | Student`} />
      <div style={{ fontFamily: 'var(--font-sans)', maxWidth: '900px', margin: '0 auto' }}>

        {/* ── Header ── */}
        <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/students"
              className="flex h-9 w-9 items-center justify-center rounded-lg transition-all hover:bg-(--bg-hover)"
              style={{ color: 'var(--text-muted)' }}
            >
              <ArrowLeft size={16} />
            </Link>
            <div>
              <h1 className="text-2xl font-bold tracking-tight text-(--text-primary)">
                {student.name}
              </h1>
              <div className="mt-1 flex items-center gap-3">
                <StudentStatusBadge status={student.deletedAt ? 'deleted' : student.status} />
                <span className="text-xs" style={{ color: 'var(--text-muted)' }}>
                  ID: {student.id.substring(0, 8)}...
                </span>
                <span className="text-xs" style={{ color: 'var(--text-muted)' }}>
                  Registered: {new Date(student.createdAt).toLocaleDateString()}
                </span>
              </div>
            </div>
          </div>
          <Link
            href={`/students/${student.id}/edit`}
            className="btn-modern btn-modern-primary inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition-all shadow-md hover:shadow-lg"
          >
            <Pencil size={16} /> Edit Student
          </Link>
        </div>

        {/* ── Grid Layout ── */}
        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">

          {/* Main Info Column */}
          <div className="md:col-span-2 space-y-6">

            {/* Contact Details Card */}
            <div className="card-modern shadow-md p-6">
              <h2 className="mb-4 text-base font-semibold text-(--text-primary)">
                Contact Information
              </h2>
              <div className="space-y-4">
                <InfoRow icon={<User size={16} />} label="Full Name" value={student.name} />
                <InfoRow
                  icon={<Mail size={16} />}
                  label="Email"
                  value={student.email ? (
                    <a href={`mailto:${student.email}`} className="hover:underline" style={{ color: 'var(--accent-info)' }}>
                      {student.email}
                    </a>
                  ) : 'Not specified'}
                />
                <InfoRow
                  icon={<Phone size={16} />}
                  label="Phone"
                  value={student.phone ? (
                    <a href={`tel:${student.phone}`} className="hover:underline" style={{ color: 'var(--accent-info)' }}>
                      {student.phone}
                    </a>
                  ) : 'Not specified'}
                />
                <InfoRow icon={<CreditCard size={16} />} label="DNI / ID" value={student.dni ?? 'Not specified'} />
                <InfoRow icon={<Calendar size={16} />} label="Birth Date" value={student.birthDate ?? 'Not specified'} />
                <InfoRow icon={<MapPin size={16} />} label="Address" value={student.address ?? 'Not specified'} />
              </div>
            </div>

            {/* Notes Card */}
            <div className="card-modern shadow-md p-6">
              <h2 className="mb-4 text-base font-semibold text-(--text-primary)">
                Notes
              </h2>
              <p className="text-sm whitespace-pre-wrap leading-relaxed" style={{ color: 'var(--text-secondary)' }}>
                {student.notes ?? 'No notes recorded.'}
              </p>
            </div>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            <div className="card-modern p-6">
              <h2 className="mb-4 text-base font-semibold text-(--text-primary)">Status</h2>
              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-muted)' }}>Status</span>
                  <span className="text-sm font-bold text-(--accent-primary)">{student.status}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-muted)' }}>Active</span>
                  <span className={`text-sm font-bold ${student.active ? 'text-(--accent-success)' : 'text-(--accent-error)'}`}>
                    {student.active ? 'Yes' : 'No'}
                  </span>
                </div>
              </div>
            </div>

            <div className="card-modern p-6">
              <h2 className="mb-4 text-base font-semibold text-(--text-primary)">Metadata</h2>
              <div className="space-y-3 text-sm" style={{ color: 'var(--text-secondary)' }}>
                <MetaRow label="Created" value={new Date(student.createdAt).toLocaleString()} />
                <MetaRow label="Updated" value={student.updatedAt ? new Date(student.updatedAt).toLocaleString() : 'Never'} />
                {student.deletedAt && (
                  <MetaRow label="Deleted" value={new Date(student.deletedAt).toLocaleString()} />
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}

/** ── Helper Components ── */
function InfoRow({ icon, label, value }: { icon: React.ReactNode; label: string; value: React.ReactNode }) {
  return (
    <div className="flex items-start gap-3">
      <div className="pt-0.5" style={{ color: 'var(--text-muted)' }}>{icon}</div>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--text-disabled)' }}>{label}</p>
        <p className="mt-1 text-sm" style={{ color: 'var(--text-secondary)' }}>
          {typeof value === 'string' ? value : value}
        </p>
      </div>
    </div>
  );
}

function MetaRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between border-b pb-2" style={{ borderColor: 'var(--border-subtle)' }}>
      <span style={{ color: 'var(--text-disabled)' }}>{label}</span>
      <span>{value}</span>
    </div>
  );
}
