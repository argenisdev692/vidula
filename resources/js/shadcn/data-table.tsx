import {
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
  flexRender,
  type TableOptions,
  type RowSelectionState,
  type OnChangeFn,
} from '@tanstack/react-table';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/shadcn/table';

type SoftDeleteRow = {
  deletedAt?: string | null;
  deleted_at?: string | null;
};

interface DataTableProps<TData extends SoftDeleteRow> {
  columns: TableOptions<TData>['columns'];
  data: TData[];
  isPending?: boolean;
  isLoading?: boolean;
  isError?: boolean;
  noDataMessage?: string;
  errorMessage?: string;
  loadingMessage?: string;
  
  // Optional Row Selection
  rowSelection?: RowSelectionState;
  onRowSelectionChange?: OnChangeFn<RowSelectionState>;
  // Optional row ID function for stable IDs (avoids index-based IDs)
  getRowId?: TableOptions<TData>['getRowId'];
}

export function DataTable<TData extends SoftDeleteRow>({
  columns,
  data,
  isPending = false,
  isLoading = false,
  isError = false,
  noDataMessage = 'No results.',
  errorMessage = 'Failed to load data. Please try again.',
  loadingMessage = 'Loading...',
  rowSelection,
  onRowSelectionChange,
  getRowId,
}: DataTableProps<TData>) {
  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    enableRowSelection: rowSelection !== undefined,
    onRowSelectionChange,
    getRowId,
    state: {
      rowSelection: rowSelection ?? {},
    },
  });

  return (
    <div className="overflow-x-auto">
      <Table className="w-full min-w-[700px]">
        <TableHeader>
          {table.getHeaderGroups().map((headerGroup) => (
            <TableRow key={headerGroup.id} style={{ borderBottom: '1px solid var(--border-subtle)' }} className="hover:bg-transparent">
              {headerGroup.headers.map((header) => {
                return (
                  <TableHead
                    key={header.id}
                    className="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider h-auto"
                    style={{ color: 'var(--text-disabled)' }}
                  >
                    {header.isPlaceholder
                      ? null
                      : flexRender(
                          header.column.columnDef.header,
                          header.getContext()
                        )}
                  </TableHead>
                );
              })}
            </TableRow>
          ))}
        </TableHeader>
        <TableBody>
          {isError ? (
            <TableRow className="hover:bg-transparent">
              <TableCell
                colSpan={columns.length}
                className="h-24 text-center text-sm"
                style={{ color: 'var(--accent-error)' }}
              >
                {errorMessage}
              </TableCell>
            </TableRow>
          ) : (isPending || isLoading) ? (
            <TableRow className="hover:bg-transparent">
              <TableCell
                colSpan={columns.length}
                className="h-24 text-center text-sm"
                style={{ color: 'var(--text-muted)' }}
              >
                <div className="flex flex-col items-center justify-center gap-3">
                  <div className="h-8 w-8 animate-spin rounded-full border-b-2" style={{ borderColor: 'var(--accent-primary)' }} />
                  <span style={{ color: 'var(--text-muted)' }}>{loadingMessage}</span>
                </div>
              </TableCell>
            </TableRow>
          ) : table.getRowModel().rows?.length ? (
            table.getRowModel().rows.map((row) => {
              const isDeleted = Boolean(row.original.deletedAt || row.original.deleted_at);
              
              return (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && 'selected'}
                  className="transition-colors duration-150 group text-center"
                  style={{
                    borderBottom: '1px solid var(--border-subtle)',
                    ...(isDeleted && {
                      background: 'var(--deleted-row-bg)',
                      opacity: 'var(--deleted-row-opacity)',
                      borderLeft: '2px solid var(--deleted-row-border)',
                    }),
                  }}
                >
                {row.getVisibleCells().map((cell) => (
                  <TableCell key={cell.id} className="px-4 py-3 h-auto text-center align-middle">
                    {flexRender(
                      cell.column.columnDef.cell,
                      cell.getContext()
                    )}
                  </TableCell>
                ))}
              </TableRow>
            );
          })
          ) : (
            <TableRow className="hover:bg-transparent">
              <TableCell
                colSpan={columns.length}
                className="h-24 text-center text-sm"
                style={{ color: 'var(--text-muted)' }}
              >
                {noDataMessage}
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
    </div>
  );
}
