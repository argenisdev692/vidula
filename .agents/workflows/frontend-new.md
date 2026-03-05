---
description: Generates a React 19 + Inertia 2.0 + TanStack CRUD module following tokens, a11y, architecture & security rules. 5-line summary.
---

---

## description: Generates a React 19 + Inertia 2.0 + TanStack CRUD module following tokens, a11y, architecture & security rules. 5-line summary.

# FRONTEND NEW MODULE AGENT — React 19 + Inertia 2.0 + TanStack + Tailwind v4

## PHASE 1 — PLAN NEW CRUD OR MODULE (produce checklist)

Before writing any code, you MUST:

1. Call context7 to resolve current docs for: React 19, TanStack Query v5, TanStack Table v8, Inertia.js 2.0, shadcn/ui, Tailwind CSS v4
2. Call tavily to verify latest stable versions of all frontend packages and search site:ui.shadcn.com for every component to be used in this module
3. USE SEQUENTIAL THINKING TO REASON HARD about the module structure, token usage, layer boundaries, table shape, hook selection, permission guards, animation constraints, and TypeScript interfaces — before generating a single file

Then generate the indicated module following these rules.
For each generated item mark ✅ DONE, ❌ SKIPPED (with reason) or ⚠️ WARN.

### Required Generation Checklist

**Styles & Tokens (§0, §1)**

- [ ] Zero hex values or Tailwind color names in components — only `var(--token)`
- [ ] No `bg-red-600`, `text-gray-500`, or `bg-[#hex]` anywhere
- [ ] All new tokens added to `globals.css` FIRST before use
- [ ] Soft-deleted rows use `--deleted-row-bg`, `--deleted-row-border`, `--deleted-row-opacity`
- [ ] Badge variants use `color-mix(in srgb, var(--accent-*) 15%, transparent)`
- [ ] Cards use `.card` class / `var(--bg-card)` + `var(--border-default)`
- [ ] Buttons use `.btn-primary` / `.btn-ghost` — no inline bg styles
- [ ] `[data-sileo-container]` configured in `globals.css` with design tokens
- [ ] `[data-theme="light"]` override block present in `globals.css`

**Accessibility (§2)**

- [ ] `@media (prefers-reduced-motion: reduce)` present in `globals.css`
- [ ] `:focus-visible` ring uses `var(--accent-primary)`, not `:focus`
- [ ] Icon-only buttons have `aria-label` or `title`
- [ ] Modals close on `Escape`, confirm button receives auto-focus
- [ ] No element flashes > 3×/sec — all animation `duration ≤ 0.4s`
- [ ] Labels use `--text-secondary`, never `--text-disabled`

**Architecture / Layer Rules (§3)**

- [ ] `common/` imports nothing from `modules/` or `pages/`
- [ ] `modules/` imports nothing from `pages/`
- [ ] `pages/` is the ONLY layer using `usePage()` and `useForm()`
- [ ] `shadcn/` files are CLI-generated — no manual edits
- [ ] File naming: components `PascalCase.tsx`, hooks `camelCase.ts`, dirs `kebab-case/`
- [ ] Inertia pages named `{Module}IndexPage.tsx`, `{Entity}ShowPage.tsx`, etc.

**Inertia 2.0 (§5)**

- [ ] `Link` + `router` from `@inertiajs/react` (never `Inertia.visit()`)
- [ ] `Link` uses `prefetch` on nav items
- [ ] Never calling `/api/*` routes from Inertia pages
- [ ] `useRemember` used for filter state persistence
- [ ] All pages have `export default` + `<Head title="..." />`

**TanStack Query v5 (§6)**

- [ ] `isPending` used — NEVER `isLoading`
- [ ] `placeholderData: keepPreviousData` on all paginated queries
- [ ] `queryKey` first element is the entity name string
- [ ] `queryClient.clear()` called on logout
- [ ] Mutations use `onSuccess` → `toast.success()` + `queryClient.invalidateQueries()`
- [ ] Mutations use `onError` → `toast.error()`

