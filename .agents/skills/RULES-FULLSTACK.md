# RULES-FULLSTACK.md — PHP 8.5 + Laravel 12 + React 19 + Inertia 2.0 (2026)

---

## 0. Assistant Behavior

- **Role**: Senior Software Architect — PHP 8.5, Laravel 12, React 19, Inertia.js 2.0.
- **Language**: Always respond in **English**. Technical terms use standard industry naming.
- **Mandatory MCP (use Context7)**: Always resolve live documentation for the following — never rely on cached training knowledge:
    - `laravel/laravel` → Laravel 12 (released Feb 24, 2025)
    - `inertiajs/inertia` → Inertia.js 2.0
    - `facebook/react` → React 19
    - `php/php-src` → PHP 8.5 (released Nov 20, 2025)
    - `spatie/laravel-data` → latest
    - `pestphp/pest` → latest
    - `@tanstack/react-query` → v5 (current: ~5.90.x)
    - `@tanstack/react-table` → v8 (latest)
- **Use Tavily** whenever:
    - The user writes **"investigate"** or **"investigar"** — trigger Tavily immediately before responding.
    - A UI component may not exist in shadcn/ui — search before building custom.
    - An external library API needs version verification.
    - PHP 8.5 / Laravel 12 nuances require current confirmation.
- **shadcn/ui component styling**: Every time a shadcn/ui component is created or customized, use Tavily to search for current modern styles, hover patterns, and interaction states (`site:ui.shadcn.com` + `shadcn modern hover styles 2025`). All results must be adapted to the project's CSS token system — never apply hardcoded colors from examples.
- **UI/UX**: Before generating any React/Inertia component, apply `RULES-STYLES.md`. Never hardcode colors; always use CSS tokens.
- **Style**: Concise, technical, pragmatic. Proactively correct code violating these rules.

---

## 1. Verified Stack Versions (March 2026)

| Technology | Version | Notes |
|---|---|---|
| PHP | **8.5** | Released Nov 20, 2025. Pipe operator, URI extension, clone-with, `array_first`/`array_last` |
| Laravel | **12** | Released Feb 24, 2025. Zero breaking changes. `bootstrap/providers.php` (not `config/app.php`) |
| Inertia.js | **2.0** | Deferred props, Link prefetching, `router.*` API (deprecated `Inertia.visit()`) |
| React | **19** | Actions, `useTransition`, `useOptimistic`, `use()`, `useFormStatus` |
| TypeScript | **5.x** | Strict mode required |
| TanStack Query | **v5** (5.90+) | `isPending` (not `isLoading`), `placeholderData: keepPreviousData`, single-object API |
| TanStack Table | **v8** | Server-side pagination, `useReactTable` |
| shadcn/ui | **latest (Dec 2025)** | Radix UI default; Base UI available. New: Spinner, Field, InputGroup, ButtonGroup, Empty, Item, Kbd |
| Tailwind CSS | **v4** | CSS-first config, `@import "tailwindcss"` |
| Vite | **6.x** | `vite.config.ts` |
| Spatie Permission | **6.x** | `bootstrap/app.php` middleware aliases |
| Spatie Laravel Data | **4.x** | No `readonly` on classes extending `Data` |
| Pest | **3.x** | `arch()` tests, `describe()` blocks |

---

## 2. PHP 8.5 Hard Rules

> PHP 8.5 was released **November 20, 2025**. All features below are stable and production-ready.

### Pipe operator (`|>`)

Always use for chained transformations in services, mappers, and pipes. The right-hand side MUST be a single-parameter callable. Arrow functions in pipes MUST be wrapped in parentheses:

```php
// ✅ Correct
$result = $value
    |> trim(...)
    |> (fn(string $s) => str_replace(' ', '-', $s))
    |> strtolower(...);

// ❌ Wrong — arrow function without parentheses
$result = $value |> fn($s) => strtolower($s);
```

### Clone with (immutability pattern)

```php
// Wither pattern for readonly classes
$updated = clone($entity, ['status' => 'active', 'updatedAt' => now()->toIso8601String()]);
```

### Immutability via `readonly` + `clone`

- `readonly class` + `clone($obj, ['prop' => $val])` for Value Objects and Domain Events.
- `AggregateRoot` and Domain Entities: **no `readonly`** (need mutable event arrays).
- DTOs extending `Spatie\LaravelData\Data`: **no `readonly`** (parent is not readonly).

### Other PHP 8.5 mandatory usage

- `#[\NoDiscard]` — apply on Domain Services/Specifications where ignoring the return is a bug.
- `#[\Override]` — apply to explicitly typed properties inheriting from a parent.
- `array_first()` / `array_last()` — prefer over `reset()`/`end()`. Both return `null` on empty arrays.
- `Uri\Rfc3986\Uri` / `Uri\WhatWg\Url` — use built-in URI extension in `ValueObjects/Url.php`.
- `declare(strict_types=1);` — **every file**, no exceptions.
- Every method must have an explicit return type.
- `Closure` in constant expressions — use for DTO validation rules with `spatie/laravel-data`.

---

## 3. Readonly Classes — Correct Usage

