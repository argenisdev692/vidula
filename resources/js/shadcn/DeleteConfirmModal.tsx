import * as React from 'react';

// ══════════════════════════════════════════════════════════════════
// DeleteConfirmModal
//
// Usage:
//   <DeleteConfirmModal
//     open={pendingDelete !== null}
//     entityLabel={pendingDelete?.name ?? ''}
//     onConfirm={handleConfirmDelete}
//     onCancel={() => setPendingDelete(null)}
//     isDeleting={mutation.isPending}
//   />
// ══════════════════════════════════════════════════════════════════

interface DeleteConfirmModalProps {
  open: boolean;
  /** The name / identifier of the item being deleted — shown inside a highlighted chip */
  entityLabel: string;
  onConfirm: () => void;
  onCancel: () => void;
  isDeleting?: boolean;
}

const IconTrash = () => (
  <svg
    width={28}
    height={28}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth={1.8}
    strokeLinecap="round"
    strokeLinejoin="round"
  >
    <polyline points="3 6 5 6 21 6" />
    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
    <line x1="10" y1="11" x2="10" y2="17" />
    <line x1="14" y1="11" x2="14" y2="17" />
  </svg>
);

const Spinner = () => (
  <svg
    width={16}
    height={16}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth={2.5}
    strokeLinecap="round"
    style={{ animation: 'spin 0.8s linear infinite' }}
  >
    <path d="M21 12a9 9 0 11-6.219-8.56" />
  </svg>
);

