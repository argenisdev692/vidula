---
description: Audits React 19 + Inertia.js 3.0 modules against design tokens, TanStack Query/Table, Zustand, accessibility, security and architecture rules. Auto-fixes violations and re-verifies until 100% score.
---

# FRONTEND AUDIT AGENT — React 19 + Inertia.js 3.0 + TanStack Query/Table + Zustand + shadcn/ui + Tailwind v4

## PHASE 1 — AUDIT (produce checklist)

Before starting the audit, you MUST:

1. Read `.claude/FRONTEND/SKILL.md` — single source of truth for frontend rules
2. Read `.claude/OWASP/SKILL.md` — the always-on security baseline (15+1 items mapped to OWASP Top 10:**2025** + API Top 10:2023 + LLM Top 10:2025 when AI is in scope)
3. Call context7 to resolve current docs for: React 19 (function components + new hooks: `use`, `useActionState`, `useFormStatus`, `useOptimistic`, `useTransition`), React Compiler (`babel-plugin-react-compiler`, stable v1), Inertia.js 3.0 (`@inertiajs/react` — built-in XHR client, `useHttp`, `@inertiajs/vite`), TanStack Query v5 (`/tanstack/query`), TanStack Table v8 (`/tanstack/table`), Zustand v5 (`/pmndrs/zustand`), shadcn/ui (`/shadcn-ui/ui`), `react-hook-form` + `@hookform/resolvers`, Zod v4, Tailwind CSS v4, Framer Motion, Sileo
4. Call tavily to verify latest stable versions of all frontend packages, prioritizing recent/current sources (`time_range: day`, `week`, or `month`); search `site:ui.shadcn.com` for any primitive being audited

Then analyze the indicated module against these rules.
For each item mark ✅ PASS, ❌ FAIL (with file:line and brief description) or ⚠️ WARN.

### Required Checklist

**Styles & Tokens (§0, §1)**

- [ ] Zero hex values or Tailwind palette names in components — only `var(--token)` project tokens or token-mapped Tailwind utilities
- [ ] No `bg-red-600`, `text-gray-500`, or `bg-[#hex]` anywhere
- [ ] All new core tokens added to `globals.css` FIRST, with the `.dark`/light override added in the same commit when contrast flips
- [ ] Soft-deleted rows use `--deleted-row-bg`, `--deleted-row-border`, `--deleted-row-opacity` via the table row `style`
- [ ] Badge/severity tones resolve through `color-mix(in srgb, var(--accent-*) …)` — never inline hex
- [ ] Cards use the `.card` token-based class (or shadcn `Card`) — no hardcoded colors
- [ ] Buttons use `.btn-primary` / `.btn-ghost` token classes (or shadcn `Button` variants) — no inline bg styles
- [ ] Toast colors sourced from tokens in the `[data-sileo-container]` block (no hardcoded hex)
- [ ] `app.css` imports `tailwindcss` + `globals.css`; declares `@custom-variant dark (&:is(.dark *));`
- [ ] `.dark` override block present in `globals.css` (NOT `[data-theme]` besides the documented light override, NEVER `.light`)

**Accessibility (§2)**

- [ ] `@media (prefers-reduced-motion: reduce)` present in `globals.css`
- [ ] `:focus-visible` ring uses `var(--accent-primary)`, not `:focus`
- [ ] Icon-only buttons have `aria-label` or `title`
- [ ] Modals close on `Escape`, confirm button receives auto-focus
- [ ] No element flashes > 3×/sec — all animation `duration ≤ 0.4s`
- [ ] Labels use `--text-secondary`, never `--text-disabled`
- [ ] Minimum 24×24px tap targets; text contrast ≥ 4.5:1

**Architecture / Layer Rules (§3)**