| Class type | Use `readonly`? | Reason |
|---|---|---|
| Value Objects | ✅ Yes | Immutable by nature |
| Domain Events | ✅ Yes | Immutable by nature |
| Standalone entities (no parent) | ✅ Yes | Immutable by nature |
| DTOs extending `Spatie\LaravelData\Data` | ❌ No | Parent is not readonly |
| Domain Entities extending `AggregateRoot` | ❌ No | Parent is not readonly; needs mutable event array |
| `AggregateRoot` itself | ❌ No | Needs mutable state for domain events |
| Classes with default property values | ❌ No | PHP restriction |

```bash
# Verify no readonly violations exist
grep -r "readonly class.*extends Data" src/
grep -r "readonly class.*extends AggregateRoot" src/
grep -r "readonly.*=.*;" src/
# All must return 0 results
```

---

## 4. Laravel 12 Rules

### Service Provider registration

```php
// bootstrap/providers.php (NOT config/app.php in Laravel 12)
return [
    App\Providers\AppServiceProvider::class,
    Src\Providers\SharedServiceProvider::class,
    Src\Modules\Users\Providers\UsersServiceProvider::class,
    Src\Modules\Auth\Providers\AuthServiceProvider::class,
    // ... all module providers
];
```

### Port bindings — auto-complete every module

Every time a backend module is generated, automatically finalize the `{Module}ServiceProvider.php`:

```php
public function register(): void
{
    $this->app->bind(
        UserRepositoryPort::class,
        EloquentUserRepository::class
    );
    // Bind ALL ports in this module — never leave an unbound port
}
```

### Spatie Permission middleware (bootstrap/app.php)

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

### Database

- **Universal timestamps**: every entity and Eloquent schema MUST have `created_at`, `updated_at`, `deleted_at`.
- **No hard deletes**: `deleted_at` is the universal soft-delete marker. Never use hard `delete()` unless legally required.
- **Default filtering**: all repository read queries filter `deleted_at IS NULL`. Use `withTrashed()` to opt-in.
- `SoftDeletes` trait on every `*EloquentModel.php`.
- Always use `select()` — never `SELECT *`.
- Always use `paginate()` — never unbounded `get()` on listing queries.
- Query by `uuid` column, never by `id`, in all public-facing operations.
- Use `when()` for conditional filters.

### EloquentModel template

```php
declare(strict_types=1);

namespace Src\Modules\{Module}\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** @internal */
final class {Module}EloquentModel extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = '{table_name}';

    protected $fillable = [/* fields */];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['field_1', 'field_2', 'status'])  // Never log passwords/tokens
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('{context}.{module}');
    }
}
```

---

## 5. Architecture Rules

### Layer imports (enforced)

| Layer | Can import | Cannot import |
|---|---|---|
| Domain | Shared/Domain, own VOs, Enums | Eloquent, Laravel, HTTP, Queues |
| Application | Domain, Shared/Application, DTOs | Eloquent (`@internal` enforced) |
| Infrastructure | Domain (Ports), Application, Eloquent | No restrictions — it's the adapter |

> The **Mapper** is the ONLY class allowed to import both the Domain Entity and the EloquentModel simultaneously.

### Date handling (critical)

Domain entities store dates as ISO 8601 strings, NOT Carbon instances.

```php
// Mapper (Carbon → string)
createdAt: $model->created_at?->toIso8601String(),

// Query Handler (string → ReadModel, no conversion)
createdAt: $user->createdAt ?? '',   // ✅ Already a string

// ❌ Wrong — trying to call Carbon method on string
createdAt: $user->created_at?->toISOString() ?? '',
```

### Property naming

- **Eloquent Model**: `snake_case` (`created_at`, `profile_photo_path`)
- **Domain Entity**: `camelCase` (`createdAt`, `profilePhotoPath`)
- **ReadModel/DTO**: `camelCase` (`createdAt`, `profilePhotoPath`)
- **Frontend**: receives camelCase from JSON (mirrors DTO field names exactly)

### CQRS

- **Command handlers**: mutate state, dispatch domain events, return `void` or domain events.
- **Query handlers**: read-only, return `ReadModel`s, use `Cache::remember`.
- Inject handlers directly — no Bus in intermediate architecture.

### Cache management

```php
// List query — use cache tags with fallback
try {
    return Cache::tags(['{module}_list'])->remember($cacheKey, $ttl, fn() => $this->fetchData());
} catch (\Exception $e) {
    return Cache::remember($cacheKey, $ttl, fn() => $this->fetchData());
}

// Mutation handler — clear cache
Cache::forget("{module}_{$uuid}");
try {
    Cache::tags(['{module}_list'])->flush();
} catch (\Exception $e) { /* tags not supported, expires naturally */ }
```

---

## 6. CRUD Module Checklist

Every new CRUD module must include ALL of the following:

**Domain Layer**
- [ ] `Domain/Entities/{YourEntity}.php` — extends `AggregateRoot`, no Eloquent, no Laravel
- [ ] `Domain/ValueObjects/{YourId}.php` — `readonly` + `ramsey/uuid`
- [ ] `Domain/Ports/{YourEntity}RepositoryPort.php` — `find()`, `save()`, `softDelete()`, `restore()`, `list()`

