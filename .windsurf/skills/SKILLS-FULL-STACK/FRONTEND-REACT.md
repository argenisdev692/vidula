---
name: frontend-react
description: Primary guide for frontend tasks with React 19, Inertia 2.0, and strict TypeScript, including TanStack, token-based styling, components, and the project's UI patterns.
---

# FRONTEND-REACT.md — React 19 + Inertia 2.0 + Styles · Enterprise Frontend (2026)

> **Authority**: This file is the SINGLE SOURCE OF TRUTH for all frontend rules.
> **Stack**: React 19 · Inertia.js 2.0 · TypeScript 5 (strict) · TanStack Query v5 · TanStack Table v8 · Tailwind CSS v4 · shadcn/ui · Framer Motion · Sileo (toasts)
> **Design**: Developer UI inspired by VS Code, Linear, Raycast, Vercel. Dark-first, token-driven.

> **CRITICAL — Backend ↔ Frontend Contract**: All TypeScript interfaces use `snake_case` keys (`full_name`, `created_at`, `deleted_at`).
> Every Spatie `Data` class that serializes to JSON **MUST** have `#[MapOutputName(SnakeCaseMapper::class)]`.
> If a field shows `undefined` in the table, verify the backend Data class has this attribute.

## §0 — Token-First Principle (ABSOLUTE RULE)

**Never use hex values, Tailwind color names like `bg-red-600`, or `bg-[#hex]` in components. All colors from `var(--token)` only.**

```css
/* ✅ Correct */
background: var(--bg-card);
color: var(--text-muted);

/* ❌ NEVER */
background: #1a1a2e;
color: bg-red-600;
```

Before implementing any component, read `globals.css` and use existing tokens. If a token doesn't exist, add it to `globals.css` first.

---

## §1 — Design Tokens (`resources/css/globals.css`)

> `app.css` is the Tailwind v4 entry point. It imports `globals.css` via `@import "./globals.css"`. All custom tokens go in `globals.css`.

### Core Tokens

```css
:root {
    /* backgrounds (darkest → lightest) */
    --bg-app: #0a0a1a;
    --bg-surface: #12122a;
    --bg-card: #1a1a3e;
    --bg-hover: #252550;

    /* borders */
    --border-subtle: rgba(255, 255, 255, 0.06);
    --border-default: rgba(255, 255, 255, 0.1);
    --border-hover: rgba(255, 255, 255, 0.18);

    /* text */
    --text-primary: #e8e8ed;
    --text-secondary: #b0b0c0;
    --text-muted: #7a7a90;
    --text-disabled: #4a4a5e;

    /* accents */
    --accent-primary: #6366f1;
    --accent-secondary: #a78bfa;
    --accent-success: #22c55e;
    --accent-warning: #f59e0b;
    --accent-error: #ef4444;
    --accent-info: #38bdf8;

    /* typography */
    --font-sans: "Inter", sans-serif;
    --font-mono: "JetBrains Mono", monospace;

    /* radii & transition */
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --transition: 0.2s ease;

    /* form tokens */
    --input-bg: var(--bg-card);
    --input-border: var(--border-default);
    --input-border-focus: var(--accent-primary);
    --input-border-error: var(--accent-error);
    --input-text: var(--text-primary);
    --input-placeholder: var(--text-muted);
    --input-height: 40px;
    --input-padding-x: 12px;
    --input-font-size: 14px;
    --input-radius: var(--radius-md);

    /* soft-delete row tokens */
    --deleted-row-bg: color-mix(
        in srgb,
        var(--accent-error) 8%,
        var(--bg-card)
    );
    --deleted-row-border: color-mix(
        in srgb,
        var(--accent-error) 25%,
        transparent
    );
    --deleted-row-opacity: 0.65;
}
```

### Light Mode Override

