import * as React from 'react';

interface DataTableDateRangeFilterProps {
  dateFrom: string | undefined;
  dateTo: string | undefined;
  onChange: (range: { dateFrom?: string; dateTo?: string }) => void;
  className?: string;
}

export function DataTableDateRangeFilter({
  dateFrom,
  dateTo,
  onChange,
  className,
}: DataTableDateRangeFilterProps): React.JSX.Element {
  return (
    <div className={`flex items-center gap-3 ${className}`}>
      <div className="flex flex-col gap-1.5">
        <label className="text-[10px] font-bold uppercase tracking-wider text-(--text-secondary)">
          From
        </label>
        <input
          type="date"
          value={dateFrom || ''}
          onChange={(e) => onChange({ dateFrom: e.target.value || undefined, dateTo })}
          className="h-9 rounded-lg border border-(--border-default) bg-(--bg-card) px-3 text-sm text-(--text-primary) shadow-sm transition-all focus:border-(--accent-primary) focus:outline-none focus:ring-1 focus:ring-(--accent-primary)"
        />
      </div>
      <div className="flex flex-col gap-1.5">
        <label className="text-[10px] font-bold uppercase tracking-wider text-(--text-secondary)">
          To
        </label>
        <input
          type="date"
          value={dateTo || ''}
          onChange={(e) => onChange({ dateFrom, dateTo: e.target.value || undefined })}
          className="h-9 rounded-lg border border-(--border-default) bg-(--bg-card) px-3 text-sm text-(--text-primary) shadow-sm transition-all focus:border-(--accent-primary) focus:outline-none focus:ring-1 focus:ring-(--accent-primary)"
        />
      </div>
    </div>
  );
}