**Application Layer**
- [ ] `Application/Commands/Create{YourEntity}/` handler + command
- [ ] `Application/Commands/Update{YourEntity}/` handler + command
- [ ] `Application/Commands/Delete{YourEntity}/` handler — soft delete only
- [ ] `Application/Commands/Restore{YourEntity}/` handler
- [ ] `Application/Queries/List{YourEntities}/` handler — returns paginated `ReadModel`, uses Cache
- [ ] `Application/Queries/Get{YourEntity}/` handler — returns single `ReadModel`, uses Cache
- [ ] `Application/DTOs/Create{YourEntity}DTO.php` — extends `Spatie\LaravelData\Data`, no `readonly`
- [ ] `Application/DTOs/Update{YourEntity}DTO.php` — extends `Spatie\LaravelData\Data`, no `readonly`
- [ ] `Application/DTOs/{YourEntity}FilterDTO.php` — extends `Spatie\LaravelData\Data`, no `readonly`
- [ ] `Application/Queries/ReadModels/{YourEntity}ListReadModel.php` — no `readonly`
- [ ] `Application/Queries/ReadModels/{YourEntity}ReadModel.php` — no `readonly`
- [ ] Domain events dispatched: `{YourEntity}Created`, `{YourEntity}Updated`, `{YourEntity}Deleted`, `{YourEntity}Restored`
- [ ] Cache invalidation listener on each domain event

**Infrastructure Layer**
- [ ] `Infrastructure/Persistence/Eloquent/Models/{YourEntity}EloquentModel.php` — `@internal`, `SoftDeletes`, `LogsActivity`
- [ ] `Infrastructure/Persistence/Mappers/{YourEntity}Mapper.php` — ONLY class importing both domain + Eloquent
- [ ] `Infrastructure/Persistence/Repositories/Eloquent{YourEntity}Repository.php` — implements port
- [ ] `Infrastructure/Http/Controllers/Api/{YourEntity}Controller.php` — orchestrator ONLY, no business logic
- [ ] `Infrastructure/Http/Controllers/Web/{YourEntity}PageController.php` — returns `Inertia::render()`
- [ ] `Infrastructure/Http/Requests/Create{YourEntity}Request.php`
- [ ] `Infrastructure/Http/Requests/Update{YourEntity}Request.php`
- [ ] `Infrastructure/Http/Resources/{YourEntity}Resource.php`
- [ ] `Infrastructure/Routes/api.php`
- [ ] `Infrastructure/Routes/web.php`
- [ ] `{YourModule}ServiceProvider.php` — all port bindings registered

**Permissions / Seeder**
- [ ] `VIEW_{MODULE}`, `CREATE_{MODULE}`, `UPDATE_{MODULE}`, `DELETE_{MODULE}` permissions
- [ ] Call `app(PermissionRegistrar::class)->forgetCachedPermissions()` BEFORE creating permissions
- [ ] Super Admin receives all permissions

**SoftDeletes Convention**
- [ ] `SoftDeletes` trait on EloquentModel
- [ ] `softDeletes()` in migration
- [ ] `softDelete()` + `restore()` on Repository Port and implementation
- [ ] `DELETE` + `PATCH /{uuid}/restore` routes in both web and API route groups
- [ ] Delete handler performs soft-delete (`.delete()`), NEVER hard delete

**Export**
- [ ] `Infrastructure/Persistence/Export/{YourEntity}ExcelExport.php` — `FromQuery`, `WithHeadings`, `WithMapping`, `ShouldAutoSize`
- [ ] `Infrastructure/Persistence/Export/{YourEntity}PdfExport.php` — stylized template with logo
- [ ] Both exports reuse the same `FilterDTO`

**Tests**
- [ ] `Tests/Unit/Domain/` — domain invariants
- [ ] `Tests/Unit/Application/` — handlers with mocked repository
- [ ] `Tests/Integration/` — DB round-trip via Mapper
- [ ] `Tests/Feature/` — full HTTP CRUD + export

**Sidebar Navigation — mandatory last step**
- [ ] Add a navigation item in `AppLayout.tsx` (or sidebar component) linking to `/{module}` with a `lucide-react` icon at 18px
- [ ] Use `<Link href="/{module}">` from `@inertiajs/react` — never a native `<a>` tag
- [ ] Active state detected from current URL, styled with the `.sidebar-item--active` token class
- [ ] Wrap with `<PermissionGuard permission="VIEW_{MODULE}">` — only visible to authorized users

---

## 7. Routes Convention

### Web routes (Inertia + session auth)