```css
[data-theme="light"] {
    --bg-app: #f8f8fc;
    --bg-surface: #ffffff;
    --bg-card: #f1f1f6;
    --bg-hover: #e8e8f0;
    --border-subtle: rgba(0, 0, 0, 0.05);
    --border-default: rgba(0, 0, 0, 0.1);
    --text-primary: #1a1a2e;
    --text-secondary: #3a3a52;
    --text-muted: #6a6a82;
    --accent-primary: #4f46e5;
    --accent-error: #dc2626;
    --accent-success: #16a34a;
}
```

### Tailwind v4 Mapping

Map all tokens in `tailwind.config.js` under `theme.extend.colors`, `fontFamily`, `borderRadius`.

### §2.1 — Accessibility (WCAG AA Compliance)

> Integrated into §2 below.

---

## §2 — Accessibility (WCAG 2.2 AA + WCAG 2.3.1)

```css
:focus {
    outline: none;
}
:focus-visible {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
    border-radius: var(--radius-sm);
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
```

**Hard rules:**

- **WCAG 2.3.1 — Three Flashes**: No element may flash >3×/sec. `duration ≤ 0.4s` (§11). `globals.css` MUST include `@media (prefers-reduced-motion: reduce)`.
- **Contrast**: Text ≥ 4.5:1 (normal), ≥ 3:1 (large). Labels use `--text-secondary`, never `--text-disabled`.
- **Focus rings**: ≥ 3:1 contrast. Minimum 24×24px tap targets.
- **ARIA**: Icon-only buttons must have `aria-label` or `title`.
- **Keyboard**: Modals close on `Escape`. Confirm buttons receive auto-focus.
- **Form controls**: `select`, `input[type="date"]` must have `color-scheme: dark` and `background: var(--bg-elevated)` in dark mode.

---

## §3 — Directory Structure

```
resources/js/
├── app.tsx                    # Inertia createInertiaApp entry
├── common/                    # 🔵 Domain-agnostic UI (CANNOT import modules/ or pages/)
│   ├── data-table/           # DataTable, Pagination, BulkActions, DateRangeFilter, DeleteConfirmModal, RestoreConfirmModal
│   ├── export/               # ExportButton, ExportMenu
│   ├── helpers/              # cn.ts, formatDate.ts, formatCurrency.ts
│   └── hooks/                # useDebounce, useLocalStorage
├── modules/                   # 🟡 Domain-specific (CANNOT import pages/)
│   ├── auth/                 # PermissionGuard, useCurrentUser
│   └── {context}/            # hooks/, components/, helpers/, types.ts
├── pages/                     # 🟢 Inertia pages (can import everything)
│   ├── layouts/              # AppLayout, AuthLayout, GuestLayout
│   └── {module}/             # IndexPage, ShowPage, CreatePage, EditPage + components/
├── shadcn/                    # 🔶 CLI-generated ONLY — never hand-edit
└── types/                     # inertia.d.ts, api.ts, props.ts
```

### Layer Rules

| Layer      | Can import from                      | Cannot import from   |
| ---------- | ------------------------------------ | -------------------- |
| `common/`  | self-contained                       | `modules/`, `pages/` |
| `modules/` | `common/`, other modules' `types.ts` | `pages/`             |
| `pages/`   | `modules/`, `common/`, `shadcn/`     | —                    |
| `shadcn/`  | CLI-generated                        | never hand-edit      |

---

## §4 — Route Architecture

### Web Routes (Inertia + session)

| Type         | Pattern                                     | Purpose                       |
| ------------ | ------------------------------------------- | ----------------------------- |
| Inertia page | `GET /{module}`                             | Renders `{Entities}IndexPage` |
| Inertia page | `GET /{module}/create`                      | Renders `{Entity}CreatePage`  |
| Inertia page | `GET /{module}/{uuid}`                      | Renders `{Entity}ShowPage`    |
| Inertia page | `GET /{module}/{uuid}/edit`                 | Renders `{Entity}EditPage`    |
| JSON data    | `GET /{module}/data/admin`                  | List (TanStack Query)         |
| JSON data    | `POST /{module}/data/admin`                 | Create                        |
| JSON data    | `GET /{module}/data/admin/{uuid}`           | Show one                      |
| JSON data    | `PUT /{module}/data/admin/{uuid}`           | Update                        |
| JSON data    | `DELETE /{module}/data/admin/{uuid}`        | Soft delete                   |
| JSON data    | `PATCH /{module}/data/admin/{uuid}/restore` | Restore                       |
| JSON data    | `GET /{module}/data/admin/export`           | Export                        |
| JSON data    | `POST /{module}/data/admin/bulk-delete`     | Bulk delete                   |

