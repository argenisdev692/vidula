import {
  useReactTable,
  getCoreRowModel,
  flexRender,
  type ColumnDef,
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

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  isLoading?: boolean;
  isError?: boolean;
  noDataMessage?: string;
  errorMessage?: string;
  loadingMessage?: string;
  
  // Optional Row Selection
  rowSelection?: RowSelectionState;
  onRowSelectionChange?: OnChangeFn<RowSelectionState>;
}

export function DataTable<TData, TValue>({
  columns,
  data,
  isLoading = false,
  isError = false,
  noDataMessage = 'No results.',
  errorMessage = 'Failed to load data. Please try again.',
  loadingMessage = 'Loading...',
  rowSelection,
  onRowSelectionChange,
}: DataTableProps<TData, TValue>) {
  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    enableRowSelection: rowSelection !== undefined,
    onRowSelectionChange,
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
          ) : isLoading ? (
            <TableRow className="hover:bg-transparent">
              <TableCell
                colSpan={columns.length}
                className="h-24 text-center text-sm"
                style={{ color: 'var(--text-muted)' }}
              >
                {loadingMessage}
              </TableCell>
            </TableRow>
          ) : table.getRowModel().rows?.length ? (
            table.getRowModel().rows.map((row) => {
              // Soft-delete visual indicator
              // Check if original data has `deletedAt` or `deleted_at` field and it is truthy
              const orig = row.original as any;
              const isDeleted = Boolean(orig?.deletedAt || orig?.deleted_at);
              
              return (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && 'selected'}
                  className="transition-colors duration-150 group text-center"
                  style={{
                    borderBottom: '1px solid var(--border-subtle)',
                    ...(isDeleted && {
                      background: 'color-mix(in srgb, var(--accent-error) 6%, transparent)',
                      opacity: 0.65,
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