```php
// Infrastructure/Routes/web.php

// Inertia pages
Route::middleware(['auth'])->group(function () {
    Route::get('/{module}', [{Module}PageController::class, 'index']);
    Route::get('/{module}/create', [{Module}PageController::class, 'create']);
    Route::get('/{module}/{uuid}', [{Module}PageController::class, 'show'])
        ->whereUuid('uuid');
    Route::get('/{module}/{uuid}/edit', [{Module}PageController::class, 'edit'])
        ->whereUuid('uuid');
    Route::delete('/{module}/{uuid}', [{Module}PageController::class, 'destroy'])
        ->whereUuid('uuid');
    Route::patch('/{module}/{uuid}/restore', [{Module}PageController::class, 'restore'])
        ->whereUuid('uuid');
});

// JSON data endpoints (used by TanStack Query — web session auth)
Route::middleware(['auth', 'role:SUPER_ADMIN'])
    ->prefix('/{module}/data/admin')
    ->group(function () {
        Route::get('/', [{Module}Controller::class, 'index']);
        Route::post('/', [{Module}Controller::class, 'store']);
        Route::get('/{uuid}', [{Module}Controller::class, 'show'])->whereUuid('uuid');
        Route::put('/{uuid}', [{Module}Controller::class, 'update'])->whereUuid('uuid');
        Route::delete('/{uuid}', [{Module}Controller::class, 'destroy'])->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [{Module}Controller::class, 'restore'])->whereUuid('uuid');
        Route::get('/export', [{Module}Controller::class, 'export']);
    });
```

### API routes (Sanctum — mobile / external)

```php
// Infrastructure/Routes/api.php

Route::middleware(['auth:sanctum', 'role:super-admin'])
    ->prefix('/api/{module}/admin')
    ->group(function () {
        Route::get('/', [{Module}Controller::class, 'index']);
        Route::post('/', [{Module}Controller::class, 'store']);
        Route::get('/{uuid}', [{Module}Controller::class, 'show'])->whereUuid('uuid');
        Route::put('/{uuid}', [{Module}Controller::class, 'update'])->whereUuid('uuid');
        Route::delete('/{uuid}', [{Module}Controller::class, 'destroy'])->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [{Module}Controller::class, 'restore'])->whereUuid('uuid');
    });
```

### Route table reference

| Method | Web JSON endpoint | API endpoint | Purpose |
|---|---|---|---|
| GET | `/{module}/data/admin` | `/api/{module}/admin` | List (paginated) |
| POST | `/{module}/data/admin` | `/api/{module}/admin` | Create |
| GET | `/{module}/data/admin/{uuid}` | `/api/{module}/admin/{uuid}` | Show one |
| PUT | `/{module}/data/admin/{uuid}` | `/api/{module}/admin/{uuid}` | Update |
| DELETE | `/{module}/data/admin/{uuid}` | `/api/{module}/admin/{uuid}` | Soft delete |
| PATCH | `/{module}/data/admin/{uuid}/restore` | `/api/{module}/admin/{uuid}/restore` | Restore |
| GET | `/{module}/data/admin/export` | — | Export (Excel/PDF) |

### Key distinctions

| Aspect | Web routes | API routes |
|---|---|---|
| Auth | Session (cookies) | Sanctum token (Bearer) |
| Middleware | `web`, `auth` | `api`, `auth:sanctum` |
| Use | Browser frontend (Inertia/React Query) | Mobile apps, external APIs |
| CSRF | Required (Inertia handles automatically) | Not required |

**Never call `/api/*` routes from Inertia pages. Never use session auth on API routes.**

---

## 8. Inertia 2.0 Rules

### Navigation

```tsx
// ✅ Always use Link for internal navigation
import { Link, router } from "@inertiajs/react";

<Link href="/users" prefetch>Users</Link>
<Link href="/users/create">New User</Link>

// ✅ Programmatic navigation
router.visit("/users");
router.get("/users", {}, { preserveState: true, preserveScroll: true });
router.post("/users/data/admin", data);
router.patch(`/users/data/admin/${uuid}/restore`);
router.delete(`/users/data/admin/${uuid}`);

// ❌ Deprecated in v2
Inertia.visit("/users");
```

### Page component pattern

```tsx
// pages/users/UsersIndexPage.tsx
import { Head, usePage } from "@inertiajs/react";
import { AppLayout } from "@/pages/layouts/AppLayout";
import type { UsersIndexPageProps } from "@/types/api";

export default function UsersIndexPage(): React.JSX.Element {
    const { filters } = usePage<UsersIndexPageProps>().props;

    return (
        <>
            <Head title="Users" />
            <AppLayout>
                {/* ... */}
            </AppLayout>
        </>
    );
}
```

**Rules:**
- Always `export default` — Inertia requires it.
- Always `<Head title="..." />`.
- Explicit return type `React.JSX.Element`.
- Always typed via `usePage<PagePropsInterface>()`.

### Deferred Props (v2 new)

```tsx
// Backend
return Inertia::render('Users/Show', [
    'user' => $user,
    'history' => Inertia::defer(fn() => $this->loadHeavyHistory()),
]);

// Frontend
<Suspense fallback={<Spinner />}>
    <UserHistory history={history} />
</Suspense>
```

### CSRF

Inertia 2.0 automatically includes `X-XSRF-TOKEN` on all requests. **Do NOT implement manual CSRF logic.**

---

## 9. TanStack Query v5 Patterns

### Installation

```bash
npm install @tanstack/react-query @tanstack/react-query-devtools
```

### List hook (paginated)