- [ ] `common/` imports nothing from `modules/` or `pages/`
- [ ] `modules/` imports nothing from `pages/` (may import `common/` and other modules' `types.ts`)
- [ ] `pages/` is the layer that composes `modules/`, `common/`, `shadcn/`
- [ ] `shadcn/` files are CLI-generated only — never hand-edited (except the documented custom compositions: `data-table.tsx`, `DeleteConfirmModal.tsx`, `RestoreConfirmModal.tsx`, `DataTableBulkActions.tsx`)
- [ ] `@` Vite alias points at `resources/js/`; `cn()` exported from `resources/js/common/helpers/cn.ts`
- [ ] File naming: components `PascalCase.tsx`, hooks/helpers `camelCase.ts`, dirs `kebab-case/`
- [ ] Inertia pages live in `pages/{module}/{Entity}{Index|Show|Create|Edit}Page.tsx`
- [ ] Every page `export default`, wraps its layout (`<AppLayout>`), and renders `<Head title="..." />`
- [ ] State ownership is clear: server state in TanStack Query, client UI state in Zustand, page props from `usePage<Props>()`
- [ ] KISS / DRY / Clean Code / SRP applied to components, hooks, helpers

**State Management (Zustand + TanStack Query)**

- [ ] Audit whether Zustand is installed and whether the module truly needs shared client state
- [ ] Zustand stores live in `modules/{context}/stores/` (or `modules/app/stores/` for app-shell) and are created with `create<T>()`
- [ ] Stores are explicitly typed and consumed through selectors, not the whole store
- [ ] No duplication between Zustand stores and TanStack Query server state
- [ ] `persist` middleware stores only non-sensitive UI preferences (never tokens/PII/permission snapshots)
- [ ] No oversized global store introduced without a concrete DX/complexity justification

**Inertia.js 3.0 (§5)**

- [ ] `Link` + `router` imported from `@inertiajs/react` (never `Inertia.visit()`)
- [ ] `Link` uses `prefetch` on primary nav items
- [ ] Never calling `/api/*` routes from Inertia pages
- [ ] `useRemember` used for filter state persistence
- [ ] All pages have `<Head title="..." />` and explicit `React.JSX.Element` return
- [ ] Deferred props consumed via `<Suspense>` / `Inertia::defer()` where heavy data applies
- [ ] Inertia 3.0's built-in XHR client auto-includes `X-XSRF-TOKEN` — no manual CSRF logic
- [ ] Axios NOT assumed bundled (Inertia v3 dropped it): data hooks install `axios` as an explicit peer dependency OR use `useHttp` from `@inertiajs/react`
- [ ] Layout assigned via the `layout` option in `createInertiaApp()` (or `Page.layout = [Layout]` array form — never the bare arrow-component shorthand)
- [ ] `onException` / `onHttpException` / `onNetworkError` (+ `onError` for validation) wired globally in `app.tsx`
- [ ] `app.tsx` sets up `createInertiaApp` (with `@inertiajs/vite`), `QueryClientProvider`, Sileo container, and a global error boundary

**React 19 Hooks (§5.1)**

- [ ] `useOptimistic` used for optimistic delete/update feedback, always inside `React.startTransition(async () => {...})`
- [ ] `useTransition` wraps slow search/filter/export updates
- [ ] `useActionState` imported from `react` (not `react-dom`); `useFormStatus` used only inside the nearest enclosing `<form>` when action-based forms apply
- [ ] `use()` used for reading promises/context where deferred data / conditional context applies (paired with `<Suspense>`)

**React Compiler (§5.2)**

- [ ] React Compiler enabled (`babel-plugin-react-compiler` in Vite config) when React is scaffolded
- [ ] No NEW manual `useMemo`/`useCallback`/`React.memo` added purely for render performance — manual memoization only where referential stability is semantically required (e.g. TanStack Table `columns`)
- [ ] No `"use no memo"` directive without an explanatory comment
- [ ] `eslint-plugin-react-hooks` v6+ (`recommended-latest`) active — components flagged as skipped by the compiler are FAIL

**Theme Controller**

- [ ] Theme uses ONLY the `.dark` class on `<html>` — light mode = absence of class. NEVER `<body>`, NEVER `.light`
- [ ] `@custom-variant dark (&:is(.dark *));` present in `app.css`
- [ ] FOUC-killer inline script present in the root Blade view `<head>` with `nonce="{{ csp_nonce() }}"`, defaulting to `'dark'` (dark-first) and adding `.dark` only when the resolved theme is dark
- [ ] Theme persistence has a SINGLE source (Zustand `persist` for the theme preference only — no duplicated persistence layer); no auth tokens or PII persisted alongside

**TanStack Query v5 (§6) — Server State**

- [ ] All network hooks under `modules/{context}/hooks/` use `useQuery` / `useMutation` from `@tanstack/react-query`
- [ ] `queryKey` first element = entity name string, followed by filters (`["{entities}", filters]`)
- [ ] `placeholderData: keepPreviousData` on paginated queries (imported function, not the removed option)
- [ ] `queryClient.invalidateQueries({ queryKey: ["{entities}"] })` after every successful write
- [ ] `isPending` used everywhere (never `isLoading`); `gcTime` (never `cacheTime`)
- [ ] `getErrorMessage()` helper extracts the best Axios error message; Sileo `toast.success/error` on mutation outcomes
- [ ] `staleTime` configured per query (lists default to 2 minutes)
- [ ] No raw `fetch()` inside components — data access only inside hooks
- [ ] No competing server-state library present unless a documented JSDoc reason exists

**TanStack Table v8 (§7)**

- [ ] Table uses `useReactTable` from `@tanstack/react-table` — NEVER shadcn's `data-table`; shadcn `Table` primitives used ONLY for HTML rendering
- [ ] `columnHelper` defined OUTSIDE the component (module-level) and NOT in any `useMemo` deps
- [ ] `getRowId: (row) => row.uuid` provided on every table
- [ ] Server-side pagination/sort/filter — `data`/`meta.total` from the TanStack Query hook, `isPending` drives the loading state (no client-side sort/filter)
- [ ] Selection via `rowSelection` state (`RowSelectionState`) + a `select` display column
- [ ] Soft-deleted rows styled inline with `--deleted-row-*` tokens
- [ ] `DeleteConfirmModal.tsx` + `BulkDeleteConfirmModal` used — never `window.confirm()` / `alert()`
- [ ] `RestoreConfirmModal.tsx` + `BulkRestoreConfirmModal` used — never `window.confirm()` / `alert()`
- [ ] 3 action icons per active row: `Eye` (View) / `Pencil` (Edit) / `Trash2` (Delete) — each with `aria-label` + tooltip + `<PermissionGuard>` (VIEW/UPDATE/DELETE)
- [ ] 2 action icons per deleted row: `Eye` (View) / `CheckCircle` (Restore) — each with `aria-label` + tooltip + `<PermissionGuard>` (VIEW/RESTORE). Edit on a soft-deleted row is FAIL.
- [ ] All cells centered by default (`text-align: center` on `TableHead`/`TableCell`) — consistent with PDF exports

**Index Page (§8)**

- [ ] Record counter: `{meta.total} {meta.total === 1 ? 'record' : 'records'} found`
- [ ] Status filter supports: All / Active / Deleted
- [ ] Search debounced 300ms + `DataTableDateRangeFilter` with presets (Today, Yesterday, Last 7/30 days, This/Last month, This year, Custom) + Clear + inline `date_to >= date_from` validation + max-date=today
- [ ] `useRemember` (Inertia) for filter persistence; sliding paginator (5 pages around current)
- [ ] **Bulk delete AND bulk restore both wired** via `rowSelection` + `router.post` / `useMutation` against `POST /bulk-delete` / `POST /bulk-restore` (paired — shipping one without the other is FAIL)
- [ ] `DataTableBulkActions` disables bulk-delete when any selected row has `deleted_at !== null`, disables bulk-restore when any selected row has `deleted_at === null`, disables both with tooltip on mixed selection

**Export (§12.1)**

- [ ] `ExportButton` imported from `@/common/export/ExportButton`, wrapped by `useTransition`
- [ ] All active filters passed as query params via a shared param builder (no drift with the list query)
- [ ] Export opens in a new tab (`window.open(..., '_blank')`) with a loading state
- [ ] Export button placed after the date range filter in the toolbar

**shadcn/ui Primitives (§10)**

- [ ] Every UI primitive consumed via `@/shadcn/{component}` — shadcn/ui is the sole UI library
- [ ] shadcn files never hand-edited (except documented custom compositions)
- [ ] Confirmation dialogs (Delete/Restore) use the shadcn `Dialog`/`AlertDialog`-based modal — never `window.confirm()` and never a hand-rolled modal
- [ ] Side drawer / mobile sidebar uses shadcn `Sheet`
- [ ] Date range filter uses shadcn `Calendar` (`mode="range"`) inside `Popover`
- [ ] Searchable selects use shadcn `Command` / `Combobox`
- [ ] Binary toggles use shadcn `Switch`
- [ ] App-level class composition uses `cn()` from `@/common/helpers/cn` (clsx + tailwind-merge)

**Animation (§11)**

- [ ] Variants/transitions imported from `lib/motion.ts` — never inline
- [ ] No `duration > 0.4s`
- [ ] No `whileHover` scale `> 1.04`
- [ ] Never animate bg colors or font sizes via Framer Motion
- [ ] Unmounting elements wrapped with `<AnimatePresence>`

**Security (§14)**

- [ ] No `dangerouslySetInnerHTML` on untrusted input
- [ ] No `eval()` or dynamic code execution
- [ ] No tokens/PII stored in `localStorage`
- [ ] No `console.log()` of sensitive props in production
- [ ] All restricted UI wrapped in `<PermissionGuard permission="VIEW_{MODULE}">`
- [ ] `router.visit('/login')` + `queryClient.clear()` on logout
- [ ] Authorization checks remain permission-based (`permissions`, never `roles`) even if a Zustand store exists

**TypeScript (§13)**

- [ ] `PaginatedResponse<T>` interface used for all list responses
- [ ] Entity list items use `snake_case` keys: `uuid`, `created_at`, `updated_at`, `deleted_at: string | null`
- [ ] No `any` types — strict TypeScript 6 throughout
- [ ] Module `types.ts` defines all domain-specific interfaces
- [ ] Props typed via explicit interfaces; Inertia `PageProps` augmented in `types/inertia.d.ts`

**Forms (§13.1)**

- [ ] Every mutating form uses shadcn `Form` (`react-hook-form`) with `zodResolver` from `@hookform/resolvers/zod`
- [ ] Zod v4 schema in `modules/{context}/schemas/`; form type is `z.infer<typeof schema>` (never duplicated as a separate interface)
- [ ] Field errors rendered via shadcn `FormMessage` — not toasts
- [ ] No competing validators installed (`yup`, `joi`, `valibot`, `class-validator`)

**Sidebar (§9.1)**

- [ ] Every nav item with a permission wrapped in `<PermissionGuard permissions={[...]}>`
- [ ] Related modules grouped in collapsible dropdown sections
- [ ] Group open/closed state persisted (`useState` + `localStorage`); active route auto-expands its parent group
- [ ] Section labels use `text-[10px] font-semibold uppercase tracking-[1.8px]`
- [ ] Nav icons are `lucide-react` components

---

## PHASE 2 — FIX

For each ❌ FAIL, you MUST before writing the fix:

1. Call context7 to confirm the exact API for the affected library (React 19, Inertia 3.0, TanStack Query v5, TanStack Table v8, Zustand v5, shadcn/ui, `react-hook-form`, Zod v4)
2. Call tavily to search `site:ui.shadcn.com` IF the failure involves a shadcn primitive, OR to verify current React 19 / TanStack best practices if uncertain (prioritize `time_range: day | week | month`)

Then apply the minimal fix following the exact rules in `.claude/FRONTEND/SKILL.md` and the security baseline in `.claude/OWASP/SKILL.md`.
If the audit reveals missing or unnecessary global state management, prefer the simplest valid approach: local `useState` first, a Zustand store only when state must be shared beyond component boundaries, and TanStack Query for all server state.

---

## PHASE 3 — VERIFICATION CHECKLIST

After all fixes, you MUST:

1. Call context7 to re-confirm any APIs touched during Phase 2
2. Re-run EVERY item from Phase 1

Expected result:
✅ ALL items PASS
📊 Score: X/Y items — target 100% (10/10)

If any item remains ❌, repeat Phase 2 → Phase 3 until perfect score.
