# HOW TO CREATE A NEW CRUD MODULE

> Stack: PHP 8.5 · Laravel 12 · React 19 · Inertia 2.0 · TanStack Query v5 · TanStack Table v8

## Replacements

| Placeholder    | Example     |
| -------------- | ----------- |
| `{YourModule}` | `Products`  |
| `{YourEntity}` | `Product`   |
| `{YourId}`     | `ProductId` |
| `{yourModule}` | `products`  |
| `{yourEntity}` | `product`   |

---

## ── BACKEND (PHP / Laravel) ──────────────────────────────────────────────

**1.** Copy the `{YourModule}/` template folder and rename it with your module name.

**2.** Create `Domain/Entities/{YourEntity}.php`

- Extends `AggregateRoot`
- Pure domain logic — no Eloquent, no Laravel

**3.** Create `Domain/ValueObjects/{YourId}.php`

- `readonly` + `ramsey/uuid`

**4.** Create `Domain/Ports/{YourEntity}RepositoryPort.php`

- Interface only: `find()`, `save()`, `softDelete()`, `restore()`, `list()`

**5.** Create `Application/Commands/` handlers (write side)

- Inject the port directly — no Bus
- MUST dispatch Domain Events (`{YourEntity}Created`, etc.)
- MUST implement listeners to invalidate cache (`Cache::forget`)
- Extend `TransactionalHandler` if DB atomicity is needed

**6.** Create `Application/Queries/` handlers (read side)

- Eloquent is OK here directly
- MUST use `Cache::remember` with TTL
- Return `ReadModels`, not Domain Entities

**7.** Create `Infrastructure/Persistence/Eloquent/Models/{YourEntity}EloquentModel.php`

- ALWAYS add `@internal` docblock
- MUST use `SoftDeletes` + `timestamps`
- MUST use `Spatie\Activitylog\Traits\LogsActivity` and implement `getActivitylogOptions()`

**8.** Create `Infrastructure/Persistence/Mappers/{YourEntity}Mapper.php`

- This is the ONLY class allowed to import both the Domain Entity and the EloquentModel

**9.** Create `Infrastructure/Persistence/Repositories/Eloquent{YourEntity}Repository.php`

- Implements `{YourEntity}RepositoryPort`

**10.** Register the binding in `{YourModule}ServiceProvider.php`

```php
$this->app->bind(
    {YourEntity}RepositoryPort::class,
    Eloquent{YourEntity}Repository::class
);
```

**11.** Create `Infrastructure/Http/Controllers/Api/{YourEntity}Controller.php`

- Methods: `index`, `show`, `store`, `update`, `destroy`, `restore`, `bulkDelete`, `export`
- Orchestrator only — zero business logic

**12.** Create `Infrastructure/Http/Controllers/Web/{YourEntity}PageController.php`

- Returns `Inertia::render()`

**13.** Create `Infrastructure/Http/Requests/` + `Resources/`

**14.** Register routes in `Infrastructure/Routes/web.php`

```php
// Inertia pages
Route::get('/', [{Module}PageController::class, 'index'])->name('{module}.index');
Route::get('/create', [{Module}PageController::class, 'create'])->name('{module}.create');
Route::get('/{uuid}', [{Module}PageController::class, 'show'])->name('{module}.show')->whereUuid('uuid');
Route::get('/{uuid}/edit', [{Module}PageController::class, 'edit'])->name('{module}.edit')->whereUuid('uuid');

// JSON data endpoints (consumed by TanStack Query — web session auth)
Route::prefix('data')->group(function () {
    Route::middleware(['role:SUPER_ADMIN'])->prefix('admin')->group(function () {
        Route::get('/', [Admin{Module}Controller::class, 'index']);
        Route::post('/', [Admin{Module}Controller::class, 'store']);
        Route::get('/export', [{Module}ExportController::class, '__invoke']); // BEFORE /{uuid}
        Route::get('/{uuid}', [Admin{Module}Controller::class, 'show'])->whereUuid('uuid');
        Route::put('/{uuid}', [Admin{Module}Controller::class, 'update'])->whereUuid('uuid');
        Route::delete('/{uuid}', [Admin{Module}Controller::class, 'destroy'])->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [Admin{Module}Controller::class, 'restore'])->whereUuid('uuid');
        Route::post('/bulk-delete', [Admin{Module}Controller::class, 'bulkDelete']);
    });
});
```

> ⚠️ `/export` route MUST be registered BEFORE `/{uuid}` — otherwise Laravel matches "export" as a UUID.

- All UUID parameters must use `->whereUuid('uuid')`
- All routes must be inside the `auth` middleware group

**15.** Add permissions in the seeder

```
VIEW_{MODULE}
CREATE_{MODULE}
UPDATE_{MODULE}
DELETE_{MODULE}
Role: {ModuleName}
```