```ts
// modules/{context}/hooks/use{Entities}.ts
import { useQuery, keepPreviousData } from "@tanstack/react-query";
import type { PaginatedResponse, {Entity}ListItem, {Entity}Filters } from "@/types/api";

async function fetch{Entities}(filters: {Entity}Filters): Promise<PaginatedResponse<{Entity}ListItem>> {
    const params = new URLSearchParams(filters as Record<string, string>);
    const response = await fetch(`/{module}/data/admin?${params}`);
    if (!response.ok) throw new Error("Failed to fetch {entities}");
    return response.json();
}

export function use{Entities}(filters: {Entity}Filters) {
    return useQuery<PaginatedResponse<{Entity}ListItem>, Error>({
        queryKey: ["{entities}", "list", filters],
        queryFn: () => fetch{Entities}(filters),
        placeholderData: keepPreviousData, // ✅ v5 — prevents blank state on page change
        staleTime: 1000 * 60 * 2,
    });
}
```

### Mutation hook

```ts
// modules/{context}/hooks/use{Entity}Mutations.ts
import { useMutation, useQueryClient } from "@tanstack/react-query";

export function useDelete{Entity}() {
    const queryClient = useQueryClient();

    return useMutation<void, Error, string>({
        mutationFn: async (uuid: string) => {
            const response = await fetch(`/{module}/data/admin/${uuid}`, { method: "DELETE" });
            if (!response.ok) throw new Error("Failed to delete");
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["{entities}"] });
        },
    });
}

export function useRestore{Entity}() {
    const queryClient = useQueryClient();

    return useMutation<void, Error, string>({
        mutationFn: async (uuid: string) => {
            const response = await fetch(`/{module}/data/admin/${uuid}/restore`, { method: "PATCH" });
            if (!response.ok) throw new Error("Failed to restore");
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["{entities}"] });
        },
    });
}
```

### v5 Breaking changes to remember

- `isLoading` renamed to `isPending` for queries and mutations.
- `cacheTime` renamed to `gcTime`.
- `keepPreviousData` option removed — use `placeholderData: keepPreviousData` (imported function).
- `onError`, `onSuccess`, `onSettled` removed from `useQuery` — use `useEffect` for side effects.
- Single-object API only — no positional arguments overloads.
- `context` option removed — pass `queryClient` directly.

---

## 10. TanStack Table v8 — Server-Side Pattern

```tsx
// pages/{module}/components/{Entities}Table.tsx
import { useMemo } from "react";
import { getCoreRowModel, type ColumnDef } from "@tanstack/react-table";
import { Eye, Pencil, Trash2, CheckCircle } from "lucide-react";
import { Link } from "@inertiajs/react";
import type { {Entity}ListItem } from "@/types/api";

export function {Entities}Table({ data, isLoading, onDeleteClick, onRestoreClick }) {
    const columns = useMemo<ColumnDef<{Entity}ListItem>[]>(() => [
        { accessorKey: "name", header: "Name" },
        { accessorKey: "email", header: "Email" },
        {
            id: "actions",
            header: "Actions",
            cell: ({ row }) => {
                const item = row.original;
                const isDeleted = !!item.deleted_at;
                return (
                    <div style={{ display: "flex", alignItems: "center", gap: "6px" }}>
                        <Link href={`/{module}/${item.uuid}`}>
                            <Eye size={14} />
                        </Link>
                        {!isDeleted ? (
                            <>
                                <Link href={`/{module}/${item.uuid}/edit`}>
                                    <Pencil size={14} />
                                </Link>
                                <button onClick={() => onDeleteClick(item.uuid, item.name)}>
                                    <Trash2 size={14} />
                                </button>
                            </>
                        ) : (
                            <button onClick={() => onRestoreClick(item.uuid, item.name)}>
                                <CheckCircle size={14} />
                            </button>
                        )}
                    </div>
                );
            },
        },
    ], [onDeleteClick, onRestoreClick]);

    return <DataTable columns={columns} data={data} isLoading={isLoading} />;
}
```

---

## 11. Index Page Full Pattern

Every `{Entities}IndexPage.tsx` must include ALL of:

- Total records counter: `{data?.meta.total ?? 0} total records`
- Search filter
- Status filter
- Date range filter (`DataTableDateRangeFilter`)
- Export button (`ExportButton`)
- `DeleteConfirmModal` — for soft deletes (never `window.confirm()`)
- `RestoreConfirmModal` — for restoring soft-deleted records
- Table with 3 action icons (View / Edit / Delete for active rows; View / Restore for deleted rows)
- Soft-deleted rows with red-tinted background + reduced opacity (via CSS tokens, not hardcoded colors)

