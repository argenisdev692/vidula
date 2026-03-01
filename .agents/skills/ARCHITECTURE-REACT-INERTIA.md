# ARCHITECTURE-REACT-INERTIA.md
# React 19 + Inertia.js 2.0 · Frontend Architecture (2026)

> Stack: React 19 · Inertia.js 2.0 · TypeScript 5 · TanStack Query v5 · TanStack Table v8 · Tailwind CSS v4 · shadcn/ui (latest)

---

## Directory Structure

```
resources/
│
├── css/
│   └── app.css                                   # Tailwind v4 entry + CSS custom tokens (globals)
│
└── js/
    │
    ├── app.tsx                                    # Inertia createInertiaApp entry point
    ├── ssr.tsx                                    # SSR entry point (if enabled)
    │
    ├── common/                                    # 🔵 Generic, domain-agnostic UI primitives
    │   │                                          # Rule: CANNOT import from modules/ or pages/
    │   │
    │   ├── button/
    │   │   └── Button.tsx
    │   │
    │   ├── card/
    │   │   ├── Card.tsx
    │   │   ├── CardHeader.tsx
    │   │   └── CardContent.tsx
    │   │
    │   ├── data-table/                            # Generic TanStack Table wrapper
    │   │   ├── DataTable.tsx                      # <DataTable columns={} data={} />
    │   │   ├── DataTableToolbar.tsx
    │   │   ├── DataTablePagination.tsx
    │   │   ├── DataTableColumnHeader.tsx
    │   │   ├── DataTableBulkActions.tsx
    │   │   ├── DataTableDateRangeFilter.tsx       # shadcn Calendar + Popover — validates dateFrom ≤ dateTo
    │   │   ├── DeleteConfirmModal.tsx             # Modal for soft-delete confirm — replaces window.confirm()
    │   │   └── RestoreConfirmModal.tsx            # Modal for restore confirm
    │   │
    │   ├── form/
    │   │   ├── FormField.tsx
    │   │   ├── FormError.tsx
    │   │   └── FormSection.tsx
    │   │
    │   ├── feedback/
    │   │   ├── Spinner.tsx
    │   │   ├── EmptyState.tsx
    │   │   ├── ErrorBoundary.tsx
    │   │   └── SkeletonRow.tsx
    │   │
    │   ├── export/                                # Used by ALL index pages
    │   │   ├── ExportButton.tsx                   # Dropdown: Export Excel | Export PDF
    │   │   └── ExportMenu.tsx                     # Menu items with per-format loading state
    │   │
    │   ├── helpers/                               # Pure utility functions — no React, no domain
    │   │   ├── cn.ts                              # clsx + tailwind-merge
    │   │   ├── formatDate.ts
    │   │   ├── formatCurrency.ts
    │   │   └── formatPhone.ts
    │   │
    │   └── hooks/                                 # Generic reusable hooks — no domain knowledge
    │       ├── useDebounce.ts
    │       ├── useLocalStorage.ts
    │       └── useIntersectionObserver.ts
    │
    ├── modules/                                   # 🟡 Domain-specific shared code
    │   │                                          # Rule: CANNOT import from pages/
    │   │                                          # Can import from common/ and other modules via types.ts only
    │   │
    │   ├── auth/                                  # 🔐 Reference module — authentication
    │   │   ├── components/
    │   │   │   ├── Avatar.tsx
    │   │   │   └── PermissionGuard.tsx            # Conditional rendering by role/permission
    │   │   ├── hooks/
    │   │   │   └── useCurrentUser.ts              # Reads usePage().props.auth.user
    │   │   └── types.ts
    │   │
    │   ├── users/                                 # 👤 Complete CRUD reference — model for all modules
    │   │   ├── components/
    │   │   │   ├── UserStatusBadge.tsx
    │   │   │   ├── UserSummaryCard.tsx
    │   │   │   └── UserAvatar.tsx
    │   │   ├── hooks/
    │   │   │   ├── useUsers.ts                    # TanStack Query: paginated list
    │   │   │   ├── useUser.ts                     # TanStack Query: single record
    │   │   │   └── useUserMutations.ts            # create / update / softDelete / restore
    │   │   ├── helpers/
    │   │   │   └── userStatusColor.ts
    │   │   └── types.ts
    │   │
    │   └── {your-context}/                        # ⭐ TEMPLATE — duplicate for each new module
    │       ├── components/
    │       │   ├── {YourEntity}StatusBadge.tsx
    │       │   └── {YourEntity}SummaryCard.tsx
    │       ├── hooks/
    │       │   ├── use{YourEntities}.ts           # paginated list
    │       │   ├── use{YourEntity}.ts             # single record
    │       │   └── use{YourEntity}Mutations.ts    # create / update / softDelete / restore
    │       ├── helpers/
    │       │   └── {yourEntity}StatusColor.ts
    │       └── types.ts
    │
    ├── pages/                                     # 🟢 Inertia Page components
    │   │                                          # Rule: mirrors URL route structure
    │   │                                          # ONLY layer allowed to use usePage() and useForm()
    │   │                                          # Can import from modules/ and common/ — never the reverse
    │   │
    │   ├── layouts/
    │   │   ├── AppLayout.tsx                      # Authenticated layout (sidebar + header)
    │   │   ├── AuthLayout.tsx                     # Unauthenticated (login, register)
    │   │   └── GuestLayout.tsx                    # Public-facing
    │   │
    │   ├── dashboard/
    │   │   └── DashboardPage.tsx
    │   │
    │   ├── auth/
    │   │   ├── LoginPage.tsx
    │   │   ├── RegisterPage.tsx
    │   │   └── ForgotPasswordPage.tsx
    │   │
    │   ├── users/                                 # 👤 Complete CRUD page reference
    │   │   ├── components/                        # Private — only imported within pages/users/
    │   │   │   ├── UsersTable.tsx                 # Table with 3 action icons per row
    │   │   │   ├── UserFilters.tsx                # Search + status dropdown + date range
    │   │   │   ├── UserDateRangeFilter.tsx        # Wraps DataTableDateRangeFilter
    │   │   │   ├── UserBulkActionsBar.tsx
    │   │   │   └── UserExportBar.tsx              # Wraps ExportButton with module filters
    │   │   ├── helpers/
    │   │   │   └── buildUserQueryParams.ts        # UserFilters → URLSearchParams
    │   │   ├── UsersIndexPage.tsx                 # GET /users
    │   │   ├── UserShowPage.tsx                   # GET /users/{uuid}
    │   │   ├── UserCreatePage.tsx                 # GET /users/create
    │   │   └── UserEditPage.tsx                   # GET /users/{uuid}/edit
    │   │
    │   └── {your-context}/                        # ⭐ TEMPLATE — duplicate for each new module
    │       ├── components/
    │       │   ├── {YourEntities}Table.tsx
    │       │   ├── {YourEntity}Filters.tsx
    │       │   ├── {YourEntity}DateRangeFilter.tsx
    │       │   ├── {YourEntity}BulkActionsBar.tsx
    │       │   └── {YourEntity}ExportBar.tsx
    │       ├── helpers/
    │       │   └── build{YourEntity}QueryParams.ts
    │       ├── {YourEntities}IndexPage.tsx        # table + filters + total count + export
    │       ├── {YourEntity}ShowPage.tsx
    │       ├── {YourEntity}CreatePage.tsx
    │       └── {YourEntity}EditPage.tsx
    │
    ├── shadcn/                                    # 🔶 CLI-generated only — NEVER hand-edit
    │   ├── button.tsx                             # Regenerate: npx shadcn@latest add <name>
    │   ├── dialog.tsx
    │   ├── input.tsx
    │   ├── select.tsx
    │   ├── table.tsx
    │   ├── badge.tsx
    │   ├── calendar.tsx
    │   ├── popover.tsx
    │   ├── dropdown-menu.tsx
    │   ├── avatar.tsx
    │   ├── separator.tsx
    │   ├── skeleton.tsx
    │   ├── tooltip.tsx
    │   ├── sheet.tsx
    │   ├── card.tsx
    │   ├── form.tsx
    │   ├── checkbox.tsx
    │   ├── tabs.tsx
    │   ├── pagination.tsx
    │   ├── breadcrumb.tsx
    │   ├── scroll-area.tsx
    │   ├── command.tsx
    │   ├── alert.tsx
    │   ├── progress.tsx
    │   ├── sidebar.tsx
    │   ├── spinner.tsx                            # New Oct 2025
    │   ├── field.tsx                              # New Oct 2025 — label + input + error
    │   ├── input-group.tsx                        # New Oct 2025
    │   ├── button-group.tsx                       # New Oct 2025
    │   └── empty.tsx                              # New Oct 2025 — empty state pattern
    │
    └── types/                                     # 🔷 Global TypeScript declarations
        ├── inertia.d.ts                           # Inertia PageProps augmentation
        ├── api.ts                                 # API response interfaces — mirrors backend DTOs
        ├── props.ts                               # Shared prop utility types
        └── globals.d.ts                           # Global ambient declarations (route(), etc.)
```