### API Routes (Sanctum — mobile/external)

Same CRUD pattern under `/api/{module}/admin`. Middleware: `api`, `auth:sanctum`.

**Never call `/api/*` from Inertia pages. Never use session auth on API routes.**

---

## §5 — Inertia 2.0 Rules

```tsx
// ✅ Always use Link + router
import { Link, router } from "@inertiajs/react";
<Link href="/users" prefetch>
    Users
</Link>;
router.visit("/users");
router.post("/users/data/admin", data);

// ❌ Deprecated
Inertia.visit("/users");
```

### Page Component Pattern

```tsx
export default function UsersIndexPage(): React.JSX.Element {
    return (
        <>
            <Head title="Users" />
            <AppLayout>{/* ... */}</AppLayout>
        </>
    );
}
```

**Rules**: Always `export default`. Always `<Head title="..." />`. Explicit `React.JSX.Element` return. Typed via `usePage<Props>()`.

### Deferred Props (v2)

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

Inertia 2.0 auto-includes `X-XSRF-TOKEN`. **Do NOT implement manual CSRF logic.**

---

## §6 — TanStack Query v5

### List Hook (paginated)

```ts
import { useQuery, keepPreviousData } from "@tanstack/react-query";

export function use{Entities}(filters: {Entity}Filters) {
    return useQuery<PaginatedResponse<{Entity}ListItem>, Error>({
        queryKey: ["{entities}", filters],
        queryFn: async () => {
            const { data } = await axios.get<PaginatedResponse<{Entity}ListItem>>(
                "/{module}/data/admin", { params: filters }
            );
            return data;
        },
        placeholderData: keepPreviousData, // ✅ v5
        staleTime: 1000 * 60 * 2,
    });
}
```

### Mutation Hook

```ts
import { sileo } from 'sileo';
import type { AxiosError } from 'axios';

/**
 * MANDATORY: Helper to safely extract the best error message from Axios
 * This prevents showing generic "Request failed with status code 422" messages.
 */
function getErrorMessage(err: AxiosError | any, defaultMsg: string): string {
  if (err?.response?.data?.message) {
      return err.response.data.message;
  }
  return err?.message || defaultMsg;
}

export function use{Entity}Mutations() {
    const queryClient = useQueryClient();

    const create{Entity} = useMutation({
        mutationFn: (payload: Create{Entity}Payload) => axios.post(`/{module}/data/admin`, payload),
        onSuccess: () => {
            sileo.success({ title: '{Entity} created successfully' });
            queryClient.invalidateQueries({ queryKey: ["{entities}"] });
        },
        onError: (err: AxiosError) => {
            sileo.error({ title: getErrorMessage(err, 'Failed to create {entity}') });
        }
    });

    const delete{Entity} = useMutation({
        mutationFn: (uuid: string) => axios.delete(`/{module}/data/admin/${uuid}`),
        onSuccess: () => {
             sileo.success({ title: '{Entity} deleted successfully' });
             queryClient.invalidateQueries({ queryKey: ["{entities}"] });
        },
        onError: (err: AxiosError) => {
             sileo.error({ title: getErrorMessage(err, 'Failed to delete {entity}') });
        }
    });

    return { create{Entity}, delete{Entity} };
}
```

### v5 Breaking Changes

