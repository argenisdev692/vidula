import * as React from 'react';

// ══════════════════════════════════════════════════════════════
// Icons
// ══════════════════════════════════════════════════════════════
const IconDownload = () => (
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
    <polyline points="7 10 12 15 17 10" />
    <line x1="12" y1="15" x2="12" y2="3" />
  </svg>
);

const IconExcel = () => (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
    <polyline points="14 2 14 8 20 8" />
    <line x1="8" y1="13" x2="16" y2="13" />
    <line x1="8" y1="17" x2="16" y2="17" />
    <line x1="10" y1="9" x2="8" y2="9" />
  </svg>
);

const IconPdf = () => (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
    <polyline points="14 2 14 8 20 8" />
    <path d="M9 15h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H9v-3z" />
    <path d="M13 15v3h2" />
    <path d="M13 16.5h1.5" />
  </svg>
);

interface ExportButtonProps {
  onExport: (format: 'excel' | 'pdf') => void;
  isExporting?: boolean;
}

export function ExportButton({ onExport, isExporting }: ExportButtonProps): React.JSX.Element {
  const [isOpen, setIsOpen] = React.useState(false);
  const containerRef = React.useRef<HTMLDivElement>(null);

  React.useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  return (
    <div className="relative" ref={containerRef}>
      <button
        onClick={() => setIsOpen(!isOpen)}
        disabled={isExporting}
        className="flex h-10 cursor-pointer items-center justify-center gap-2 rounded-lg border px-4 text-sm font-medium transition-all hover:shadow-sm"
        style={{
          background: 'var(--bg-card)',
          borderColor: 'var(--border-default)',
          color: 'var(--text-primary)',
          opacity: isExporting ? 0.6 : 1,
        }}
      >
        <IconDownload />
        <span className="hidden sm:inline">Export</span>
        {isExporting && (
           <div className="h-3 w-3 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        )}
      </button>

      {isOpen && (
        <div
          className="absolute right-0 top-full z-50 mt-2 w-48 overflow-hidden rounded-xl border p-1 shadow-xl animate-in fade-in zoom-in duration-200"
          style={{
            background: 'var(--bg-card)',
            borderColor: 'var(--border-default)',
          }}
        >
          <button
            onClick={() => {
              onExport('excel');
              setIsOpen(false);
            }}
            className="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-900 dark:text-gray-100 transition-all hover:bg-muted"
          >
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-green-500/10 text-green-500">
              <IconExcel />
            </div>
            <div className="flex flex-col items-start">
              <span className="font-semibold">Excel</span>
              <span className="text-[10px]" style={{ color: 'var(--text-disabled)' }}>Download as .xlsx</span>
            </div>
          </button>

          <button
            onClick={() => {
              onExport('pdf');
              setIsOpen(false);
            }}
            className="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-900 dark:text-gray-100 transition-all hover:bg-muted"
          >
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-red-500/10 text-red-500">
              <IconPdf />
            </div>
            <div className="flex flex-col items-start">
              <span className="font-semibold">PDF Report</span>
              <span className="text-[10px]" style={{ color: 'var(--text-disabled)' }}>Download for print</span>
            </div>
          </button>
        </div>
      )}
    </div>
  );
}