- Call `app(PermissionRegistrar::class)->forgetCachedPermissions()` BEFORE creating permissions
- Super Admin automatically receives all permissions

**16.** Create exports in `Infrastructure/Persistence/Export/`

- `{YourEntity}ExcelExport.php` — implement `FromQuery`, `WithHeadings`, `WithMapping`, `ShouldAutoSize`
- `{YourEntity}PdfExport.php` — use DomPDF with Blade template (see Export section below)
- Both MUST reuse the same `FilterDTO` used for listing
- Implement `{YourEntity}ExportController.php`

**17.** Add Swagger/OpenAPI annotations to the API Controller (see Swagger section below).

**18.** If the module requires a `user_id` relationship, add the corresponding `belongsTo` / `hasMany`

**19.** Write PEST tests in `Tests/`

- `Unit/Domain/` — domain invariants and business rules
- `Unit/Application/` — handlers with mocked repository
- `Integration/` — DB round-trip via Mapper
- `Feature/` — full HTTP CRUD + export

---

## ── FRONTEND (React 19 + TanStack) ──────────────────────────────────────

### Step A — Module hooks (`modules/{yourModule}/hooks/`)

#### `use{Entities}.ts` — paginated list

```ts
import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axios from 'axios';
import type { PaginatedResponse, {Entity}ListItem, {Entity}Filters } from '@/types/api';

export function use{Entities}(filters: {Entity}Filters) {
  return useQuery({
    queryKey: ['{entities}', filters],           // ✅ array key — namespace collision prevention
    queryFn: async () => {
      const { data } = await axios.get<PaginatedResponse<{Entity}ListItem>>(
        '/{module}/data/admin',
        { params: filters }
      );
      return data;
    },
    placeholderData: keepPreviousData,           // ✅ v5 — no blank state on page change
    staleTime: 1000 * 60 * 2,
  });
}
```

> **TanStack Query v5 rules (enforced):**
>
> - `isPending` — NOT `isLoading` (renamed in v5)
> - `placeholderData: keepPreviousData` — imported function (option `keepPreviousData` removed)
> - `gcTime` — NOT `cacheTime` (renamed in v5)
> - No `onSuccess`/`onError` on `useQuery` — use `useEffect` for side effects
> - `queryKey` as the first array element = entity name string, e.g. `['products', filters]`

#### `use{Entity}Mutations.ts` — CRUD mutations

```ts
import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

export function use{Entity}Mutations() {
  const queryClient = useQueryClient();

  const create{Entity} = useMutation({
    mutationFn: (payload: Record<string, unknown>) =>
      axios.post('/{module}/data/admin', payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['{entities}'] }),
  });

  const update{Entity} = useMutation({
    mutationFn: ({ uuid, payload }: { uuid: string; payload: Record<string, unknown> }) =>
      axios.put(`/{module}/data/admin/${uuid}`, payload),
    onSuccess: (_, { uuid }) => {
      queryClient.invalidateQueries({ queryKey: ['{entity}', uuid] });
      queryClient.invalidateQueries({ queryKey: ['{entities}'] });
    },
  });

  const delete{Entity} = useMutation({
    mutationFn: (uuid: string) => axios.delete(`/{module}/data/admin/${uuid}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['{entities}'] }),
  });

  const restore{Entity} = useMutation({
    mutationFn: (uuid: string) => axios.patch(`/{module}/data/admin/${uuid}/restore`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['{entities}'] }),
  });

  return { create{Entity}, update{Entity}, delete{Entity}, restore{Entity} };
}
```

---

### Step B — Table component (`pages/{yourModule}/components/{Entities}Table.tsx`)

> **TanStack Table v8 rules (enforced):**
>
> - `columnHelper` defined OUTSIDE the component (never inside — causes re-creation on every render)
> - `getRowId: (row) => row.uuid` — stable UUID-based IDs, required for optimistic updates
> - `useMemo` deps for `columns` must NOT include `columnHelper` (it's a module-level constant)
> - `createColumnHelper<T>()`, `useReactTable()`, `flexRender()` from `@tanstack/react-table`
> - Server-side pagination: pass `manualPagination: true` + `pageCount` when the backend controls it

```tsx
import * as React from 'react';
import {
  createColumnHelper,
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  flexRender,
  type ColumnDef,
  type RowSelectionState,
  type OnChangeFn,
  type SortingState,
} from '@tanstack/react-table';
import type { {Entity}ListItem } from '@/types/api';

// ── ✅ OUTSIDE component — never inside ──
const columnHelper = createColumnHelper<{Entity}ListItem>();

interface {Entities}TableProps {
  data: {Entity}ListItem[];
  isLoading: boolean;
  isError?: boolean;
  onDelete: (uuid: string, name: string) => void;
  rowSelection: RowSelectionState;
  onRowSelectionChange: OnChangeFn<RowSelectionState>;
}