```tsx
// pages/{module}/{Entities}IndexPage.tsx
export default function {Entities}IndexPage(): React.JSX.Element {
    const [filters, setFilters] = useRemember<{Entity}Filters>({}, "{entity}-filters");
    const [pendingDelete, setPendingDelete] = useState<{ uuid: string; name: string } | null>(null);
    const [pendingRestore, setPendingRestore] = useState<{ uuid: string; name: string } | null>(null);
    const [, startTransition] = useTransition();

    const { data, isPending } = use{Entities}(filters);
    const { delete{Entity}, restore{Entity} } = use{Entity}Mutations();

    return (
        <>
            <Head title="{Entities}" />
            <AppLayout>
                {/* Header with total count */}
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                    <h1>{Entities}</h1>
                    <p style={{ color: "var(--text-muted)", fontSize: "14px" }}>
                        {data?.meta.total ?? 0} total records
                    </p>
                </div>

                {/* Filters + Export toolbar */}
                <div style={{ display: "flex", gap: "12px", alignItems: "center" }}>
                    <input
                        placeholder="Search..."
                        value={filters.search ?? ""}
                        onChange={e => startTransition(() => setFilters(f => ({ ...f, search: e.target.value, page: 1 })))}
                        style={{ background: "var(--input-bg)", color: "var(--input-text)", border: "1px solid var(--input-border)" }}
                    />
                    <DataTableDateRangeFilter
                        dateFrom={filters.dateFrom}
                        dateTo={filters.dateTo}
                        onChange={({ dateFrom, dateTo }) =>
                            startTransition(() => setFilters(f => ({ ...f, dateFrom, dateTo, page: 1 })))
                        }
                    />
                    <ExportButton endpoint="/{module}/data/admin/export" filters={filters} />
                    <Link href="/{module}/create">New {Entity}</Link>
                </div>

                {/* Table */}
                <{Entities}Table
                    data={data?.data ?? []}
                    isLoading={isPending}
                    onDeleteClick={(uuid, name) => setPendingDelete({ uuid, name })}
                    onRestoreClick={(uuid, name) => setPendingRestore({ uuid, name })}
                />

                {/* Pagination */}
                <DataTablePagination
                    currentPage={data?.meta.currentPage ?? 1}
                    lastPage={data?.meta.lastPage ?? 1}
                    onPageChange={page => startTransition(() => setFilters(f => ({ ...f, page })))}
                />

                {/* Delete modal */}
                <DeleteConfirmModal
                    isOpen={!!pendingDelete}
                    entityLabel="{entity}"
                    entityName={pendingDelete?.name}
                    onConfirm={() => {
                        if (pendingDelete) delete{Entity}.mutate(pendingDelete.uuid);
                        setPendingDelete(null);
                    }}
                    onCancel={() => setPendingDelete(null)}
                    isPending={delete{Entity}.isPending}
                />

                {/* Restore modal */}
                <RestoreConfirmModal
                    isOpen={!!pendingRestore}
                    entityLabel="{entity}"
                    entityName={pendingRestore?.name}
                    onConfirm={() => {
                        if (pendingRestore) restore{Entity}.mutate(pendingRestore.uuid);
                        setPendingRestore(null);
                    }}
                    onCancel={() => setPendingRestore(null)}
                    isPending={restore{Entity}.isPending}
                />
            </AppLayout>
        </>
    );
}
```

---

## 12. TypeScript Contracts

```ts
// types/api.ts — mirrors backend DTO field names exactly

export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
    };
}

// Every entity list item must include deleted_at for soft-delete styling
export interface {Entity}ListItem {
    uuid: string;
    name: string;
    email?: string;
    status: string;
    createdAt: string;   // ISO 8601 string from backend DTO
    updatedAt: string;
    deletedAt: string | null;  // null = active, string = soft-deleted
}
```

```ts
// types/inertia.d.ts
import type { PageProps as InertiaPageProps } from "@inertiajs/core";

declare module "@inertiajs/core" {
    interface PageProps extends InertiaPageProps {
        auth: {
            user: {
                id: string;
                name: string;
                email: string;
                roles: string[];
                permissions: string[];
            };
        };
        flash: { success?: string; error?: string; warning?: string };
        ziggy: { url: string; port: number | null; routes: Record<string, unknown> };
    }
}
```

---

## 13. Security — OWASP Top 10:2025

> The current standard is **OWASP Top 10:2025** (released late 2025, active reference for 2026). Two new categories, one consolidation vs. 2021. Each item below maps to the official category and its Laravel/React mitigation.

### A01:2025 — Broken Access Control *(#1, includes SSRF)*

SSRF was consolidated here in 2025. Highest incidence rate of all categories.

- Enforce authorization in `AuthorizationService.php` using Laravel Policies and Gates — never in controllers.
- Deny by default: every route requires explicit permission check.
- Use `->whereUuid('uuid')` on all public-facing route parameters.
- Never expose internal service URLs to user input (mitigates SSRF).
- `PermissionGuard` on all frontend components rendering sensitive actions.

### A02:2025 — Security Misconfiguration *(moved up from #5)*

- Apply HSTS, strict CSP, `X-Frame-Options`, `X-Content-Type-Options` via `SecurityHeadersMiddleware`.
- Disable debug mode (`APP_DEBUG=false`) in production — fail at boot if misconfigured.
- Remove all unused routes, services, and default credentials before deployment.
- Use `php artisan config:cache` in production — never expose raw `.env` values.
- Validate all critical env vars at boot — throw immediately if missing.

### A03:2025 — Software Supply Chain Failures *(new — expanded from Vulnerable Components)*

