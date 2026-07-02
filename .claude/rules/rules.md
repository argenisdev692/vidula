---
trigger: always_on
---

# [ABSOLUTE] Non-negotiable constraints — ALWAYS apply

- **Language:** Respond in English at all times.
- **CLI:** Use `./vendor/bin/sail artisan` — NEVER bare `php`.
- **PHP 8.5:** Follow `.claude/BACKEND-PHP/SKILL.md` §0–§3 — SINGLE SOURCE OF TRUTH for PHP 8.5 syntax.
- **TypeScript:** Strict mode enforced on ALL `.tsx` / `.ts` files.
- **Security baseline:** `.claude/OWASP/SKILL.md` is **always-on**. Every backend and frontend change MUST satisfy its 15 baseline items (OWASP Top 10:**2025** + API Top 10:2023 + LLM Top 10:2025 when AI is in scope, adapted to Laravel 13 + React 19 + Inertia 3.0).
- **Context7 (MCP):** Always resolve live docs — never rely on cached training knowledge.
- **Sequential Thinking (MCP):** Use `mcp3_sequentialthinking` for ALL non-trivial tasks — architecture decisions, debugging, multi-step implementations, and any task with unclear scope. No exceptions.
- **Investigate:** Run Tavily search immediately before responding, prioritizing recent/current sources (`time_range: day`, `week`, or `month`) and official docs; avoid historical years unless the task explicitly asks for them.

# [MUST] Before writing any code — read the relevant skill

| Task type                                            | Required reading                                                |
| ---------------------------------------------------- | --------------------------------------------------------------- |
| Security baseline (any backend or frontend change)   | `.claude/OWASP/SKILL.md`                                      |
| PHP / Laravel / Backend / Business                   | `.claude/BACKEND-PHP/SKILL.md`                                |
| PHP simple CRUD / 3–8 fields                         | `.claude/skills/ARCHITECTURE-PHP/SKILL-SIMPLE-CRUD.md`        |
| React 19 / Inertia 3.0 / TanStack / Zustand / Frontend | `.claude/FRONTEND/SKILL.md`                                 |
| CSS / Styles / UI design tokens                      | `.claude/FRONTEND/SKILL.md` §0–§2, §9                         |
| PHP project structure / directory tree               | `.claude/skills/ARCHITECTURE-PHP/SKILL.md`                    |
| React / Inertia directory tree / file placement      | `.claude/skills/ARCHITECTURE-REACT/SKILL.md`                 |

> **Rule:** If a skill file covers the task, read it FIRST — no exceptions.
>
> **Solo-dev default (this project is single-developer):** the DEFAULT backend baseline is `SKILL-SIMPLE-CRUD.md` + `/backend-new-crud`. Promote to the intermediate `ARCHITECTURE-PHP/SKILL.md` ONLY when at least ONE of the following is true:
> 1. Aggregate root with domain invariants beyond simple validation (state machines, multi-step lifecycle).
> 2. ≥ 2 third-party integrations live in the module (LLM provider + payment gateway, etc.).
> 3. Cross-module orchestration with domain events that already have ≥ 1 listener.
> 4. The module has > 15 persisted fields OR composes ≥ 2 sub-entities under one aggregate.
> 5. Excel/PDF exports AND queue workers AND WebSockets co-exist in the same module.
>
> If none of the above is true, use SIMPLE-CRUD. Adding `Domain/Entities/`, `ReadRepositories/`, `AggregateRoot`, `CommandBus`, `UnitOfWork`, or the full Resilience layer to a 5-field CRUD is **overengineering** — auditors must flag it.
>
> **Total skill files:** 6 (OWASP + BACKEND-PHP + FRONTEND + 2× ARCHITECTURE-PHP + ARCHITECTURE-REACT) + this router. No redundancy.

# [MUST] CSS / Styles

- Follow `FRONTEND/SKILL.md` §0–§2 strictly.
- NEVER hardcode hex, `bg-red-600`, or `bg-[#hex]`. Use `var(--token)` only.
- All tokens defined in `resources/css/globals.css` (imported by `app.css`).

---

# [MUST] React 19 / TypeScript

