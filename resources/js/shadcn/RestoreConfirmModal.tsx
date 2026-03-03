import * as React from 'react';

// ══════════════════════════════════════════════════════════════════
// RestoreConfirmModal
//
// Usage:
//   <RestoreConfirmModal
//     open={pendingRestore !== null}
//     entityLabel={pendingRestore?.name ?? ''}
//     onConfirm={handleConfirmRestore}
//     onCancel={() => setPendingRestore(null)}
//     isRestoring={mutation.isPending}
//   />
// ══════════════════════════════════════════════════════════════════

interface RestoreConfirmModalProps {
  open: boolean;
  /** The name / identifier of the item being restored — shown inside a highlighted chip */
  entityLabel: string;
  onConfirm: () => void;
  onCancel: () => void;
  isRestoring?: boolean;
}

const IconRestore = () => (
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
    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
    <path d="M3 3v5h5" />
    <path d="M12 7v5l4 2" />
  </svg>
);

export function RestoreConfirmModal({
  open,
  entityLabel,
  onConfirm,
  onCancel,
  isRestoring = false,
}: RestoreConfirmModalProps): React.JSX.Element | null {
  // ── Close on Escape ──────────────────────────────────────────
  React.useEffect(() => {
    if (!open) return;
    function onKey(e: KeyboardEvent): void {
      if (e.key === 'Escape' && !isRestoring) onCancel();
    }
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open, isRestoring, onCancel]);

  // ── Prevent scroll when open ─────────────────────────────────
  React.useEffect(() => {
    if (open) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => { document.body.style.overflow = ''; };
  }, [open]);

  // ── Focus trap: auto-focus confirm button ────────────────────
  const confirmRef = React.useRef<HTMLButtonElement>(null);
  React.useEffect(() => {
    if (open) {
      // Small delay to ensure the modal is rendered
      const timer = setTimeout(() => confirmRef.current?.focus(), 100);
      return () => clearTimeout(timer);
    }
  }, [open]);

  if (!open) return null;

  return (
    <>
      {/* ── Keyframe styles (injected once) ── */}
      <style>{`
        @keyframes rcm-spin { to { transform: rotate(360deg); } }
        @keyframes rcm-in {
          from { opacity: 0; transform: scale(0.93) translateY(8px); }
          to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .rcm-spin { animation: rcm-spin 0.8s linear infinite; }
        .rcm-card { animation: rcm-in 0.18s cubic-bezier(0.16, 1, 0.3, 1) both; }
      `}</style>

      {/* ── Backdrop ───────────────────────────────────────────── */}
      <div
        onClick={!isRestoring ? onCancel : undefined}
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
        aria-labelledby="rcm-title"
      >
        {/* ── Card ─────────────────────────────────────────────── */}
        <div
          className="rcm-card"
          onClick={(e) => e.stopPropagation()}
          style={{
            width: '100%',
            maxWidth: 420,
            borderRadius: 20,
            padding: '2rem 2rem 1.75rem',
            fontFamily: 'var(--font-sans)',
            background: 'color-mix(in srgb, var(--bg-card) 92%, transparent)',
            border: '1px solid color-mix(in srgb, var(--accent-success) 30%, var(--border-default))',
            boxShadow: '0 24px 60px color-mix(in srgb, #000 40%, transparent), 0 0 0 1px color-mix(in srgb, var(--accent-success) 10%, transparent)',
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
              background: 'color-mix(in srgb, var(--accent-success) 12%, transparent)',
              color: 'var(--accent-success)',
              border: '1px solid color-mix(in srgb, var(--accent-success) 25%, transparent)',
            }}
          >
            <IconRestore />
          </div>

          {/* Heading */}
          <h2
            id="rcm-title"
            style={{
              margin: '0 0 0.5rem',
              fontSize: '1.125rem',
              fontWeight: 700,
              color: 'var(--text-primary)',
              letterSpacing: '-0.01em',
            }}
          >
            Restore confirmation
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
            Are you sure you want to restore{' '}
            <span
              style={{
                display: 'inline-block',
                padding: '1px 8px',
                borderRadius: 6,
                fontSize: '0.8125rem',
                fontWeight: 600,
                color: 'var(--accent-success)',
                background: 'color-mix(in srgb, var(--accent-success) 10%, transparent)',
                border: '1px solid color-mix(in srgb, var(--accent-success) 20%, transparent)',
                wordBreak: 'break-all',
              }}
            >
              {entityLabel}
            </span>
            ?{' '}This will make the record active again.
          </p>

          {/* Actions */}
          <div style={{ display: 'flex', gap: '0.75rem', justifyContent: 'flex-end' }}>
            {/* Cancel */}
            <button
              onClick={onCancel}
              disabled={isRestoring}
              style={{
                padding: '0.5rem 1.25rem',
                borderRadius: 10,
                fontSize: '0.875rem',
                fontWeight: 600,
                cursor: isRestoring ? 'not-allowed' : 'pointer',
                border: '1px solid var(--border-default)',
                background: 'transparent',
                color: 'var(--text-secondary)',
                fontFamily: 'var(--font-sans)',
                transition: 'all 0.15s',
                opacity: isRestoring ? 0.5 : 1,
              }}
              onMouseEnter={(e) => {
                if (!isRestoring) {
                  (e.currentTarget as HTMLButtonElement).style.background = 'var(--bg-surface)';
                }
              }}
              onMouseLeave={(e) => {
                (e.currentTarget as HTMLButtonElement).style.background = 'transparent';
              }}
            >
              Cancel
            </button>

            {/* Confirm restore */}
            <button
              ref={confirmRef}
              onClick={onConfirm}
              disabled={isRestoring}
              style={{
                padding: '0.5rem 1.25rem',
                borderRadius: 10,
                fontSize: '0.875rem',
                fontWeight: 600,
                cursor: isRestoring ? 'not-allowed' : 'pointer',
                border: '1px solid color-mix(in srgb, var(--accent-success) 50%, transparent)',
                background: 'color-mix(in srgb, var(--accent-success) 15%, transparent)',
                color: 'var(--accent-success)',
                fontFamily: 'var(--font-sans)',
                transition: 'all 0.15s',
                display: 'flex',
                alignItems: 'center',
                gap: '0.4rem',
                opacity: isRestoring ? 0.8 : 1,
              }}
              onMouseEnter={(e) => {
                if (!isRestoring) {
                  (e.currentTarget as HTMLButtonElement).style.background = 'color-mix(in srgb, var(--accent-success) 25%, transparent)';
                }
              }}
              onMouseLeave={(e) => {
                (e.currentTarget as HTMLButtonElement).style.background = 'color-mix(in srgb, var(--accent-success) 15%, transparent)';
              }}
            >
              {isRestoring ? (
                <>
                  <svg
                    width={14}
                    height={14}
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={2.5}
                    strokeLinecap="round"
                    className="rcm-spin"
                  >
                    <path d="M21 12a9 9 0 11-6.219-8.56" />
                  </svg>
                  Restoring…
                </>
              ) : (
                'Restore'
              )}
            </button>
          </div>
        </div>
      </div>
    </>
  );
}
