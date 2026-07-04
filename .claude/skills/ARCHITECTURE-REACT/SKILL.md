---
name: architecture-react-inertia
description: Directory tree and file placement rules for the React and Inertia frontend, including common, modules, pages, shadcn, and the project's structural conventions.
---

# ARCHITECTURE-REACT-INERTIA.md

# React 19 + Inertia.js 2.0 · Frontend Architecture (2026)

> Stack: React 19 · Inertia.js 2.0 · TypeScript 5 · TanStack Query v5 · TanStack Table v8 · Zustand v5 · Tailwind CSS v4 · shadcn/ui (latest)

---

## Directory Structure

```
resources/
│
├── css/
│   ├── app.css                                   # Tailwind v4 entry point (imports globals.css)
│   └── globals.css                               # Vidula design tokens — ALL custom vars go here
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
    │   │   ├── stores/                            # Zustand stores for shared client state only
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
    │       ├── stores/                            # Zustand stores — typed, selector-based, no server state
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

> **For rules, routes, layer constraints, Zustand state rules, and naming conventions** → see `FRONTEND-REACT.md` §3–§4, §6.1, §15.
> This file is the detailed directory tree ONLY.