- Pin all Composer and npm dependencies to exact versions in `composer.lock` / `package-lock.json`.
- Run `composer audit` and `npm audit` in CI on every PR.
- Never install packages from untrusted sources — verify publisher identity for critical packages.
- Monitor dependencies for new CVEs post-deploy (use Dependabot or equivalent).
- Never commit secrets, tokens, or credentials in any dependency or config file.

### A04:2025 — Cryptographic Failures *(down from #2)*

- Use Laravel's built-in `Hash::make()` (bcrypt/argon2) for all passwords — never `md5`, `sha1`, or plain text.
- All sensitive data in transit via HTTPS/TLS 1.3 minimum.
- Encrypt sensitive database columns with Laravel's `encrypted` cast where required.
- Never log sensitive fields (passwords, tokens, PII) — enforced via `logOnly([...])` in audit config.
- Use `APP_KEY` rotation policy for `Crypt::` operations.

### A05:2025 — Injection *(down from #3 — includes SQLi, XSS, OS command injection)*

- **SQLi**: Eloquent PDO binding only. Never concatenate SQL strings. Never use raw `DB::statement()` with user input.
- **XSS**: React `{ }` interpolation only — no `dangerouslySetInnerHTML` without explicit sanitization. Blade uses `{{ }}` strictly.
- **OS command injection**: Never pass user input to `exec()`, `shell_exec()`, `proc_open()`, or `system()`.
- Validate all input at the DTO layer via `spatie/laravel-data` rules before it reaches any handler.

### A06:2025 — Insecure Design *(down from #4)*

- Apply threat modeling at the start of every new module — identify trust boundaries before writing code.
- Domain layer must be framework-agnostic: no Eloquent, no HTTP, no Laravel helpers in `Domain/`.
- Rate limiting on all auth endpoints: `throttle:6,1` on login, `throttle:3,1` on password reset.
- **Honeypot**: every public form (login, register, contact, password reset) MUST use `spatie/laravel-honeypot`.

### A07:2025 — Authentication Failures *(stable at #7)*

- Use Laravel Fortify + Sanctum — never roll custom auth.
- Enforce strong password policy via Fortify's password validation rules.
- Implement MFA / OTP via `spatie/laravel-one-time-passwords` for sensitive operations.
- Token expiry enforced — short-lived access tokens, refresh token rotation.
- Lock accounts after N failed attempts via `throttle` + Fortify's lockout.
- **CSRF**: Inertia 2.0 includes `X-XSRF-TOKEN` automatically on all requests — do NOT add manual CSRF logic.

### A08:2025 — Software or Data Integrity Failures *(stable)*

- Verify integrity of all uploaded files (mime type + content inspection, not just extension).
- Use signed URLs for sensitive file downloads via Laravel Storage.
- Never deserialize untrusted data — avoid PHP `unserialize()` on user input.
- CI/CD pipeline must verify artifact integrity before deploying (checksums, signed commits).

### A09:2025 — Logging & Alerting Failures *(was "Security Logging and Monitoring Failures")*

- Structured OTEL logs on every auth event, failed access attempt, and business state transition.
- Never log raw exceptions with sensitive payload — sanitize before logging.
- Alert on repeated auth failures, mass data exports, and privilege escalation attempts.
- Audit trail via `AuditPort` for all business actions (see Section 15).
- Health checks (`HealthCheckController`) must include alerting for anomalous patterns.

### A10:2025 — Mishandling of Exceptional Conditions *(new)*

- Every command handler and service MUST have explicit error handling — never let exceptions propagate silently.
- Use typed exceptions (`DomainException`, `EntityNotFoundException`, etc.) — never generic `\Exception`.
- Global exception handler maps domain exceptions to appropriate HTTP responses (404, 422, 403, 409).
- Never expose stack traces or internal error details to the client in production.
- Queue jobs MUST implement `failed()` method and log structured failure context via OTEL.

### Frontend Security — React 19 + Inertia 2.0

Security is not only a backend responsibility. Every Inertia/React layer must enforce its own controls:

**Access Control (A01)**
- `<PermissionGuard permission="VIEW_{MODULE}">` wraps every sensitive UI element — buttons, links, action icons, sidebar items.
- Never rely solely on hiding UI elements as the access control mechanism — the backend must always validate. Frontend guards are UX, not security.
- Never pass the authenticated user's `id` (integer) in URLs or forms — always use `uuid`.

**Sensitive Data Exposure (A04)**
- Never store sensitive data (tokens, passwords, PII) in `localStorage` or `sessionStorage` — use session cookies managed by Laravel.
- Never log sensitive props to `console.log()` in production — use environment-aware logging only.
- Inertia shared props (`usePage().props`) must never include raw tokens, hashed passwords, or full PII. Backend controls what is shared.

**Injection / XSS (A05)**
- Always use React `{ }` interpolation — never `dangerouslySetInnerHTML` unless the content has been explicitly sanitized server-side.
- Never construct URLs by concatenating user input directly — use `URLSearchParams` or typed route helpers.
- Never use `eval()`, `new Function()`, or dynamic `import()` with user-controlled strings.