---

## Route Architecture

### Web routes — Inertia (browser, session auth)

These are the **primary routes**. All browser navigation goes through here. Two sub-types:

**1. Inertia page routes** — return a React component via `Inertia::render()`

```
GET  /{module}               → {Module}PageController@index   → renders {Entities}IndexPage
GET  /{module}/create        → {Module}PageController@create  → renders {Entity}CreatePage
GET  /{module}/{uuid}        → {Module}PageController@show    → renders {Entity}ShowPage
GET  /{module}/{uuid}/edit   → {Module}PageController@edit    → renders {Entity}EditPage
```

**2. JSON data endpoints** — used by TanStack Query from within the browser (same session cookie)

```
GET    /{module}/data/admin              → list (paginated)
POST   /{module}/data/admin              → create
GET    /{module}/data/admin/{uuid}       → show one
PUT    /{module}/data/admin/{uuid}       → update
DELETE /{module}/data/admin/{uuid}       → soft delete
PATCH  /{module}/data/admin/{uuid}/restore → restore
GET    /{module}/data/admin/export       → export Excel or PDF
```

Middleware: `web`, `auth` (session-based). Role checks via `role:SUPER_ADMIN` inside the group.

### API routes — REST for mobile / external systems

Built later, separate concern. Token-based authentication via Laravel Sanctum.

