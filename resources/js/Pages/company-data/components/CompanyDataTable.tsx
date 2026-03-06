import * as React from 'react';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { DataTable } from '@/shadcn/data-table';
import type { CompanyDataListItem } from '@/types/api';
import { formatDateShort } from '@/common/helpers/formatDate';

import { Building2, Eye, Pencil } from 'lucide-react';

const columnHelper = createColumnHelper<CompanyDataListItem>();

// ══════════════════════════════════════════════════════════════
// Props
// ══════════════════════════════════════════════════════════════
interface CompanyDataTableProps {
  data: CompanyDataListItem[];
  isLoading: boolean;
  isError?: boolean;
  onDelete?: (uuid: string, name: string) => void;
  rowSelection: Record<string, boolean>;
  onRowSelectionChange: (updater: any) => void;
}

export default function CompanyDataTable({
  data,
  isLoading,
  isError,
  rowSelection,
  onRowSelectionChange,
}: CompanyDataTableProps) {

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
                background: 'color-mix(in srgb, var(--accent-secondary) 15%, transparent)',
                color: 'var(--accent-secondary)',
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
        const val = info.getValue() as string | null | undefined;
        return (
          <span className="text-sm font-medium" style={{ color: 'var(--text-primary)' }}>
            {val ? formatDateShort(val) : '—'}
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
              href={`/company-data/${company.uuid}`}
              prefetch
              className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
              title="View"
            >
              <Eye size={16} />
            </Link>
            <Link
              href={`/company-data/${company.uuid}/edit`}
              prefetch
              className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
              title="Edit"
            >
              <Pencil size={16} />
            </Link>
          </div>
        );
      },
    }),
  ], []);

  return (
    <DataTable
      columns={columns}
      data={data}
      isLoading={isLoading}
      isError={isError}
      noDataMessage="No companies found"
      rowSelection={rowSelection}
      onRowSelectionChange={onRowSelectionChange}
      /* eslint-disable-next-line @typescript-eslint/no-explicit-any */
      getRowId={(row: any) => row.uuid}
    />
  );
}
