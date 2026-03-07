import * as React from 'react';
import {
  createColumnHelper,
  type RowSelectionState,
  type OnChangeFn,
} from '@tanstack/react-table';
import { DataTable } from '@/common/data-table/DataTable';
import { Link } from '@inertiajs/react';
import { useAuthorization } from '@/modules/auth/hooks/useAuthorization';
import type { StudentListItem } from '@/types/api';
import { formatDateShort } from '@/common/helpers/formatDate';
import { Eye, Pencil, Trash2, CheckCircle, GraduationCap } from 'lucide-react';

/**
 * StudentsTable — TanStack Table v8 with sorting, filtering, and row selection.
 * columnHelper defined outside component per §7.
 * Actions: Eye/Pencil/Trash2 (active) | Eye/CheckCircle (soft-deleted) per §7.
 */

// ── columnHelper outside component (performance — §7) ──
const columnHelper = createColumnHelper<StudentListItem>();

interface StudentsTableProps {
  data: StudentListItem[];
  isLoading: boolean;
  isError?: boolean;
  onDelete: (uuid: string, name: string) => void;
  onRestore: (uuid: string, name: string) => void;
  rowSelection: RowSelectionState;
  onRowSelectionChange: OnChangeFn<RowSelectionState>;
}

export default function StudentsTable({
  data,
  isLoading,
  isError = false,
  onDelete,
  onRestore,
  rowSelection,
  onRowSelectionChange,
}: StudentsTableProps) {
  const { hasPermission } = useAuthorization();
  const canViewStudents = hasPermission('VIEW_STUDENTS');
  const canUpdateStudents = hasPermission('UPDATE_STUDENTS');
  const canDeleteStudents = hasPermission('DELETE_STUDENTS');

  const columns = React.useMemo(() => [
    ...(canDeleteStudents ? [
      columnHelper.display({
        id: 'select',
        header: ({ table }) => (
          <input
            type="checkbox"
            checked={table.getIsAllPageRowsSelected()}
            onChange={table.getToggleAllPageRowsSelectedHandler()}
            aria-label="Select all"
            className="h-4 w-4 rounded border-(--border-default) accent-(--accent-primary) cursor-pointer"
          />
        ),
        cell: ({ row }) => (
          <input
            type="checkbox"
            checked={row.getIsSelected()}
            onChange={row.getToggleSelectedHandler()}
            aria-label="Select row"
            className="h-4 w-4 rounded border-(--border-default) accent-(--accent-primary) cursor-pointer"
          />
        ),
      }),
    ] : []),
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
              <GraduationCap size={18} />
            </div>
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold text-(--text-primary)">
                {student.name}
              </p>
              {student.email && (
                <p className="truncate text-[11px] mt-0.5" style={{ color: 'var(--text-disabled)' }}>
                  {student.email}
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
    columnHelper.accessor('status', {
      header: 'Status',
      cell: (info) => {
        const status = info.getValue();
        const isActive = info.row.original.active;
        const colorStyle = isActive
          ? { background: 'color-mix(in srgb, var(--accent-success) 15%, transparent)', color: 'var(--accent-success)' }
          : { background: 'color-mix(in srgb, var(--accent-warning) 15%, transparent)', color: 'var(--accent-warning)' };
        return (
          <span className="inline-flex items-center uppercase rounded-full px-2 py-0.5 text-[10px] font-bold tracking-wider" style={colorStyle}>
            {status}
          </span>
        );
      },
    }),
    columnHelper.accessor('createdAt', {
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
        const isDeleted = !!student.deletedAt;

        return (
          <div className="flex items-center justify-end gap-2 pr-4">
            {canViewStudents && (
              <Link
                href={`/students/${student.uuid}`}
                className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
                title="View Profile"
                aria-label={`View ${student.name}`}
              >
                <Eye size={16} />
              </Link>
            )}

            {!isDeleted ? (
              <>
                {canUpdateStudents && (
                  <Link
                    href={`/students/${student.uuid}/edit`}
                    className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) text-(--text-secondary) shadow-sm transition-colors"
                    title="Edit"
                    aria-label={`Edit ${student.name}`}
                  >
                    <Pencil size={16} />
                  </Link>
                )}
                {canDeleteStudents && (
                  <button
                    onClick={() => onDelete(student.uuid, student.name)}
                    className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) shadow-sm transition-colors"
                    style={{ color: 'var(--accent-error)' }}
                    title="Delete"
                    aria-label={`Delete ${student.name}`}
                  >
                    <Trash2 size={16} />
                  </button>
                )}
              </>
            ) : (
              canUpdateStudents && (
                <button
                  onClick={() => onRestore(student.uuid, student.name)}
                  className="p-1.5 rounded-md border border-(--border-default) bg-(--bg-card) hover:bg-(--bg-hover) shadow-sm transition-colors"
                  style={{ color: 'var(--accent-success)' }}
                  title="Restore"
                  aria-label={`Restore ${student.name}`}
                >
                  <CheckCircle size={16} />
                </button>
              )
            )}
          </div>
        );
      },
    }),
  ], [canDeleteStudents, canUpdateStudents, canViewStudents, onDelete, onRestore]);

  return (
    <DataTable
      columns={columns}
      data={data}
      isLoading={isLoading}
      isError={isError}
      noDataMessage="No students found"
      rowSelection={rowSelection}
      onRowSelectionChange={onRowSelectionChange}
      getRowId={(row: StudentListItem) => row.uuid}
    />
  );
}
