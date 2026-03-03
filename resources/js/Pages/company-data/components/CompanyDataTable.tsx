import * as React from 'react';
import { createColumnHelper, type ColumnDef, type RowSelectionState, type OnChangeFn } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { DataTable } from '@/shadcn/data-table';
import type { CompanyDataListItem } from '@/types/api';

import { Building2, Eye, Pencil, Trash2 } from 'lucide-react';

// ══════════════════════════════════════════════════════════════
// Props
// ══════════════════════════════════════════════════════════════
interface CompanyDataTableProps {
  data: CompanyDataListItem[];
  isLoading: boolean;
  isError: boolean;
  onDelete: (uuid: string, name: string) => void;
  rowSelection: RowSelectionState;
  onRowSelectionChange: OnChangeFn<RowSelectionState>;
}

export default function CompanyDataTable({
  data,
  isLoading,
  isError,
  onDelete,
  rowSelection,
  onRowSelectionChange,
}: CompanyDataTableProps) {
  const columnHelper = createColumnHelper<CompanyDataListItem>();

  const columns = React.useMemo<ColumnDef<CompanyDataListItem, any>[]>(() => [
    columnHelper.display({
      id: 'select',
      header: ({ table }) => (
        <input
          type="checkbox"
          checked={table.getIsAllPageRowsSelected()}
          onChange={table.getToggleAllPageRowsSelectedHandler()}
          aria-label="Select all"
          className="h-4 w-4 rounded border-gray-300 accent-(--accent-primary) cursor-pointer"
        />
      ),
      cell: ({ row }) => (
        <input
          type="checkbox"
          checked={row.getIsSelected()}
          onChange={row.getToggleSelectedHandler()}
          aria-label="Select row"
          className="h-4 w-4 rounded border-gray-300 accent-(--accent-primary) cursor-pointer"
        />
      ),
    }),
    columnHelper.accessor('company_name', {
      header: 'Company',
      cell: (info) => {
        const company = info.row.original;
        return (
          <div className="flex items-center justify-center gap-3 text-left">
            <div
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
              style={{
                background: 'color-mix(in srgb, var(--purple-500) 15%, transparent)',
                color: 'var(--purple-500)',
              }}
            >
              <Building2 size={16} />
            </div>
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold uppercase text-gray-900 dark:text-gray-100">
                {company.company_name}
              </p>
              {company.name && (
                <p className="truncate text-[11px]" style={{ color: 'var(--text-secondary)' }}>
                  Rep: {company.name}
                </p>
              )}
            </div>
          </div>
        );
      },
    }),
    columnHelper.accessor('email', {
      header: 'Contact Email',
      cell: (info) => <span className="text-sm" style={{ color: 'var(--text-secondary)' }}>{info.getValue() ?? '—'}</span>,
    }),
    columnHelper.accessor('phone', {
      header: 'Phone',
      cell: (info) => <span className="text-sm" style={{ color: 'var(--text-secondary)' }}>{info.getValue() ?? '—'}</span>,
    }),
    columnHelper.accessor('created_at', {
      header: 'Created',
      cell: (info) => {
        const val = info.getValue();
        return (
          <span className="text-sm" style={{ color: 'var(--text-muted)' }}>
            {val ? new Date(val).toLocaleDateString() : '—'}
          </span>
        );
      },
    }),
    columnHelper.display({
      id: 'actions',
      header: 'Actions',
      cell: (info) => {
        const company = info.row.original;
        return (
          <div className="flex items-center justify-end gap-2 pr-4">
            <Link
              href={`/company-data/${company.id}`}
              className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
              title="View"
            >
              <Eye size={16} />
            </Link>
            <Link
              href={`/company-data/${company.id}/edit`}
              className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
              title="Edit"
            >
              <Pencil size={16} />
            </Link>
            <button
              onClick={() => onDelete(company.id, company.company_name)}
              className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-red-500/10 text-(--accent-error) shadow-sm transition-colors"
              title="Delete"
            >
              <Trash2 size={16} />
            </button>
          </div>
        );
      },
    }),
  ], [columnHelper, onDelete]);

  return (
    <DataTable
      columns={columns}
      data={data}
      isLoading={isLoading}
      isError={isError}
      noDataMessage="No companies found"
      rowSelection={rowSelection}
      onRowSelectionChange={onRowSelectionChange}
    />
  );
}