```
GET    /api/{module}/admin              → list
POST   /api/{module}/admin              → create
GET    /api/{module}/admin/{uuid}       → show one
PUT    /api/{module}/admin/{uuid}       → update
DELETE /api/{module}/admin/{uuid}       → soft delete
PATCH  /api/{module}/admin/{uuid}/restore → restore
```

Middleware: `api`, `auth:sanctum`.

### Key distinction

| | Web routes | API routes |
|---|---|---|
| Auth | Session cookie | Sanctum Bearer token |
| Used by | Browser (Inertia + React Query) | Mobile apps, external consumers |
| CSRF | Inertia handles automatically | Not required |
| Build priority | Primary — always first | Secondary — when mobile is needed |

**Never call `/api/*` routes from Inertia pages. Never use session auth on API routes.**

### Route registration in ServiceProvider

```php
// {Module}ServiceProvider.php
private function registerWebRoutes(): void
{
    Route::middleware(['web', 'auth'])
        ->prefix('{module}')
        ->group(__DIR__ . '/../Infrastructure/Routes/web.php');
}

private function registerApiRoutes(): void
{
    Route::middleware(['api', 'auth:sanctum'])
        ->prefix('api/{module}')
        ->group(__DIR__ . '/../Infrastructure/Routes/api.php');
}
```

```php
// Infrastructure/Routes/web.php

// Inertia pages
Route::get('/', [{Module}PageController::class, 'index'])->name('{module}.index');
Route::get('/create', [{Module}PageController::class, 'create'])->name('{module}.create');
Route::get('/{uuid}', [{Module}PageController::class, 'show'])->name('{module}.show')->whereUuid('uuid');
Route::get('/{uuid}/edit', [{Module}PageController::class, 'edit'])->name('{module}.edit')->whereUuid('uuid');

// JSON data endpoints (React Query internal web API)
Route::prefix('data')->group(function () {
    Route::middleware(['role:SUPER_ADMIN'])->prefix('admin')->group(function () {
        Route::get('/', [Admin{Module}Controller::class, 'index']);
        Route::post('/', [Admin{Module}Controller::class, 'store']);
        Route::get('/{uuid}', [Admin{Module}Controller::class, 'show'])->whereUuid('uuid');
        Route::put('/{uuid}', [Admin{Module}Controller::class, 'update'])->whereUuid('uuid');
        Route::delete('/{uuid}', [Admin{Module}Controller::class, 'destroy'])->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [Admin{Module}Controller::class, 'restore'])->whereUuid('uuid');
        Route::get('/export', [{Module}ExportController::class, '__invoke']);
    });
});
```

---

## Layer Rules

| Layer | Can import from | Cannot import from |
|---|---|---|
| `common/` | nothing (self-contained) | `modules/`, `pages/` |
| `modules/` | `common/`, other modules' `types.ts` | `pages/` |
| `pages/` | `modules/`, `common/`, `shadcn/` | nothing forbidden |
| `shadcn/` | nothing (CLI-generated) | — never hand-edited |

---

## File Naming Conventions

| What | Convention | Example |
|---|---|---|
| React components | `PascalCase.tsx` | `UserStatusBadge.tsx` |
| Hooks | `camelCase.ts` | `useUsers.ts` |
| Helpers / utils | `camelCase.ts` | `formatCurrency.ts` |
| Type files | `camelCase.ts` | `types.ts`, `api.ts` |
| Directories | `kebab-case` | `data-table/`, `users/` |
| Inertia Pages | `{Module}IndexPage.tsx` | `UsersIndexPage.tsx` |
| Layouts | `PascalCaseLayout.tsx` | `AppLayout.tsx` |

---

## Quick Reference: Where Does This File Go?

| What you are creating | Directory |
|---|---|
| Reusable UI primitive (Button, Badge, Modal) | `common/{name}/` |
| Generic table wrapper | `common/data-table/` |
| Delete or restore confirm modal | `common/data-table/` |
| Export button | `common/export/` |
| Domain component used across multiple pages | `modules/{context}/components/` |
| TanStack Query hook | `modules/{context}/hooks/` |
| Domain types / interfaces | `modules/{context}/types.ts` |
| Inertia Page component | `pages/{route-group}/` |
| Component private to one page group | `pages/{route-group}/components/` |
| Helper private to one page group | `pages/{route-group}/helpers/` |
| Global layout | `pages/layouts/` |
| shadcn/ui component | `shadcn/` — CLI only |
| Inertia PageProps interface | `types/inertia.d.ts` |
| API response / DTO interfaces | `types/api.ts` |
| Shared React prop utility types | `types/props.ts` |
| Date range filter | `common/data-table/DataTableDateRangeFilter.tsx` |
