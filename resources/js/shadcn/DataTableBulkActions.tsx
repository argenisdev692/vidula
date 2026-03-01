import * as React from 'react';
import { DeleteConfirmModal } from '@/shadcn/DeleteConfirmModal';

// ══════════════════════════════════════════════════════════════════
// DataTableBulkActions
//
// Renders an animated bar when count > 0.
// Delete button opens a DeleteConfirmModal before proceeding.
//
// Usage:
//   <DataTableBulkActions
//     count={Object.keys(rowSelection).length}
//     onDelete={handleBulkDelete}
//     onRestore={handleBulkRestore}        // optional
//     isDeleting={deleteMutation.isPending}
//   />
// ══════════════════════════════════════════════════════════════════

interface DataTableBulkActionsProps {
  count: number;
  onDelete: () => void;
  onRestore?: () => void;
  isDeleting?: boolean;
  isRestoring?: boolean;
}

const IconTrash = () => (
  <svg width={14} height={14} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
    <polyline points="3 6 5 6 21 6" />
    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
  </svg>
);

const IconRefresh = () => (
  <svg width={14} height={14} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
    <polyline points="1 4 1 10 7 10" />
    <path d="M3.51 15a9 9 0 102.13-9.36L1 10" />
  </svg>
);

export function DataTableBulkActions({
  count,
  onDelete,
  onRestore,
  isDeleting = false,
  isRestoring = false,
}: DataTableBulkActionsProps): React.JSX.Element | null {
  const [modalOpen, setModalOpen] = React.useState<boolean>(false);

  if (count === 0) return null;

  function handleDeleteConfirm(): void {
    onDelete();
    setModalOpen(false);
  }

  return (
    <>
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          gap: '0.75rem',
          padding: '0.625rem 1rem',
          borderRadius: 12,
          marginBottom: '0.75rem',
          background: 'color-mix(in srgb, var(--accent-primary) 8%, var(--bg-card))',
          border: '1px solid color-mix(in srgb, var(--accent-primary) 25%, var(--border-default))',
          fontFamily: 'var(--font-sans)',
          animation: 'bulk-slide-in 0.2s cubic-bezier(0.16, 1, 0.3, 1) both',
        }}
      >
        <style>{`
          @keyframes bulk-slide-in {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
          }
        `}</style>

        {/* Count badge */}
        <span
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            justifyContent: 'center',
            minWidth: 24,
            height: 24,
            padding: '0 8px',
            borderRadius: 8,
            fontSize: '0.75rem',
            fontWeight: 700,
            background: 'var(--accent-primary)',
            color: '#fff',
          }}
        >
          {count}
        </span>

        <span style={{ fontSize: '0.8125rem', fontWeight: 500, color: 'var(--text-secondary)', flex: 1 }}>
          {count === 1 ? '1 row selected' : `${count} rows selected`}
        </span>

        {/* Restore button — optional */}
        {onRestore && (
          <button
            onClick={onRestore}
            disabled={isRestoring || isDeleting}
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '0.375rem',
              padding: '0.375rem 0.875rem',
              borderRadius: 8,
              fontSize: '0.8125rem',
              fontWeight: 600,
              cursor: (isRestoring || isDeleting) ? 'not-allowed' : 'pointer',
              border: '1px solid color-mix(in srgb, var(--accent-success, #22c55e) 30%, var(--border-default))',
              background: 'color-mix(in srgb, var(--accent-success, #22c55e) 10%, transparent)',
              color: 'var(--accent-success, #22c55e)',
              fontFamily: 'var(--font-sans)',
              transition: 'all 0.15s',
              opacity: (isRestoring || isDeleting) ? 0.5 : 1,
            }}
          >
            <IconRefresh />
            {isRestoring ? 'Restoring…' : 'Restore'}
          </button>
        )}

        {/* Delete button */}
        <button
          onClick={() => setModalOpen(true)}
          disabled={isDeleting || isRestoring}
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '0.375rem',
            padding: '0.375rem 0.875rem',
            borderRadius: 8,
            fontSize: '0.8125rem',
            fontWeight: 600,
            cursor: (isDeleting || isRestoring) ? 'not-allowed' : 'pointer',
            border: '1px solid color-mix(in srgb, var(--accent-error) 30%, var(--border-default))',
            background: 'color-mix(in srgb, var(--accent-error) 10%, transparent)',
            color: 'var(--accent-error)',
            fontFamily: 'var(--font-sans)',
            transition: 'all 0.15s',
            opacity: (isDeleting || isRestoring) ? 0.5 : 1,
          }}
        >
          <IconTrash />
          {isDeleting ? 'Deleting…' : 'Delete selected'}
        </button>
      </div>

      <DeleteConfirmModal
        open={modalOpen}
        entityLabel={`${count} selected ${count === 1 ? 'item' : 'items'}`}
        onConfirm={handleDeleteConfirm}
        onCancel={() => setModalOpen(false)}
        isDeleting={isDeleting}
      />
    </>
  );
}
