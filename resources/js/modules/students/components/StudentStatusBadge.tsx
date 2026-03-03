import * as React from 'react';

type StudentBadgeStatus = 'active' | 'inactive' | 'deleted' | 'DRAFT' | 'ACTIVE' | 'INACTIVE' | 'GRADUATED' | 'SUSPENDED';

interface StudentStatusBadgeProps {
  status?: StudentBadgeStatus;
}

const STATUS_CONFIG: Record<
  string,
  { label: string; bg: string; text: string; dot: string }
> = {
  active: {
    label: 'Active',
    bg: 'color-mix(in srgb, var(--accent-success) 15%, transparent)',
    text: 'var(--accent-success)',
    dot: 'var(--accent-success)',
  },
  ACTIVE: {
    label: 'Active',
    bg: 'color-mix(in srgb, var(--accent-success) 15%, transparent)',
    text: 'var(--accent-success)',
    dot: 'var(--accent-success)',
  },
  inactive: {
    label: 'Inactive',
    bg: 'color-mix(in srgb, var(--accent-warning) 15%, transparent)',
    text: 'var(--accent-warning)',
    dot: 'var(--accent-warning)',
  },
  INACTIVE: {
    label: 'Inactive',
    bg: 'color-mix(in srgb, var(--accent-warning) 15%, transparent)',
    text: 'var(--accent-warning)',
    dot: 'var(--accent-warning)',
  },
  DRAFT: {
    label: 'Draft',
    bg: 'color-mix(in srgb, var(--accent-info) 15%, transparent)',
    text: 'var(--accent-info)',
    dot: 'var(--accent-info)',
  },
  GRADUATED: {
    label: 'Graduated',
    bg: 'color-mix(in srgb, var(--accent-secondary) 15%, transparent)',
    text: 'var(--accent-secondary)',
    dot: 'var(--accent-secondary)',
  },
  SUSPENDED: {
    label: 'Suspended',
    bg: 'color-mix(in srgb, var(--accent-error) 15%, transparent)',
    text: 'var(--accent-error)',
    dot: 'var(--accent-error)',
  },
  deleted: {
    label: 'Deleted',
    bg: 'color-mix(in srgb, var(--text-disabled) 15%, transparent)',
    text: 'var(--text-disabled)',
    dot: 'var(--text-disabled)',
  },
};

export default function StudentStatusBadge({
  status = 'active',
}: StudentStatusBadgeProps): React.JSX.Element {
  const config = STATUS_CONFIG[status] ?? STATUS_CONFIG.active;

  return (
    <span
      className="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
      style={{
        background: config.bg,
        color: config.text,
        fontFamily: 'var(--font-sans)',
      }}
    >
      <span
        className="h-1.5 w-1.5 rounded-full"
        style={{ background: config.dot }}
      />
      {config.label}
    </span>
  );
}
