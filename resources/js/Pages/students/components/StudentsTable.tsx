import * as React from 'react';
import {
  createColumnHelper,
  type ColumnDef,
  type RowSelectionState,
  type OnChangeFn,
  type SortingState,
  type ColumnFiltersState,
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  flexRender,
} from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import type { StudentListItem } from '@/types/api';
import { useStudentMutations } from '@/modules/students/hooks/useStudentMutations';
import { formatDateShort } from '@/common/helpers/formatDate';
import { Building2, Eye, Pencil, Trash2, CheckCircle, ArrowUpDown } from 'lucide-react';

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

  // TanStack Table v8: Local state for sorting and filtering
  const [sorting, setSorting] = React.useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>([]);

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
      header: ({ column }) => (
        <button
          onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
          className="flex items-center gap-2 hover:text-(--accent-primary) transition-colors"
        >
          Student
          <ArrowUpDown size={14} />
        </button>
      ),
      enableSorting: true,
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
      header: ({ column }) => (
        <button
          onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
          className="flex items-center gap-2 hover:text-(--accent-primary) transition-colors"
        >
          Contact
          <ArrowUpDown size={14} />
        </button>
      ),
      enableSorting: true,
      enableColumnFilter: true,
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
          <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${isActive ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'}`}>
            {isActive ? 'Active' : 'Inactive'}
          </span>
        );
      },
    }),
    columnHelper.accessor('created_at', {
      header: ({ column }) => (
        <button
          onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
          className="flex items-center gap-2 hover:text-(--accent-primary) transition-colors"
        >
          Created
          <ArrowUpDown size={14} />
        </button>
      ),
      enableSorting: true,
      sortingFn: 'datetime',
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
          <div className="flex items-center justify-end gap-1.5">
            <Link
              href={`/student/${student.id}`}
              className="btn-action btn-action-view"
              title="View Profile"
            >
              <Eye size={14} />
            </Link>

            {!isDeleted && (
               <Link
                 href={`/student/${student.id}/edit`}
                 className="btn-action btn-action-edit"
                 title="Edit"
               >
                 <Pencil size={14} />
               </Link>
            )}

            {isDeleted ? (
              <button
                onClick={() => restoreStudent.mutate(student.id)}
                className="btn-action btn-action-restore"
                title="Restore"
                disabled={restoreStudent.isPending}
              >
                <CheckCircle size={14} />
              </button>
            ) : (
              <button
                onClick={() => onDelete(student.id, student.name)}
                className="btn-action btn-action-delete"
                title="Delete"
              >
                <Trash2 size={14} />
              </button>
            )}
          </div>
        );
      },
    }),
  ], [onDelete, restoreStudent]);

  // TanStack Table v8: getRowId uses stable UUID instead of array index
  const table = useReactTable({
    data,
    columns,
    getRowId: (row) => row.id,
    state: {
      rowSelection,
      sorting,
      columnFilters,
    },
    onRowSelectionChange,
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    enableRowSelection: true,
    enableSorting: true,
    enableColumnFilters: true,
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-(--accent-primary)" />
      </div>
    );
  }

  if (isError) {
    return (
      <div className="flex items-center justify-center py-12 text-(--accent-error)">
        Error loading students
      </div>
    );
  }

  if (data.length === 0) {
    return (
      <div className="flex items-center justify-center py-12 text-(--text-muted)">
        No students found
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full">
        <thead>
          {table.getHeaderGroups().map(headerGroup => (
            <tr key={headerGroup.id} className="border-b border-(--border-subtle)">
              {headerGroup.headers.map(header => (
                <th
                  key={header.id}
                  className="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-(--text-muted)"
                >
                  {header.isPlaceholder
                    ? null
                    : flexRender(header.column.columnDef.header, header.getContext())}
                </th>
              ))}
            </tr>
          ))}
        </thead>
        <tbody>
          {table.getRowModel().rows.map(row => (
            <tr
              key={row.id}
              className="border-b border-(--border-subtle) hover:bg-(--bg-hover) transition-colors"
            >
              {row.getVisibleCells().map(cell => (
                <td key={cell.id} className="px-6 py-4">
                  {flexRender(cell.column.columnDef.cell, cell.getContext())}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