- `isLoading` → `isPending` (queries and mutations)
- `cacheTime` → `gcTime`
- `keepPreviousData` option removed → `placeholderData: keepPreviousData` (imported function)
- `onError`/`onSuccess`/`onSettled` removed from `useQuery` → use `useEffect`
- Single-object API only — no positional argument overloads

---

## §7 — TanStack Table v8 (MANDATORY)

> **DataTable MUST use `@tanstack/react-table` (`useReactTable`) — NEVER use shadcn/ui's `data-table` component.**
> shadcn/ui `Table` primitives (`Table`, `TableRow`, `TableCell`, `TableHead`) are allowed ONLY as HTML wrappers for rendering.
> The `@/shadcn/data-table.tsx` is a **custom composition** (TanStack logic + shadcn HTML primitives), NOT a shadcn-generated file.

### Critical Rules

1. `columnHelper` MUST be defined **outside** the component (module-level constant)
2. `getRowId: (row) => row.uuid` MUST be provided — stable IDs for optimistic updates
3. `columnHelper` MUST NOT appear in `useMemo` deps
4. Never hide TanStack API behind wrapper abstractions
5. All table logic (`useReactTable`, `getCoreRowModel`, `getSortedRowModel`, `flexRender`) comes from `@tanstack/react-table`
6. HTML rendering uses `@/shadcn/table` primitives (thin `<table>`/`<tr>`/`<td>` wrappers).
7. **Centered Cells (MANDATORY)**: All table cells (`TableHead` and `TableCell`) MUST be centered by default (`text-align: center`). Ensure consistency with PDF exports.

### Table Template

```tsx
const columnHelper = createColumnHelper<{Entity}ListItem>(); // ✅ OUTSIDE component

export function {Entities}Table({ data, isLoading, onDelete, rowSelection, onRowSelectionChange }: Props) {
    const columns = React.useMemo(() => [
        columnHelper.display({ id: 'select', /* checkbox */ }),
        columnHelper.accessor('name', { header: 'Name', enableSorting: true }),
        columnHelper.display({ id: 'actions', cell: ({ row }) => { /* Eye/Pencil/Trash2 or CheckCircle */ } }),
    ], [onDelete]); // ✅ columnHelper NOT in deps

    const table = useReactTable({
        data, columns,
        getRowId: (row) => row.uuid,  // ✅ required
        state: { rowSelection, sorting },
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
    });
}
```

### Three Action Icons Per Row (mandatory)

| State        | Icons                                        |
| ------------ | -------------------------------------------- |
| Active       | Eye (View) + Pencil (Edit) + Trash2 (Delete) |
| Soft-deleted | Eye (View) + CheckCircle (Restore)           |

### Soft-Deleted Row Styling

```tsx
<tr style={row.original.deleted_at ? {
    background: "var(--deleted-row-bg)",
    opacity: "var(--deleted-row-opacity)",
    borderLeft: "2px solid var(--deleted-row-border)",
} : undefined}>
```

---

## §8 — Index Page Pattern

Every `{Entities}IndexPage.tsx` MUST include:

- **Counter**: `{meta.total} {meta.total === 1 ? 'record' : 'records'} found`
- **Status Filter**: All / Active / Deleted
- **Search** + **Date Range** (`DataTableDateRangeFilter`) + **Export** (`ExportButton`)
- **Bulk Delete**: `rowSelection` + `DataTableBulkActions` + `router.post`
- **`DeleteConfirmModal`** — never `window.confirm()`
- **`RestoreConfirmModal`**
- **`useTransition`** — wraps search/filter/export updates
- **`useOptimistic`** — instant delete feedback, inside `React.startTransition(async () => {...})`
- **`useRemember`** (Inertia) — filter persistence
- **Sliding paginator** — 5 pages around current

