import * as React from 'react';
import {
  createColumnHelper,
  type ColumnDef,
  type RowSelectionState,
  type OnChangeFn,
} from '@tanstack/react-table';
import { DataTable } from '@/shadcn/data-table';
import { Link } from '@inertiajs/react';
import type { StudentListItem } from '@/types/api';
import { useStudentMutations } from '@/modules/students/hooks/useStudentMutations';
import { formatDateShort } from '@/common/helpers/formatDate';
import { Building2, Eye, Pencil, Trash2, CheckCircle } from 'lucide-react';

/**
 * StudentsTable — TanStack Table v8 with sorting, filtering, and row selection.
 * columnHelper is defined outside the component to avoid re-creation on every render.
 */

// ── columnHelper outside component (performance) ──
const columnHelper = createColumnHelper<StudentListItem>();

interface StudentsTableProps {
  data: StudentListItem[];
  isLoading: boolean;
  isError?: boolean;
  onDelete: (uuid: string, name: string) => void;
  rowSelection: RowSelectionState;
  onRowSelectionChange: OnChangeFn<RowSelectionState>;
}

export default function StudentsTable({
  data,
  isLoading,
  isError = false,
  onDelete,
  rowSelection,
  onRowSelectionChange,
}: StudentsTableProps) {
  const { restoreStudent } = useStudentMutations();

  const columns = React.useMemo<ColumnDef<StudentListItem, any>[]>(() => [
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
    columnHelper.accessor('name', {
      header: 'Student',
      cell: (info) => {
        const student = info.row.original;
        return (
          <div className="flex items-center gap-3 text-left">
            <div
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-bold shadow-sm"
              style={{
                background: 'color-mix(in srgb, var(--accent-success) 15%, transparent)',
                color: 'var(--accent-success)',
              }}
            >
              <Building2 size={18} />
            </div>
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                {student.name}
              </p>
              {student.dni && (
                <p className="truncate text-[11px] mt-0.5" style={{ color: 'var(--text-disabled)' }}>
                  DNI: {student.dni}
                </p>
              )}
            </div>
          </div>
        );
      },
    }),
    columnHelper.accessor('email', {
      header: 'Contact',
      cell: (info) => {
        const student = info.row.original;
        return (
          <div className="flex flex-col">
            <span className="text-sm" style={{ color: 'var(--text-secondary)' }}>
              {student.email || '—'}
            </span>
            {student.phone && (
              <span className="text-[11px]" style={{ color: 'var(--text-muted)' }}>
                {student.phone}
              </span>
            )}
          </div>
        );
      },
    }),
    columnHelper.accessor('active', {
      header: 'Status',
      cell: (info) => {
        const isActive = info.getValue() as boolean;
        return (
          <span className={`inline-flex items-center uppercase rounded-full px-2 py-0.5 text-[10px] font-bold tracking-wider ${isActive ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'}`}>
            {isActive ? 'Active' : 'Inactive'}
          </span>
        );
      },
    }),
    columnHelper.accessor('created_at', {
      header: 'Created',
      cell: (info) => {
        const val = info.getValue() as string | undefined;
        return (
          <span className="text-sm" style={{ color: 'var(--text-muted)' }}>
            {formatDateShort(val)}
          </span>
        );
      },
    }),
    columnHelper.display({
      id: 'actions',
      header: 'Actions',
      cell: (info) => {
        const student = info.row.original;
        const isDeleted = !!student.deleted_at;

        return (
          <div className="flex items-center justify-end gap-2 pr-4">
            <Link
              href={`/student/${student.id}`}
              className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
              title="View Profile"
            >
              <Eye size={16} />
            </Link>

            {!isDeleted ? (
              <>
                <Link
                  href={`/student/${student.id}/edit`}
                  className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
                  title="Edit"
                >
                  <Pencil size={16} />
                </Link>
                <button
                  onClick={() => onDelete(student.id, student.name)}
                  className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-red-500/10 text-(--accent-error) shadow-sm transition-colors"
                  title="Delete"
                >
                  <Trash2 size={16} />
                </button>
              </>
            ) : (
              <button
                onClick={() => restoreStudent.mutate(student.id)}
                className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-green-500/10 text-(--accent-success) shadow-sm transition-colors"
                title="Restore"
                disabled={restoreStudent.isPending}
              >
                <CheckCircle size={16} />
              </button>
            )}
          </div>
        );
      },
    }),
  ], [onDelete, restoreStudent]);

  return (
    <DataTable
      columns={columns}
      data={data}
      isLoading={isLoading}
      isError={isError}
      noDataMessage="No students found"
      rowSelection={rowSelection}
      onRowSelectionChange={onRowSelectionChange}
      getRowId={(row: StudentListItem) => row.id}
    />
  );
}