export default function {Entities}Table({
  data, isLoading, isError = false,
  onDelete, rowSelection, onRowSelectionChange,
}: {Entities}TableProps) {
  const [sorting, setSorting] = React.useState<SortingState>([]);

  // ── Columns: deps do NOT include columnHelper (it's static) ──
  const columns = React.useMemo<ColumnDef<{Entity}ListItem, any>[]>(() => [
    columnHelper.display({
      id: 'select',
      header: ({ table }) => (
        <input type="checkbox"
          checked={table.getIsAllPageRowsSelected()}
          onChange={table.getToggleAllPageRowsSelectedHandler()}
        />
      ),
      cell: ({ row }) => (
        <input type="checkbox"
          checked={row.getIsSelected()}
          onChange={row.getToggleSelectedHandler()}
        />
      ),
    }),
    columnHelper.accessor('name', { header: 'Name', enableSorting: true }),
    // ... other columns
    columnHelper.display({
      id: 'actions',
      header: 'Actions',
      cell: ({ row }) => {
        const isDeleted = !!row.original.deleted_at;
        return (
          <div className="flex items-center gap-1.5">
            {/* Eye + Pencil/Trash2 or CheckCircle — see RULES-STYLES.md §8 */}
          </div>
        );
      },
    }),
  ], [onDelete]); // ✅ columnHelper NOT in deps

  const table = useReactTable({
    data,
    columns,
    getRowId: (row) => row.uuid,            // ✅ stable IDs for optimistic updates
    state: { rowSelection, sorting },
    onRowSelectionChange,
    onSortingChange: setSorting,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    enableRowSelection: true,
  });

  // ... render thead/tbody with flexRender()
}
```

---

### Step C — Index Page (`pages/{yourModule}/{Entities}IndexPage.tsx`)

> **React 19 patterns (enforced):**
>
> - `useTransition` — wrap search/filter/export state updates (non-blocking)
> - `useOptimistic` — instant feedback on delete; MUST be called inside `React.startTransition(async () => {...})`
> - `useRemember` (Inertia) — persists filters across back-navigation
> - Pagination: sliding window of 5 pages around current page

```tsx
import * as React from 'react';
import { Head, useRemember, router } from '@inertiajs/react';
import { useQueryClient } from '@tanstack/react-query';
import AppLayout from '@/pages/layouts/AppLayout';
import { use{Entities} } from '@/modules/{yourModule}/hooks/use{Entities}';
import { use{Entity}Mutations } from '@/modules/{yourModule}/hooks/use{Entity}Mutations';
import {Entities}Table from './components/{Entities}Table';

export default function {Entities}IndexPage(): React.JSX.Element {
  const [filters, setFilters] = useRemember<{Entity}Filters>({ page: 1, perPage: 15 }, '{yourModule}-filters');
  const [rowSelection, setRowSelection] = React.useState({});
  const [pendingDelete, setPendingDelete] = React.useState<{ uuid: string; name: string } | null>(null);

  // React 19: useTransition for non-blocking UI updates
  const [, startSearchTransition] = React.useTransition();
  const [isPendingExport, startExportTransition] = React.useTransition();

  const queryClient = useQueryClient();
  const { data, isPending, isError } = use{Entities}(filters);  // isPending, not isLoading ✅
  const items = data?.data ?? [];
  const meta = data?.meta ?? { currentPage: 1, lastPage: 1, perPage: 15, total: 0 };

  // React 19: useOptimistic — instant delete feedback
  const [optimisticItems, setOptimisticItems] = React.useOptimistic(
    items,
    (state: {Entity}ListItem[], deletedUuid: string) =>
      state.filter(i => i.uuid !== deletedUuid)
  );

  const { delete{Entity} } = use{Entity}Mutations();

  // ✅ useOptimistic INSIDE startTransition async
  async function handleConfirmDelete() {
    if (!pendingDelete) return;
    React.startTransition(async () => {
      setOptimisticItems(pendingDelete.uuid);
      try {
        await delete{Entity}.mutateAsync(pendingDelete.uuid);
        setPendingDelete(null);
      } catch { /* React auto-reverts optimistic state on error */ }
    });
  }

  // Search: non-blocking via useTransition
  function handleSearch(value: string) {
    startSearchTransition(() =>
      setFilters(p => ({ ...p, search: value || undefined, page: 1 }))
    );
  }

  // Export: non-blocking via useTransition
  function handleExport(format: 'excel' | 'pdf') {
    startExportTransition(() => {
      const params = new URLSearchParams({ format, ...filters as any });
      window.open(`/{module}/data/admin/export?${params}`, '_blank');
    });
  }

  // Bulk delete via Inertia router (CSRF handled automatically)
  const selectedUuids = Object.keys(rowSelection).filter(k => rowSelection[k as keyof typeof rowSelection]);
  function handleBulkDelete() {
    router.post('/{module}/data/admin/bulk-delete', { uuids: selectedUuids }, {
      onSuccess: () => {
        setRowSelection({});
        queryClient.invalidateQueries({ queryKey: ['{entities}'] });
      },
    });
  }

  // Sliding page window
  const pageWindow = React.useMemo(() => {
    const total = meta.lastPage, current = meta.currentPage;
    let start = Math.max(1, current - 2);
    const end = Math.min(total, start + 4);
    start = Math.max(1, end - 4);
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
  }, [meta.currentPage, meta.lastPage]);

  return (
    <>
      <Head title="{Entities}" />
      <AppLayout>
        {/* Header, filters, bulk actions, table, paginator, modals */}
      </AppLayout>
    </>
  );
}
```

---

### Step D — Create / Edit Pages (`{Entity}CreatePage.tsx` / `{Entity}EditPage.tsx`)

Standard pattern — no special notes beyond using `use{Entity}Mutations`.
For forms, prefer `Field` from shadcn/ui (installed Oct 2025) which wraps label + input + error.

---

## ── EXPORT: Excel + PDF ──────────────────────────────────────────────────

### ExportController

```php
// Infrastructure/Http/Controllers/Web/{Module}ExportController.php
declare(strict_types=1);