**TanStack Table v8 (§7)**

- [ ] `columnHelper` defined OUTSIDE the component (not in `useMemo`)
- [ ] `getRowId: (row) => row.uuid` on every table instance
- [ ] `DeleteConfirmModal` used — never `window.confirm()`
- [ ] `RestoreConfirmModal` used — never `window.confirm()`
- [ ] 3 action icons per active row: `Eye` / `Pencil` / `Trash2`
- [ ] 2 action icons per deleted row: `Eye` / `CheckCircle`
- [ ] Date columns use `formatDateShort()`
- [ ] Record counter: `{count} {count === 1 ? 'record' : 'records'} found`
- [ ] Status filter supports: All / Active / Deleted
- [ ] Bulk delete via `rowSelection` state + `router.post`
- [ ] Sliding paginator shows 5 pages around current

**React 19 Hooks (§6)**

- [ ] `useTransition` wraps search, filter, and export triggers
- [ ] `useOptimistic` inside `React.startTransition(async () => {...})`
- [ ] No legacy patterns (`useEffect` for data fetching — use TanStack Query)

**Framer Motion (§11)**

- [ ] All variants/transitions imported from `lib/motion.ts` — never inline
- [ ] No `duration > 0.4s`
- [ ] No `whileHover` scale `> 1.04`
- [ ] `AnimatePresence` used on all unmounting animations
- [ ] No Framer Motion animating bg colors or font sizes (use CSS transitions)

**Security (§14)**

- [ ] No `dangerouslySetInnerHTML` anywhere
- [ ] No `eval()` or dynamic code execution
- [ ] No tokens/PII stored in `localStorage`
- [ ] No `console.log()` of sensitive props
- [ ] All restricted UI wrapped in `<PermissionGuard permission="VIEW_{MODULE}">`
- [ ] `router.visit('/login')` + `queryClient.clear()` on logout

**TypeScript (§13)**

- [ ] `PaginatedResponse<T>` interface used for all list responses
- [ ] Entity list items include `uuid`, `deletedAt: string | null`
- [ ] No `any` types — strict TypeScript 5 throughout
- [ ] Module `types.ts` defines all domain-specific interfaces

**Sidebar (§9.1)**

- [ ] Every nav item with permission wrapped in `<PermissionGuard>`
- [ ] Related modules grouped in collapsible sections
- [ ] Group open/closed state persisted via `localStorage`
- [ ] Active route auto-expands its parent group
- [ ] Section labels use `text-[10px] font-semibold uppercase tracking-[1.8px]`

---

## PHASE 2 — GENERATE

For each item in the checklist: generate the file following the exact rules in FRONTEND-REACT.md.
For each file that involves a shadcn component, call tavily to search site:ui.shadcn.com BEFORE writing it.
For each file that involves layer boundaries, hook selection, or permission logic, USE SEQUENTIAL THINKING.
Use context7 to confirm the exact API for every library touched.

---

## PHASE 3 — VERIFICATION

After all files are generated, you MUST:

1. Call context7 to re-confirm any APIs touched during Phase 2
2. Re-run EVERY item from Phase 1. Expected result:

✅ ALL items PASS
📊 Score: X/Y items — target 100%

If any item remains ❌, repeat Phase 2 → Phase 3 until perfect score.
Then respond with EXACTLY 5 lines — no extra output, no file listing, no explanation:

```
✅ Module {Name} generated — {N} files across pages / modules / common.
🎨 Tokens: all via var(--token) · globals.css updated · light theme override present.
🔐 PermissionGuard applied: VIEW_{X}, CREATE_{X}, UPDATE_{X}, DELETE_{X}.
🧪 Table: columnHelper outside component · uuid rowId · Delete/RestoreConfirmModal · sliding paginator.
📊 {score}/{total} rules passed · Sequential thinking applied on {N} reasoning steps.
```