**Authentication (A07)**
- After logout, call `router.visit('/login')` to force a full Inertia page reload — clears all React state and Query cache.
- Use `queryClient.clear()` on logout to prevent stale authenticated data from persisting in memory.
- Never store Sanctum tokens in the frontend for web sessions — session cookies are the correct mechanism.

**Client-Side Validation**
- All forms MUST perform client-side validation (via `spatie/laravel-data` schema mirrored in TypeScript or `zod`) before submission.
- Client-side validation is UX only — backend DTO validation is the authoritative source.
- Never disable or bypass frontend validation to "save time" — it protects users from network round trips on obvious errors.

**Dependency Security (A03)**
- Run `npm audit` in CI on every PR — block merges on high/critical vulnerabilities.
- Never install frontend packages with known CVEs for convenience.

### General

- **Never hardcode** secrets — all from `.env`. Fail at boot if critical env vars are missing.
- **Never commit `.env`** — only `.env.example` with placeholders.
- Apply `SecurityHeadersMiddleware` on all web routes.

---

## 14. Observability

- **OpenTelemetry**: primary mechanism. Instrument all crucial flows with OTEL PHP SDK.
- **Structured logging**: never use bare `Log::error('string')`. Use structured OTEL logs with `trace_id`, contextual payload, error footprints.
- **Health checks**: `HealthCheckController` monitors DB, queue, cache, Reverb, 3rd-party SaaS.

---

## 15. Audit — Two-Level Strategy

| Event type | Mechanism | Activation |
|---|---|---|
| Model lifecycle (`created`, `updated`, `deleted`) | `LogsActivity` trait on EloquentModel | Automatic once configured |
| Business actions (`suspend`, `approve`, `export`) | `AuditPort` injected in CommandHandler | Always manual and explicit |

**`getActivitylogOptions()` rules:**
- `logOnly([...])` — always explicit. Never `logAll()` in production.
- Never include `password`, `remember_token`, API tokens, or hashed credentials.
- `logOnlyDirty()` — mandatory.
- `dontSubmitEmptyLogs()` — mandatory.
- `useLogName('{context}.{module}')` — mandatory.

**`AuditPort` mandatory triggers:** `login`, `logout`, `password_changed`, `user_suspended`, `user_activated`, `role_assigned`, `export_excel`, `export_pdf`, any business state transition.

**Never log:** inside QueryHandlers, inside Eloquent Observers (use trait OR port, not both), raw exceptions (use OTEL for that), full `$request->all()` without sanitizing.

---

## 16. Common Errors and Fixes

### `Target class [role] does not exist`

Add Spatie middleware aliases in `bootstrap/app.php` (see Section 4).

### `Readonly class cannot extend non-readonly class`

Remove `readonly` from any class extending `Spatie\LaravelData\Data` or `AggregateRoot` (see Section 3).

### `->toISOString()` called on string

The Mapper already converts Carbon → ISO string. In QueryHandlers just pass `$entity->createdAt ?? ''` (see Section 5 date handling).

### 401 on `/api/*` from browser

You are calling API routes from an Inertia page. Use the `/data` web JSON endpoints instead (see Section 7).

### 404 after route changes

```bash
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
```

---

## 17. Code Review Checklist

**PHP / Backend**
- [ ] `declare(strict_types=1);` in every file
- [ ] All methods have explicit return types
- [ ] No `readonly` on classes extending `Data` or `AggregateRoot`
- [ ] Dates stored as ISO 8601 strings in domain entities (not Carbon)
- [ ] camelCase property names in domain entities and ReadModels
- [ ] Mapper is the ONLY class importing both domain entity and EloquentModel
- [ ] Every public-facing query uses `uuid`, never `id`
- [ ] `SoftDeletes` + `LogsActivity` on every EloquentModel
- [ ] `logOnly([...])` explicit — no `logAll()`, no passwords/tokens
- [ ] All port bindings registered in ServiceProvider
- [ ] ServiceProvider registered in `bootstrap/providers.php`
- [ ] Cache tags used for list queries with fallback
- [ ] Cache invalidated on every mutation

**Frontend**
- [ ] No hex colors or Tailwind color names in components — only `var(--token)`
- [ ] No `bg-red-600`, `text-gray-500`, or `bg-[#hex]` in components
- [ ] `isPending` used (not `isLoading`) — TanStack Query v5
- [ ] `placeholderData: keepPreviousData` on all paginated list queries
- [ ] `onSuccess`/`onError` removed from `useQuery` — side effects in `useEffect`
- [ ] `DeleteConfirmModal` used for all deletes (no `window.confirm()`)
- [ ] `RestoreConfirmModal` used for all restores
- [ ] Total records count shown on every index page
- [ ] Soft-deleted rows styled with `var(--deleted-row-bg)` and `var(--deleted-row-opacity)`
- [ ] 3 action icons per row: View/Edit/Delete (active) or View/Restore (deleted)
- [ ] `export default` on all Inertia page components
- [ ] `<Head title="..." />` on all Inertia page components
- [ ] `router.*` used instead of deprecated `Inertia.visit()`
- [ ] shadcn components installed via CLI, never hand-edited