```tsx
export default function {Entities}IndexPage(): React.JSX.Element {
    const [filters, setFilters] = useRemember<{Entity}Filters>({ page: 1, perPage: 15 }, '{module}-filters');
    const [rowSelection, setRowSelection] = React.useState<RowSelectionState>({});
    const [pendingDelete, setPendingDelete] = React.useState<{ uuid: string; name: string } | null>(null);
    const [, startSearchTransition] = React.useTransition();

    const { data, isPending } = use{Entities}(filters); // isPending ✅
    const items = data?.data ?? [];
    const meta = data?.meta ?? { currentPage: 1, lastPage: 1, total: 0 };

    const [optimisticItems, setOptimisticItems] = React.useOptimistic(
        items,
        (state, deletedUuid: string) => state.filter(i => i.uuid !== deletedUuid)
    );

    async function handleConfirmDelete(): Promise<void> {
        if (!pendingDelete) return;
        React.startTransition(async () => {
            setOptimisticItems(pendingDelete.uuid);
            try {
                await delete{Entity}.mutateAsync(pendingDelete.uuid);
                setPendingDelete(null);
            } catch { /* auto-reverts */ }
        });
    }

    // ... render: Header + Filters + Table + Pagination + Modals
}
```

---

## §9 — Components

### Buttons

**Primary Buttons / Add New (CRUD)**
Must always use the dual class combination for the modern gradient and shadow:
`className="btn-modern btn-modern-primary flex items-center gap-2 ..."`

```css
.btn-primary {
    background: var(--accent-primary);
    color: var(--text-primary);
    border-radius: var(--radius-md);
    transition: var(--transition);
}
.btn-primary:hover {
    filter: brightness(0.88);
}
.btn-ghost {
    background: transparent;
    color: var(--text-muted);
    border: 1px solid var(--border-default);
}
.btn-ghost:hover {
    background: var(--bg-hover);
    color: var(--text-secondary);
}
```

### Badges (accent tints via `color-mix()`)

```css
.badge-success {
    background: color-mix(in srgb, var(--accent-success) 15%, transparent);
    color: var(--accent-success);
}
.badge-error {
    background: color-mix(in srgb, var(--accent-error) 15%, transparent);
    color: var(--accent-error);
}
```

### Cards

```css
.card {
    background: var(--bg-card);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
    padding: 16px;
}
```

### §9.1 — Sidebar Navigation

**Hard rules:**

1. Every nav item with a `permission` MUST be wrapped in `<PermissionGuard permissions={[...]}>`.
2. Related modules MUST be grouped inside **collapsible dropdown sections** (e.g., "People" → Users, Students, Clients).
3. Each group section has a label, an icon, and a `ChevronDown` toggle. Clicking expands/collapses children.
4. Groups persist their open/closed state across navigation (`useState` with `localStorage`).
5. Active route auto-expands its parent group.
6. Section labels (`Navigation`, `People`, `Management`) use `text-[10px] font-semibold uppercase tracking-[1.8px]` with `--text-disabled`.

**Nav structure:**

```tsx
const NAV_GROUPS = [
    {
        label: "Overview",
        items: [
            {
                label: "Dashboard",
                href: "/dashboard",
                icon: <LayoutDashboard />,
            },
        ],
    },
    {
        label: "People",
        items: [
            {
                label: "Users",
                href: "/users",
                icon: <Users />,
                permission: "VIEW ANY USERS",
            },
            {
                label: "Students",
                href: "/students",
                icon: <GraduationCap />,
                permission: "VIEW ANY STUDENTS",
            },
            {
                label: "Clients",
                href: "/clients",
                icon: <UserCheck />,
                permission: "VIEW ANY CLIENTS",
            },
        ],
    },
    {
        label: "Management",
        items: [
            {
                label: "Company Profiles",
                href: "/company-data",
                icon: <Building2 />,
                permission: "VIEW ANY COMPANY",
            },
            {
                label: "Products",
                href: "/products",
                icon: <Package />,
                permission: "VIEW ANY PRODUCTS",
            },
        ],
    },
];
```

### Typography

```
Main heading: 22px weight 800 letter-spacing -0.5px
Section heading: 18px weight 700
Body: 14px weight 400 line-height 1.8
Label: 11px weight 600 uppercase letter-spacing 1.5px
```

---