export function DeleteConfirmModal({
  open,
  entityLabel,
  onConfirm,
  onCancel,
  isDeleting = false,
}: DeleteConfirmModalProps): React.JSX.Element | null {
  // ── Close on Escape ──────────────────────────────────────────
  React.useEffect(() => {
    if (!open) return;
    function onKey(e: KeyboardEvent): void {
      if (e.key === 'Escape' && !isDeleting) onCancel();
    }
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open, isDeleting, onCancel]);

  // ── Prevent scroll when open ─────────────────────────────────
  React.useEffect(() => {
    if (open) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => { document.body.style.overflow = ''; };
  }, [open]);

  if (!open) return null;

  return (
    <>
      {/* ── Global spinner keyframe (injected once) ── */}
      <style>{`
        @keyframes dcm-spin { to { transform: rotate(360deg); } }
        @keyframes dcm-in {
          from { opacity: 0; transform: scale(0.93) translateY(8px); }
          to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .dcm-spin { animation: dcm-spin 0.8s linear infinite; }
        .dcm-card { animation: dcm-in 0.18s cubic-bezier(0.16, 1, 0.3, 1) both; }
      `}</style>

      {/* ── Backdrop ───────────────────────────────────────────── */}
      <div
        onClick={!isDeleting ? onCancel : undefined}
        style={{
          position: 'fixed',
          inset: 0,
          zIndex: 9999,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          padding: '1rem',
          background: 'color-mix(in srgb, #000 55%, transparent)',
          backdropFilter: 'blur(6px)',
          WebkitBackdropFilter: 'blur(6px)',
        }}
        aria-modal="true"
        role="dialog"
        aria-labelledby="dcm-title"
      >
        {/* ── Card ─────────────────────────────────────────────── */}
        <div
          className="dcm-card"
          onClick={(e) => e.stopPropagation()}
          style={{
            width: '100%',
            maxWidth: 420,
            borderRadius: 20,
            padding: '2rem 2rem 1.75rem',
            fontFamily: 'var(--font-sans)',
            background: 'color-mix(in srgb, var(--bg-card) 92%, transparent)',
            border: '1px solid color-mix(in srgb, var(--accent-error) 30%, var(--border-default))',
            boxShadow: '0 24px 60px color-mix(in srgb, #000 40%, transparent), 0 0 0 1px color-mix(in srgb, var(--accent-error) 10%, transparent)',
            backdropFilter: 'blur(20px)',
            WebkitBackdropFilter: 'blur(20px)',
          }}
        >
          {/* Icon zone */}
          <div
            style={{
              width: 56,
              height: 56,
              borderRadius: 14,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              marginBottom: '1.25rem',
              background: 'color-mix(in srgb, var(--accent-error) 12%, transparent)',
              color: 'var(--accent-error)',
              border: '1px solid color-mix(in srgb, var(--accent-error) 25%, transparent)',
            }}
          >
            <IconTrash />
          </div>

          {/* Heading */}
          <h2
            id="dcm-title"
            style={{
              margin: '0 0 0.5rem',
              fontSize: '1.125rem',
              fontWeight: 700,
              color: 'var(--text-primary)',
              letterSpacing: '-0.01em',
            }}
          >
            Delete confirmation
          </h2>

          {/* Body */}
          <p
            style={{
              margin: '0 0 1.5rem',
              fontSize: '0.875rem',
              color: 'var(--text-muted)',
              lineHeight: 1.6,
            }}
          >
            Are you sure you want to delete{' '}
            <span
              style={{
                display: 'inline-block',
                padding: '1px 8px',
                borderRadius: 6,
                fontSize: '0.8125rem',
                fontWeight: 600,
                color: 'var(--accent-error)',
                background: 'color-mix(in srgb, var(--accent-error) 10%, transparent)',
                border: '1px solid color-mix(in srgb, var(--accent-error) 20%, transparent)',
                wordBreak: 'break-all',
              }}
            >
              {entityLabel}
            </span>
            ?{' '}This action cannot be undone.
          </p>

          {/* Actions */}
          <div style={{ display: 'flex', gap: '0.75rem', justifyContent: 'flex-end' }}>
            {/* Cancel */}
            <button
              onClick={onCancel}
              disabled={isDeleting}
              style={{
                padding: '0.5rem 1.25rem',
                borderRadius: 10,
                fontSize: '0.875rem',
                fontWeight: 600,
                cursor: isDeleting ? 'not-allowed' : 'pointer',
                border: '1px solid var(--border-default)',
                background: 'transparent',
                color: 'var(--text-secondary)',
                fontFamily: 'var(--font-sans)',
                transition: 'all 0.15s',
                opacity: isDeleting ? 0.5 : 1,
              }}
              onMouseEnter={(e) => {
                if (!isDeleting) {
                  (e.currentTarget as HTMLButtonElement).style.background = 'var(--bg-surface)';
                }
              }}
              onMouseLeave={(e) => {
                (e.currentTarget as HTMLButtonElement).style.background = 'transparent';
              }}
            >
              Cancel
            </button>

            {/* Confirm delete */}
            <button
              onClick={onConfirm}
              disabled={isDeleting}
              style={{
                padding: '0.5rem 1.25rem',
                borderRadius: 10,
                fontSize: '0.875rem',
                fontWeight: 600,
                cursor: isDeleting ? 'not-allowed' : 'pointer',
                border: '1px solid color-mix(in srgb, var(--accent-error) 50%, transparent)',
                background: 'color-mix(in srgb, var(--accent-error) 15%, transparent)',
                color: 'var(--accent-error)',
                fontFamily: 'var(--font-sans)',
                transition: 'all 0.15s',
                display: 'flex',
                alignItems: 'center',
                gap: '0.4rem',
                opacity: isDeleting ? 0.8 : 1,
              }}
              onMouseEnter={(e) => {
                if (!isDeleting) {
                  (e.currentTarget as HTMLButtonElement).style.background = 'color-mix(in srgb, var(--accent-error) 25%, transparent)';
                }
              }}
              onMouseLeave={(e) => {
                (e.currentTarget as HTMLButtonElement).style.background = 'color-mix(in srgb, var(--accent-error) 15%, transparent)';
              }}
            >
              {isDeleting ? (
                <>
                  <svg
                    width={14}
                    height={14}
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={2.5}
                    strokeLinecap="round"
                    className="dcm-spin"
                  >
                    <path d="M21 12a9 9 0 11-6.219-8.56" />
                  </svg>
                  Deleting…
                </>
              ) : (
                'Delete'
              )}
            </button>
          </div>
        </div>
      </div>
    </>
  );
}
