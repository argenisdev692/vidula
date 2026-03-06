---
description: Audits React 19 + Inertia 2.0 modules against design tokens, TanStack Query/Table v5/v8, accessibility, security and architecture rules. Auto-fixes violations and re-verifies until 100% score.
---

# FRONTEND AUDIT AGENT — React 19 + Inertia 2.0 + TanStack + Tailwind v4

## PHASE 1 — AUDIT (produce checklist)

Before starting the audit, you MUST:

1. Call context7 to resolve current docs for: React 19, TanStack Query v5,
   TanStack Table v8, Inertia.js 2.0, shadcn/ui, Tailwind CSS v4
2. Call tavily to verify latest stable versions of all frontend packages
   and search site:ui.shadcn.com for any component being audited

Then analyze the indicated module against these rules.
For each item mark ✅ PASS, ❌ FAIL (with file:line and brief description) or ⚠️ WARN.

### Required Checklist

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

## PHASE 2 — FIX

For each ❌ FAIL, you MUST before writing the fix:

1. Call context7 to confirm the exact API for the affected library
2. Call tavily to search `site:ui.shadcn.com` IF the failure involves a shadcn component,
   OR to verify current React 19 / TanStack best practices if uncertain

Then apply the minimal fix following the exact rules in FRONTEND-REACT.md.

---

## PHASE 3 — VERIFICATION CHECKLIST

After all fixes, you MUST:

1. Call context7 to re-confirm any APIs touched during Phase 2
2. Re-run EVERY item from Phase 1

Expected result:
✅ ALL items PASS
📊 Score: X/Y items — target 100% (10/10)

If any item remains ❌, repeat Phase 2 → Phase 3 until perfect score.