- Follow `FRONTEND/SKILL.md` strictly.
- Use function components with `export default` + explicit `React.JSX.Element` return on every `.tsx` file. No class components in new code.
- No `any`. No `@ts-ignore`. No hardcoded colors in components.
- Every page uses `<Head title="..." />` and is wrapped by its layout (e.g. `<AppLayout>`). State always explicitly typed via `React.useState<T>()`, typed props interfaces, and `usePage<Props>()`.
- Prefer React 19 hooks where they fit: `useOptimistic` (optimistic UI, inside `React.startTransition`), `useTransition` (search/filter/export), `useActionState`/`useFormStatus` (forms/actions), `use()` (promises/context).
- **React Compiler (stable v1)** enabled via `babel-plugin-react-compiler` — see `FRONTEND/SKILL.md` §5.2. No new manual `useMemo`/`useCallback`/`React.memo` for pure render performance; manual memoization only where referential stability is semantically required. Lint via `eslint-plugin-react-hooks` v6+ (`recommended-latest`).
- Server state → TanStack Query v5 (`useQuery` / `useMutation`). Client state → Zustand v5 setup stores. Never mix the two.
- All UI primitives consumed through **shadcn/ui** components under `resources/js/shadcn/` (CLI-generated via `npx shadcn@latest add`, never hand-edited) — no other UI library is used. Data table = **TanStack Table v8** (`useReactTable`) with shadcn `Table` primitives for HTML rendering (NEVER shadcn's `data-table`). Toasts = **Sileo** (`toast`/`sileo`). Forms = `react-hook-form` + Zod v4. Icons = `lucide-react`. Animations = Framer Motion (variants in `lib/motion.ts`).
- Theme toggling uses ONLY the `.dark` class on `<html>` (light mode = absence of class). Never `.light`. The `dark:` variant is bound to `&:is(.dark *)` via `@custom-variant` in `app.css`. Theme persistence is a single source (Zustand `persist` for the non-sensitive theme preference only).

---

# [MUST] Laravel / PHP

- Follow `BACKEND-PHP/SKILL.md` strictly.
- No business logic in Controllers. No `php` bare CLI.
- Web routes = primary (Inertia + session). API routes = secondary (mobile/Sanctum only).
- Every input validated by `FormRequest` or Spatie `Data`. Every model declares `$fillable`.
- **N+1 prevention is mandatory** — `Model::shouldBeStrict()` enabled in `AppServiceProvider`; every list query uses `with('rel:id,fk,col')` with explicit columns; counts/sums via `withCount()`/`withSum()`/`withAvg()`; combined relation+filter via `withWhereHas()`. See `BACKEND-PHP/SKILL.md` §4.1.
- **Audit trail available**: `LogsActivity` (spatie/laravel-activitylog ^5.0) is enabled by default on every aggregate Eloquent model with explicit `logOnly([...])` + `logOnlyDirty()` + `dontSubmitEmptyLogs()`. Never `logAll()`, never log secrets/PII.
- **Bulk operations**: when the UI exposes row selection, the module ships BOTH `BulkDelete{Entity}Handler` AND `BulkRestore{Entity}Handler` (soft delete + soft restore over a UUID array), with matching `POST /bulk-delete` and `POST /bulk-restore` routes guarded by `permission:DELETE_*` / `permission:RESTORE_*`.

---

# [MUST] Security (always-on, see `.claude/OWASP/SKILL.md`)

- Authorize every route: `auth` middleware + Spatie `permission:*` or Policy.
- Frontend UI authorization uses `permissions`, never `roles`.
- UUID-bound routes use `->whereUuid('uuid')`.
- Argon2id password hashing (`config/hashing.php`).
- CSP via Spatie `laravel-csp`; security headers via `bepsvpt/secure-headers`.
- File uploads validated (MIME + size + extension); R2 access via signed URLs only.
- Spatie `LogsActivity` with explicit `logOnly([...])` — never `logAll()`, never log secrets/PII.
- `APP_DEBUG=false` in production. No stack traces, no SQL errors, no file paths leaked.

---

# [MUST] File editing & env handling

- For file administration and edits, try filesystem MCP tools first.
- If filesystem MCP does not work, use `write_to_file` only for brand-new files.
- Reserve `apply_patch` only for edits to existing files.
- For `.env` and `.env.example`, if direct modification is not possible, provide only the required environment variable keys/placeholders and continue working.

---

# [SHOULD] General quality

- Mobile-first on every UI component.
- `font-family: var(--font-sans)` everywhere.
- Prefer descriptive names over comments.