## §10 — shadcn/ui Rules

- **NEVER hand-edit** files in `shadcn/` — regenerate via `npx shadcn@latest add <name>`.
    - **Exception**: `data-table.tsx`, `DeleteConfirmModal.tsx`, `RestoreConfirmModal.tsx`, `DataTableBulkActions.tsx` — these are **custom compositions**, not shadcn-generated.
- Wrap in `common/` abstractions. Never import directly in pages.
- Search Tavily `site:ui.shadcn.com` before building custom components.
- **Required new components (Oct 2025)**: Spinner, Field, InputGroup, ButtonGroup, Empty, Item, Kbd.
- **NEVER use shadcn/ui `data-table`**: Our DataTable uses TanStack Table v8 directly (§7). shadcn/ui's `Table` primitive is used ONLY for HTML rendering (`<table>`, `<tr>`, `<td>` wrappers).

---

## §11 — Framer Motion Rules

All variants/transitions in `lib/motion.ts`:

```ts
export const transitions = {
    default: { duration: 0.2, ease: "easeOut" },
    spring: { type: "spring", stiffness: 300, damping: 30 },
} as const;

export const variants = {
    fadeIn: { hidden: { opacity: 0 }, visible: { opacity: 1 } },
    slideUp: { hidden: { opacity: 0, y: 8 }, visible: { opacity: 1, y: 0 } },
    scaleIn: {
        hidden: { opacity: 0, scale: 0.96 },
        visible: { opacity: 1, scale: 1 },
    },
} as const;
```

**Hard rules**: Never animate bg colors/font sizes via Framer Motion (use CSS). Never `duration > 0.4s`. Never `whileHover` scale `> 1.04`. Always `AnimatePresence` on unmount. Never inline variants — import from `lib/motion.ts`.

---

## §12 — Toasts (Sileo React)

```tsx
import { toast } from "sileo";
toast.success("User created successfully");
toast.error("Failed to delete user");
```

Configure in `globals.css`:

```css
[data-sileo-container] {
    --sileo-bg: var(--bg-card);
    --sileo-border: var(--border-default);
    --sileo-text: var(--text-primary);
    font-family: var(--font-sans);
}
```

---

## §12.1 — Export Functionality

### Export Button Component

Every index page MUST include an `ExportButton` component that triggers Excel/PDF downloads:

```tsx
import { ExportButton } from "@/common/export/ExportButton";

// In IndexPage component
const [isPendingExport, startExportTransition] = React.useTransition();

function handleExport(format: "excel" | "pdf"): void {
    startExportTransition(() => {
        const params = new URLSearchParams({ format });
        if (filters.search) params.append("search", filters.search);
        if (filters.dateFrom) params.append("dateFrom", filters.dateFrom);
        if (filters.dateTo) params.append("dateTo", filters.dateTo);
        if (filters.status) params.append("status", filters.status);
        window.open(`/{module}/data/admin/export?${params}`, "_blank");
    });
}

// In render
<ExportButton onExport={handleExport} isExporting={isPendingExport} />;
```

### Export Rules

1. **Always use `useTransition`** to wrap export operations
2. **Pass all active filters** to the export endpoint via query params
3. **Open in new tab** using `window.open(..., '_blank')`
4. **Show loading state** via `isExporting` prop
5. **Date format**: Backend returns dates as "March 3, 2026" (human-readable)
6. **Export route**: Must be registered BEFORE `/{uuid}` route in backend

### Export Button Placement

Place export button in the toolbar, after date range filter:

```tsx
<div className="flex items-center gap-3 flex-wrap">
    {/* Search */}
    <div className="flex flex-1 items-center gap-3">
        <Search size={14} />
        <input type="text" value={search} onChange={handleSearchChange} />
    </div>

    <div className="h-6 w-px" style={{ background: 'var(--border-subtle)' }} />

    {/* Status Filter */}
    <select value={filters.status ?? ''} onChange={...}>
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="deleted">Deleted</option>
    </select>

    <div className="h-6 w-px" style={{ background: 'var(--border-subtle)' }} />

    {/* Date Range */}
    <DataTableDateRangeFilter
        dateFrom={filters.dateFrom}
        dateTo={filters.dateTo}
        onChange={(range) => setFilters(p => ({ ...p, ...range, page: 1 }))}
    />

    <div className="h-6 w-px" style={{ background: 'var(--border-subtle)' }} />

    {/* Export */}
    <ExportButton onExport={handleExport} isExporting={isPendingExport} />
</div>
```

### Export Checklist

- [ ] `ExportButton` component imported from `@/common/export/ExportButton`
- [ ] `useTransition` hook wraps export handler
- [ ] All active filters passed as query params
- [ ] Export opens in new tab (`window.open(..., '_blank')`)
- [ ] Loading state shown during export
- [ ] Export button placed after date range filter in toolbar
- [ ] Vertical separators (`div.h-6.w-px`) between toolbar sections

---

## §13 — TypeScript Contracts

```ts
export interface PaginatedResponse<T> {
    data: T[];
    meta: { currentPage: number; lastPage: number; perPage: number; total: number; };
}

export interface {Entity}ListItem {
    uuid: string; name: string; email?: string; status: string;
    createdAt: string; updatedAt: string; deletedAt: string | null;
}
```

---

## §14 — Frontend Security

| OWASP                  | Mitigation                                                                             |
| ---------------------- | -------------------------------------------------------------------------------------- |
| **A01 Access Control** | `<PermissionGuard permission="VIEW_{MODULE}">`. Never rely on UI hiding alone.         |
| **A04 Data Exposure**  | Never store tokens/PII in localStorage. Never `console.log()` sensitive props in prod. |
| **A05 XSS**            | React `{ }` interpolation only. No `dangerouslySetInnerHTML`. No `eval()`.             |
| **A07 Auth**           | `router.visit('/login')` + `queryClient.clear()` on logout. Session cookies only.      |
| **Client Validation**  | Client-side = UX only. Backend DTO = authoritative.                                    |

---

## §15 — Frontend Checklist

- [ ] No hex colors or Tailwind names — only `var(--token)`
- [ ] `isPending` (not `isLoading`) — TanStack Query v5
- [ ] `placeholderData: keepPreviousData` on paginated queries
- [ ] `queryKey` first element = entity name string
- [ ] `columnHelper` outside component, NOT in `useMemo` deps
- [ ] `getRowId: (row) => row.uuid` on all tables
- [ ] `DeleteConfirmModal` + `RestoreConfirmModal` (no `window.confirm()`)
- [ ] Counter: `{count} {count === 1 ? 'record' : 'records'} found`
- [ ] Status Filter (All/Active/Deleted)
- [ ] Bulk Delete via `rowSelection` + `router.post`
- [ ] `formatDateShort()` for all date columns
- [ ] Soft-deleted rows styled with `--deleted-row-*` tokens
- [ ] 3 action icons: Eye/Pencil/Trash2 (active) or Eye/CheckCircle (deleted)
- [ ] `export default` + `<Head title="..." />` on all pages
- [ ] `useTransition` wraps search/filter/export
- [ ] `useOptimistic` inside `React.startTransition(async () => {...})`
- [ ] `useRemember` for filter persistence
- [ ] Sliding paginator (5 pages around current)
- [ ] shadcn components via CLI, never hand-edited
- [ ] Sidebar nav item with `PermissionGuard`

### File Naming

| What             | Convention              | Example               |
| ---------------- | ----------------------- | --------------------- |
| React components | `PascalCase.tsx`        | `UserStatusBadge.tsx` |
| Hooks            | `camelCase.ts`          | `useUsers.ts`         |
| Helpers          | `camelCase.ts`          | `formatCurrency.ts`   |
| Inertia Pages    | `{Module}IndexPage.tsx` | `UsersIndexPage.tsx`  |
| Directories      | `kebab-case`            | `data-table/`         |