namespace Src\Modules\{Module}\Infrastructure\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Src\Modules\{Module}\Application\DTOs\{Entity}FilterDTO;
use Src\Modules\{Module}\Infrastructure\Persistence\Export\{Entity}ExcelExport;

final class {Module}ExportController
{
    public function __invoke(Request $request): mixed
    {
        $filters = {Entity}FilterDTO::from($request->all());
        $format  = $request->query('format', 'excel');

        if ($format === 'pdf') {
            return $this->exportPdf($filters);
        }

        return Excel::download(new {Entity}ExcelExport($filters), '{entities}.xlsx');
    }

    private function exportPdf({Entity}FilterDTO $filters): Response
    {
        // Query reuses the same FilterDTO — same data, different format
        $items = app({List{Entities}Handler}::class)->handle($filters, perPage: 9999);

        $pdf = Pdf::loadView('{module}::exports.pdf', [
            'items' => $items->data,
            'generatedAt' => now()->format('F j, Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('{entities}-export.pdf');
    }
}
```

### PDF Blade Template

```
Infrastructure/Views/{module}/exports/pdf.blade.php
```

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; }
        h1   { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #6a6a82; font-size: 10px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th    { background: #4f46e5; color: #fff; padding: 6px 10px; text-align: left; font-size: 10px; }
        td    { padding: 5px 10px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f8f8fc; }
        .footer { margin-top: 16px; font-size: 9px; color: #9a9ab0; text-align: right; }
    </style>
</head>
<body>
    <h1>{Module} Export</h1>
    <p class="meta">Generated: {{ $generatedAt }} — {{ count($items) }} records</p>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->email ?? '—' }}</td>
                <td>{{ $item->status }}</td>
                <td>{{ \Carbon\Carbon::parse($item->createdAt)->format('M j, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p class="footer">VIDULA · Confidential</p>
</body>
</html>
```

Register the Blade namespace in the ServiceProvider:

```php
$this->loadViewsFrom(__DIR__.'/../Infrastructure/Views', '{module}');
```

### ExcelExport

```php
// Infrastructure/Persistence/Export/{Entity}ExcelExport.php
declare(strict_types=1);

namespace Src\Modules\{Module}\Infrastructure\Persistence\Export;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

final class {Entity}ExcelExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly {Entity}FilterDTO $filters
    ) {}

    public function query(): Builder
    {
        return {Entity}EloquentModel::query()
            ->when($this->filters->search, fn($q) => $q->where('name', 'like', "%{$this->filters->search}%"))
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return ['Name', 'Email', 'Status', 'Created At'];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->email ?? '—',
            $row->status,
            $row->created_at?->format('M j, Y'),
        ];
    }
}
```

---

## ── SWAGGER / OPENAPI DOCUMENTATION ────────────────────────────────────

> Package: `darkaonline/l5-swagger` (wraps swagger-php)

### Installation (once per project)

```bash
composer require darkaonline/l5-swagger
./vendor/bin/sail artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

`config/l5-swagger.php` → set `generate_always => true` in local env.

### Base annotation (place ONCE in a shared controller or `app/Http/Controllers/Controller.php`)

```php
/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="VIDULA API",
 *     description="REST API — Sanctum token auth"
 * )
 * @OA\Server(url=L5_SWAGGER_CONST_HOST, description="API Server")
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
```

### Per-endpoint annotation (add to EVERY API controller method)

```php
/**
 * @OA\Get(
 *     path="/api/{module}/admin",
 *     summary="List {entities} (paginated)",
 *     tags={"{Module}"},
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="page",   in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Paginated list",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/{Entity}ListItem")),
 *             @OA\Property(property="meta", type="object")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
public function index(Request $request): JsonResponse { ... }

/**
 * @OA\Post(
 *     path="/api/{module}/admin",
 *     summary="Create {entity}",
 *     tags={"{Module}"},
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Create{Entity}DTO")),
 *     @OA\Response(response=201, description="Created"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
public function store(Create{Entity}Request $request): JsonResponse { ... }

/**
 * @OA\Delete(
 *     path="/api/{module}/admin/{uuid}",
 *     summary="Soft delete {entity}",
 *     tags={"{Module}"},
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
 *     @OA\Response(response=204, description="Deleted"),
 *     @OA\Response(response=404, description="Not found")
 * )
 */
public function destroy(string $uuid): JsonResponse { ... }
```

### Schema annotations (add to DTO or Resource classes)

```php
/**
 * @OA\Schema(
 *     schema="{Entity}ListItem",
 *     @OA\Property(property="uuid",       type="string", format="uuid"),
 *     @OA\Property(property="name",       type="string"),
 *     @OA\Property(property="email",      type="string", format="email", nullable=true),
 *     @OA\Property(property="status",     type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
```

### Generate docs

```bash
./vendor/bin/sail artisan l5-swagger:generate
```

UI available at: `http://localhost/api/documentation`

---

## ── CHECKLIST (copy per module) ─────────────────────────────────────────

### Backend

- [ ] `Domain/Entities/{YourEntity}.php` — extends `AggregateRoot`
- [ ] `Domain/ValueObjects/{YourId}.php` — `readonly` + uuid
- [ ] `Domain/Ports/{YourEntity}RepositoryPort.php`
- [ ] `Application/Commands/Create{YourEntity}/` + handler
- [ ] `Application/Commands/Update{YourEntity}/` + handler
- [ ] `Application/Commands/Delete{YourEntity}/` handler (soft delete)
- [ ] `Application/Commands/Restore{YourEntity}/` handler
- [ ] `Application/Queries/List{YourEntities}/` (paginated, cached)
- [ ] `Application/Queries/Get{YourEntity}/` (single, cached)
- [ ] DTOs: Create, Update, Filter (all extend `Spatie\LaravelData\Data`, no `readonly`)
- [ ] Domain events dispatched + cache invalidation listeners
- [ ] `{YourEntity}EloquentModel` — `@internal`, `SoftDeletes`, `LogsActivity`
- [ ] `{YourEntity}Mapper` — only class importing domain + Eloquent simultaneously
- [ ] `Eloquent{YourEntity}Repository` — implements port
- [ ] Web Controller (Inertia) + API Controller (JSON)
- [ ] Requests, Resources
- [ ] Routes: Inertia pages + `/data/admin/*` JSON endpoints (export BEFORE `/{uuid}`)
- [ ] ServiceProvider registered in `bootstrap/providers.php`
- [ ] Permissions seeder

### Export

- [ ] `{YourEntity}ExcelExport.php` — `FromQuery`, `WithHeadings`, `WithMapping`, `ShouldAutoSize`
- [ ] `{YourEntity}PdfExport.php` — DomPDF + Blade template with logo
- [ ] Both reuse same `FilterDTO`
- [ ] `ExportController` dispatches to Excel or PDF based on `?format=`
- [ ] Blade view namespace registered in ServiceProvider
- [ ] Route `/export` registered BEFORE `/{uuid}`

### Swagger

- [ ] Base `@OA\Info` + `@OA\Server` annotation (once per project, in base controller)
- [ ] `@OA\SecurityScheme(sanctum)` declared
- [ ] `@OA\Get`, `@OA\Post`, `@OA\Put`, `@OA\Delete`, `@OA\Patch` on every API method
- [ ] `@OA\Schema` on every DTO / Resource exposed in Swagger
- [ ] `l5-swagger:generate` runs after every API controller change
- [ ] UI accessible at `/api/documentation`

### Frontend

- [ ] `modules/{yourModule}/hooks/use{Entities}.ts` — queryKey `['{entities}', filters]`, `placeholderData: keepPreviousData`, `isPending`
- [ ] `modules/{yourModule}/hooks/use{Entity}Mutations.ts` — all mutations invalidate `['{entities}']`
- [ ] `pages/{yourModule}/components/{Entities}Table.tsx`:
    - [ ] `columnHelper` defined OUTSIDE the component
    - [ ] `getRowId: (row) => row.uuid`
    - [ ] `columnHelper` NOT in `useMemo` deps
- [ ] `{Entities}IndexPage.tsx`:
    - [ ] `useTransition` for search/filter/export
    - [ ] `useOptimistic` inside `React.startTransition(async () => {...})`
    - [ ] `useRemember` for filters (Inertia back-navigation)
    - [ ] Sliding page window (5 pages around current)
    - [ ] Bulk delete via `router.post` (CSRF handled by Inertia)
    - [ ] `DeleteConfirmModal` — no `window.confirm()`
    - [ ] `RestoreConfirmModal`
    - [ ] `ExportButton` (excel + pdf)
    - [ ] Status filter dropdown (All / Active / Deleted)
    - [ ] Total counter: `{meta.total} records`
- [ ] `types/api.ts` — `{Entity}ListItem`, `{Entity}Detail`, `{Entity}Filters` interfaces
- [ ] Sidebar nav item added with `PermissionGuard`

### Tests

- [ ] `Tests/Unit/Domain/` — domain invariants
- [ ] `Tests/Unit/Application/` — handlers with mocked repository
- [ ] `Tests/Integration/` — DB round-trip via Mapper
- [ ] `Tests/Feature/` — full HTTP CRUD + export (Excel + PDF)

---

## SoftDeletes Convention

Every CRUD module MUST include:

1. `SoftDeletes` trait on the EloquentModel (adds `deleted_at` column)
2. `softDeletes()` in the migration
3. `softDelete(string $uuid)` + `restore(string $uuid)` on the Repository Port
4. `DELETE` + `PATCH /restore` routes in both web and API route groups
5. The delete handler performs soft-delete (`->delete()`), never hard-delete

---

## Controller Convention

Controllers are **orchestrators ONLY** — they contain NO business logic. Inject CQRS handlers directly (no Bus).

```php
// Web Controller (Inertia)
return Inertia::render('module/PageName', [...]);

// API Controller
return response()->json([...]);
```

| Controller type | Required methods                                                                 |
| --------------- | -------------------------------------------------------------------------------- |
| API Controller  | `index`, `show`, `store`, `update`, `destroy`, `restore`, `bulkDelete`, `export` |
| Web Controller  | `index`, `create`, `show`, `edit`                                                |

---

## Eloquent Query Conventions

1. Always use `select()` — never `SELECT *`
2. Always use `paginate()` — never unbounded `get()` on listing queries
3. Use `when()` for conditional filters
4. Use `scopeInDateRange()` for date filtering
5. Query by `uuid` column, never by `id`, in all public-facing operations

---

## Layer Rules

| Layer          | Can import                            | Cannot import                      |
| -------------- | ------------------------------------- | ---------------------------------- |
| Domain         | Shared/Domain, own VOs, Enums         | Eloquent, Laravel, HTTP, Queues    |
| Application    | Domain, Shared/Application, DTOs      | Eloquent (`@internal` enforced)    |
| Infrastructure | Domain (Ports), Application, Eloquent | No restrictions — it's the adapter |

> The **Mapper** is the ONLY class in the entire codebase allowed to import both the Domain Entity and the EloquentModel simultaneously.

---

## Composer Packages → Architecture Mapping

### Core framework

| Package             | Maps to                                               |
| ------------------- | ----------------------------------------------------- |
| `laravel/framework` | All modules                                           |
| `laravel/sanctum`   | `Auth/Infrastructure` (HasApiTokens)                  |
| `laravel/fortify`   | `Auth/Application/Services/AuthenticationService.php` |

### Exports

| Package                   | Maps to                                         |
| ------------------------- | ----------------------------------------------- |
| `maatwebsite/excel`       | `Shared/Infrastructure/Export/ExcelAdapter.php` |
| `barryvdh/laravel-dompdf` | `Shared/Infrastructure/Export/PdfAdapter.php`   |
| `darkaonline/l5-swagger`  | API documentation — `config/l5-swagger.php`     |

### Domain / Application

| Package               | Maps to                                          |
| --------------------- | ------------------------------------------------ |
| `spatie/laravel-data` | `Shared/Application/DTOs/BaseDTO.php` + all DTOs |
| `ramsey/uuid`         | `Shared/Domain/ValueObjects/Uuid.php`            |

### Auth / Permissions

| Package                      | Maps to                         |
| ---------------------------- | ------------------------------- |
| `spatie/laravel-permission`  | `Auth/Application/Permissions/` |
| `spatie/laravel-activitylog` | `Shared/Infrastructure/Audit/`  |

### Testing

| Package                       | Maps to              |
| ----------------------------- | -------------------- |
| `pestphp/pest`                | All modules `Tests/` |
| `pestphp/pest-plugin-laravel` | Feature tests        |

---

## ── PHP 8.5 MODERN SYNTAX REFERENCE (AI GUIDE) ──────────────────────────

> **Stack version:** PHP 8.5 (released Nov 20, 2025). All code in this project MUST use PHP 8.5 syntax when applicable. This section tells the AI which modern features to reach for instead of older patterns.

---

### 1. Pipe Operator (`|>`) — USE IT for transformation chains

The `|>` operator passes the left expression as the **first argument** to the right callable. Use it instead of nested function calls.

```php
// ✅ PHP 8.5 — data transformation pipeline
$slug = $title
    |> trim(...)
    |> (fn(string $s): string => strtolower($s))
    |> (fn(string $s): string => str_replace(' ', '-', $s));

// ✅ All callable types are supported
$result = $value
    |> 'strtoupper'                              // string callable
    |> str_shuffle(...)                          // first-class callable
    |> fn($x) => trim($x)                        // arrow function
    |> new MyTransformer()                       // invokable object
    |> [MyClass::class, 'staticMethod']          // static method
    |> my_named_function(...);                   // named function

// ❌ Never write nested calls — use the pipe operator instead
$slug = strtolower(str_replace(' ', '-', trim($title)));
```

**When to use in this project:**

- Data sanitization chains in `Application/Commands/`
- DTO transformation in `Infrastructure/Http/Resources/`
- Export mapping in `Infrastructure/Persistence/Export/`
- Any sequential transformation of a single value

---

### 2. Property Hooks — USE for Value Objects and domain entities

Property hooks allow inline `get`/`set` behavior directly on properties. Use them in `Domain/ValueObjects/` to replace manual validation in `__construct()`.

```php
// ✅ PHP 8.5 — property hooks in a Value Object
final readonly class Email
{
    public function __construct(
        public string $value {
            get => strtolower($this->value);
            set {
                $normalized = strtolower(trim($value));
                if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Invalid email: {$value}");
                }
                $this->value = $normalized;
            }
        }
    ) {}
}

// ✅ Set-only hook (shorthand arrow form)
final readonly class SocialLinks
{
    public function __construct(
        public ?string $website {
            set => $this->website = $value !== null
                ? (filter_var($value, FILTER_VALIDATE_URL) ? $value : null)
                : null
        },
    ) {}
}

// ❌ Never do this in 8.5 — manual validation in constructor body is the old way
public function __construct(string $email)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { ... }
    $this->email = strtolower($email);
}
```

**Rules:**

- `get` hooks: use for computed/normalized read access
- `set` hooks: use for validation and normalization on write
- Only in `readonly` Value Objects and domain Entities (not in DTOs or Eloquent models)
- `readonly` + property hooks = immutable domain object with built-in invariants

---

### 3. Clone With — USE for immutable "wither" methods

`clone($obj, ['prop' => $newValue])` clones and modifies properties in one expression.

```php
// ✅ PHP 8.5 — clean wither pattern
readonly class Money
{
    public function __construct(
        public int $amount,
        public string $currency,
    ) {}

    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Currency mismatch');
        }
        return clone($this, ['amount' => $this->amount + $other->amount]);
    }
}

// ✅ Multiple properties at once
$updated = clone($response, [
    'statusCode' => 500,
    'body' => 'Internal Server Error',
]);

// ❌ Never use the old boilerplate approach in 8.5
public function withEmail(string $email): self
{
    $values = get_object_vars($this);
    $values['email'] = $email;
    return new self(...$values);
}
```

---

### 4. `#[\NoDiscard]` Attribute — USE on critical return-value methods

Emits a **compile-time warning** when the return value is silently discarded. Apply to sanitization, calculation, and transformation methods.

```php
// ✅ Decorate any method whose return value MUST be used
#[\NoDiscard]
public static function sanitize(array $input): array
{
    return $input
        |> self::trimStrings(...)
        |> self::normalizeUrls(...);
}

// ✅ Also useful on Command Handlers that return a result ID
#[\NoDiscard]
public function handle(CreateClientCommand $command): string
{
    // returns new UUID — must not be discarded
}
```

---

### 5. `array_first()` / `array_last()` — USE instead of manual idioms

```php
// ✅ PHP 8.5
$first = array_first($collection);  // null if empty
$last  = array_last($collection);   // null if empty

// ❌ Old PHP idioms — never use these in 8.5
$first = reset($arr); // has side effects
$last  = end($arr);   // has side effects
$first = $arr[array_key_first($arr)] ?? null;
```

---

### 6. Static Properties with Asymmetric Visibility

```php
// ✅ Public read, private write on static properties
class Config
{
    public private(set) static string $apiKey;

    public static function initialize(string $key): void
    {
        self::$apiKey = $key;  // ✅ OK inside class
    }
}

Config::initialize('secret');
echo Config::$apiKey;     // ✅ readable externally
Config::$apiKey = 'new';  // ❌ write error — enforced by PHP
```

---

### 7. `final` in Constructor Property Promotion

Use `final` on promoted properties in base/parent classes to prevent child classes from overriding them.

```php
// ✅ PHP 8.5
class AggregateRoot
{
    public function __construct(
        public final string $id,   // Cannot be overridden in child classes
    ) {}
}
```

---

### 8. Attribute Enhancements

Attributes can now be applied to **constants** and **traits**, and `#[\Override]` can be applied to **properties**.

```php
// ✅ Attribute on a constant
class Status
{
    #[\Deprecated('Use Status::ACTIVE instead')]
    public const OLD_ACTIVE = 'active';
    public const ACTIVE = 'ACTIVE';
}

// ✅ #[\Override] on a property
class ConcreteHandler extends AbstractHandler
{
    #[\Override]
    public string $handlerName = 'concrete';
}
```

---

### 9. URI Extension — USE instead of `parse_url()`

The native URI extension replaces the inconsistent `parse_url()` function.

```php
use Uri\Rfc3986\Uri;
use Uri\WhatWg\Url;

// ✅ RFC3986 — traditional web standard
$uri = Uri::fromString('https://example.com:443/path?q=1#section');
$scheme   = $uri->getScheme();    // "https"
$host     = $uri->getHost();      // "example.com"
$path     = $uri->getPath();      // "/path"
$query    = $uri->getQuery();     // "q=1"

// ✅ WHATWG — browser-standard (auto-normalizes paths)
$url = Url::fromString('https://example.com/api/../v2/users');
$pathname = $url->getPathname();  // "/v2/users" (normalized!)

// ✅ Validation via try/catch
function validateUrl(string $input): ?string
{
    try {
        return (new Url($input))->toString();
    } catch (\UriException) {
        return null;
    }
}

// ❌ Never use parse_url() in PHP 8.5 code
$parts = parse_url($url); // old, inconsistent, avoid
```

---

### 10. `FILTER_THROW_ON_FAILURE` — USE for strict validation

```php
// ✅ PHP 8.5 — throws instead of returning false
try {
    $email = filter_var($input, FILTER_VALIDATE_EMAIL, FILTER_THROW_ON_FAILURE);
} catch (\ValueError $e) {
    throw new \InvalidArgumentException('Invalid email', previous: $e);
}

// ❌ Old pattern (still works but verbose in 8.5)
$email = filter_var($input, FILTER_VALIDATE_EMAIL);
if ($email === false) {
    throw new \InvalidArgumentException('Invalid email');
}
```

---

### 11. `Closure::getCurrent()` — USE for clean anonymous recursion

```php
// ✅ PHP 8.5 — no variable capture needed for recursion
$factorial = function(int $n): int {
    return $n <= 1 ? 1 : $n * Closure::getCurrent()($n - 1);
};

// ❌ Old pattern — required awkward reference capture
$factorial = function(int $n) use (&$factorial): int {
    return $n <= 1 ? 1 : $n * $factorial($n - 1);
};
```

---

### 12. Static Closures & First-Class Callables in Constant Expressions

PHP 8.5 allows static closures and first-class callables in constant expressions — useful for compile-time configuration.

```php
// ✅ PHP 8.5 — callables in constant expressions
class Transformers
{
    const TRIM = trim(...);
    const UPPER = strtoupper(...);
    const PIPELINE = [trim(...), strtoupper(...)];
}
```

---

## PHP 8.5 Deprecations — NEVER Write These

The following patterns are **deprecated in PHP 8.5**. The AI must never emit them.

| Deprecated                         | ✅ Use Instead                      |
| ---------------------------------- | ----------------------------------- |
| `` `ls -la` `` (backtick operator) | `shell_exec('ls -la')`              |
| `(boolean)$x`                      | `(bool)$x`                          |
| `(integer)$x`                      | `(int)$x`                           |
| `(double)$x`                       | `(float)$x`                         |
| `array_key_exists(null, $arr)`     | Use `''` or explicit key check      |
| `disable_classes` INI setting      | Removed — do not use                |
| `curl_close($ch)`                  | Remove call — no-op since 8.0       |
| `class_alias('array', ...)`        | Not allowed in 8.5                  |
| `socket_set_timeout()`             | `stream_set_timeout()`              |
| `__sleep()` / `__wakeup()`         | `__serialize()` / `__unserialize()` |

---

## Quick Decision Table — Which PHP 8.5 Feature?

| Situation                            | Feature to use                        |
| ------------------------------------ | ------------------------------------- |
| Sequential data transformation       | Pipe operator `\|>`                   |
| Property validation in Value Objects | Property hooks (`set { ... }`)        |
| Computed derived property            | Property hook (`get =>`)              |
| Readonly object "wither" method      | `clone($obj, ['prop' => $v])`         |
| Result must not be silently ignored  | `#[\NoDiscard]` attribute             |
| Get first/last element of collection | `array_first()` / `array_last()`      |
| Parse or validate a URL              | `Uri\Rfc3986\Uri` or `Uri\WhatWg\Url` |
| Strict filter validation that throws | `FILTER_THROW_ON_FAILURE`             |
| Anonymous recursive closure          | `Closure::getCurrent()`               |
| Immutable config locked at read      | `public private(set) static`          |
