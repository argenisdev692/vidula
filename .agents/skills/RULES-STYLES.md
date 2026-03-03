# RULES-STYLES.md — Dark Developer UI · Design System (2026)

> Stack: React 19 · Tailwind CSS v4 · shadcn/ui (latest, Dec 2025) · Framer Motion · Sonner (toasts)
>
> Style based on the aesthetics of tools like VS Code, Linear, Raycast, and Vercel. Minimalist, high contrast, developer-oriented. This document defines the tokens, patterns, components, and rules to reproduce it in any project.

---

## 0. Token-First Principle — ABSOLUTE RULE

**Never use hex values, Tailwind color names like `bg-red-600`, or `bg-[#hex]` directly in components. All colors must come from CSS custom properties defined in `globals.css`.**

```css
/* ✅ Correct */
background: var(--bg-card);
color: var(--text-muted);
border: 1px solid var(--border-default);

/* ❌ Incorrect — NEVER DO THIS */
background: #1a1a2e;
background-color: bg-red-600;
color: #9ca3af;
background: bg-[#1a1a2e];
```

For Tailwind, all tokens from `globals.css` must be mapped in `tailwind.config.js` under `theme.extend`. Always use the resulting utility classes — never arbitrary values like `bg-[#1a1a2e]`.

> **Before implementing any component**, read the project's `globals.css` file and use the available tokens. If a token does not exist, add it to the global file before using it in a component.

---

## 1. Token Structure in `globals.css`

The `globals.css` (or `app.css` in Laravel/Inertia) must expose at minimum the following token groups:

### Backgrounds (darkest → lightest)

```css
--bg-app        /* Base application background */
--bg-surface    /* Main containers, sidebars */
--bg-card       /* Cards, inputs, code blocks */
--bg-hover      /* Hover state for interactive elements */
```

### Borders

```css
--border-subtle   /* Nearly invisible, dividers */
--border-default  /* Cards and containers */
--border-hover    /* On interaction */
```

### Text

```css
--text-primary    /* Main content */
--text-secondary  /* Subtitles, supporting text */
--text-muted      /* Descriptions, labels, placeholders */
--text-disabled   /* Metadata, inactive text */
```

### Accent Colors

```css
--accent-primary   /* CTAs, active elements */
--accent-secondary /* Secondary accent, callouts */
--accent-success   /* Success, positive states */
--accent-warning   /* Warnings, notes */
--accent-error     /* Errors, alerts, deleted rows */
--accent-info      /* Information, links */
```

### Typography, Radii, Transitions

```css
--font-sans      /* Inter, sans-serif */
--font-mono      /* JetBrains Mono, monospace */
--radius-sm      /* 6px — chips, inline buttons */
--radius-md      /* 8px — standard buttons, inputs */
--radius-lg      /* 12px — cards, modals */
--transition     /* 0.2s ease — hover/active */
```

### Form Tokens

```css
--input-bg: var(--bg-card);
--input-bg-disabled: color-mix(in srgb, var(--bg-card) 50%, var(--bg-app));
--input-border: var(--border-default);
--input-border-hover: var(--border-hover);
--input-border-focus: var(--accent-primary);
--input-border-error: var(--accent-error);
--input-border-success: var(--accent-success);
--input-text: var(--text-primary);
--input-placeholder: var(--text-muted);
--input-label: var(--text-secondary);
--input-helper: var(--text-muted);
--input-error-text: var(--accent-error);
--input-height: 40px;
--input-padding-x: 12px;
--input-font-size: 14px;
--input-radius: var(--radius-md);
```

---

## 2. Theme Architecture — Dark Default, Light Ready

This design system **defaults to dark mode**. Every enterprise product shipping in 2026 must plan for light mode from day one. All color values live in `:root` and are overridden via `[data-theme="light"]`.

### Default dark theme

```css
:root {
    color-scheme: light dark;

    /* ── backgrounds ── */
    --bg-app:     #0a0a1a;
    --bg-surface: #12122a;
    --bg-card:    #1a1a3e;
    --bg-hover:   #252550;

    /* ── borders ── */
    --border-subtle:  rgba(255, 255, 255, 0.06);
    --border-default: rgba(255, 255, 255, 0.1);
    --border-hover:   rgba(255, 255, 255, 0.18);

    /* ── text ── */
    --text-primary:   #e8e8ed;
    --text-secondary: #b0b0c0;
    --text-muted:     #7a7a90;
    --text-disabled:  #4a4a5e;

    /* ── accents ── */
    --accent-primary:   #6366f1;
    --accent-secondary: #a78bfa;
    --accent-success:   #22c55e;
    --accent-warning:   #f59e0b;
    --accent-error:     #ef4444;
    --accent-info:      #38bdf8;

    /* ── typography ── */
    --font-sans: "Inter", sans-serif;
    --font-mono: "JetBrains Mono", monospace;

    /* ── radii ── */
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;

    /* ── transition ── */
    --transition: 0.2s ease;
}

body {
    background: var(--bg-app);
    color: var(--text-primary);
    font-family: var(--font-sans);
}
```

### Light mode override

```css
[data-theme="light"] {
    --bg-app:     #f8f8fc;
    --bg-surface: #ffffff;
    --bg-card:    #f1f1f6;
    --bg-hover:   #e8e8f0;

    --border-subtle:  rgba(0, 0, 0, 0.05);
    --border-default: rgba(0, 0, 0, 0.1);
    --border-hover:   rgba(0, 0, 0, 0.18);

    --text-primary:   #1a1a2e;
    --text-secondary: #3a3a52;
    --text-muted:     #6a6a82;
    --text-disabled:  #9a9ab0;

    --accent-primary:   #4f46e5;
    --accent-secondary: #7c3aed;
    --accent-success:   #16a34a;
    --accent-warning:   #d97706;
    --accent-error:     #dc2626;
    --accent-info:      #0284c7;
}
```

### System preference fallback

```css
@media (prefers-color-scheme: light) {
    :root:not([data-theme]) {
        /* same values as [data-theme="light"] block */
    }
}
```

### Theme toggle (TypeScript)

```ts
// Stored in localStorage, applied to <html> before first paint (place in <head>)
function setTheme(mode: "light" | "dark" | "system") {
    const root = document.documentElement;
    if (mode === "system") {
        root.removeAttribute("data-theme");
        localStorage.removeItem("theme");
    } else {
        root.setAttribute("data-theme", mode);
        localStorage.setItem("theme", mode);
    }
}

// On load
const saved = localStorage.getItem("theme") as "light" | "dark" | null;
if (saved) document.documentElement.setAttribute("data-theme", saved);
```

---

## 3. Tailwind v4 Mapping

With Tailwind v4 (used in Laravel 12 starter kits), map all CSS tokens in `tailwind.config.js`:

```js
// tailwind.config.js
export default {
    theme: {
        extend: {
            colors: {
                "bg-app":     "var(--bg-app)",
                "bg-surface": "var(--bg-surface)",
                "bg-card":    "var(--bg-card)",
                "bg-hover":   "var(--bg-hover)",
                "border-subtle":  "var(--border-subtle)",
                "border-default": "var(--border-default)",
                "border-hover":   "var(--border-hover)",
                "text-primary":   "var(--text-primary)",
                "text-secondary": "var(--text-secondary)",
                "text-muted":     "var(--text-muted)",
                "text-disabled":  "var(--text-disabled)",
                "accent-primary":   "var(--accent-primary)",
                "accent-secondary": "var(--accent-secondary)",
                "accent-success":   "var(--accent-success)",
                "accent-warning":   "var(--accent-warning)",
                "accent-error":     "var(--accent-error)",
                "accent-info":      "var(--accent-info)",
            },
            fontFamily: {
                sans: ["var(--font-sans)"],
                mono: ["var(--font-mono)"],
            },
            borderRadius: {
                sm: "var(--radius-sm)",
                md: "var(--radius-md)",
                lg: "var(--radius-lg)",
            },
        },
    },
};
```

This allows writing `bg-bg-card`, `text-text-muted`, `border-border-default` as Tailwind classes, or simply using `className="bg-[var(--bg-card)]"` as a last resort. The preferred approach is always `var()` in CSS or the mapped class.

---

## 4. Accessibility — WCAG 2.2 AA (Mandatory)

### `:focus-visible`

```css
:focus { outline: none; }

:focus-visible {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
    border-radius: var(--radius-sm);
}
```

Focus ring must achieve ≥ 3:1 contrast ratio against adjacent background. Never remove `:focus-visible` without a visible alternative.

### `prefers-reduced-motion`

```css
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
```

### Minimum target size — 24 × 24 CSS px (WCAG 2.5.8)

```css
.tap-target {
    position: relative;
    min-width: 24px;
    min-height: 24px;
}
.tap-target::after {
    content: "";
    position: absolute;
    inset: -4px;
}
```

---

## 5. Typography Scale

```
Main heading        22px  weight 800  letter-spacing: -0.5px
Section heading     18px  weight 700
Subheading          14px  weight 600
Body text           14px  weight 400  line-height: 1.8
Small text          12px  weight 500
Label / tag         11px  weight 600  text-transform: uppercase  letter-spacing: 1.5px
```

Monospace (`--font-mono`) is used exclusively for: code blocks, inline snippets, file paths, technical variables and values, OTP inputs.

```css
.label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-disabled);
    text-transform: uppercase;
    letter-spacing: 1.5px;
}
```

---

## 6. Core Components

### Cards

```css
.card {
    background: var(--bg-card);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
    padding: 16px;
}
```

No box-shadow. The subtle border is sufficient.

### Buttons

```css
/* Primary */
.btn-primary {
    background: var(--accent-primary);
    color: var(--text-primary);
    border: none;
    border-radius: var(--radius-md);
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}
.btn-primary:hover { filter: brightness(0.88); }

/* Ghost */
.btn-ghost {
    background: transparent;
    color: var(--text-muted);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
}
.btn-ghost:hover {
    background: var(--bg-hover);
    color: var(--text-secondary);
    border-color: var(--border-hover);
}
```

### Badges / Tags

```css
/* accent tints via color-mix() */
.badge-primary {
    background: color-mix(in srgb, var(--accent-primary) 15%, transparent);
    color: var(--accent-primary);
    border: 1px solid color-mix(in srgb, var(--accent-primary) 35%, transparent);
}
.badge-success {
    background: color-mix(in srgb, var(--accent-success) 15%, transparent);
    color: var(--accent-success);
    border: 1px solid color-mix(in srgb, var(--accent-success) 35%, transparent);
}
.badge-error {
    background: color-mix(in srgb, var(--accent-error) 15%, transparent);
    color: var(--accent-error);
    border: 1px solid color-mix(in srgb, var(--accent-error) 35%, transparent);
}
/* base */
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    border: 1px solid;
}
```

### Input

```css
.input {
    width: 100%;
    height: var(--input-height);
    padding: 0 var(--input-padding-x);
    background: var(--input-bg);
    color: var(--input-text);
    border: 1px solid var(--input-border);
    border-radius: var(--input-radius);
    font-size: var(--input-font-size);
    font-family: var(--font-sans);
    transition: var(--transition);
}
.input::placeholder     { color: var(--input-placeholder); }
.input:hover:not(:disabled) { border-color: var(--input-border-hover); }
.input:focus-visible {
    border-color: var(--input-border-focus);
    outline: 2px solid color-mix(in srgb, var(--input-border-focus) 25%, transparent);
    outline-offset: 1px;
}
.input--error   { border-color: var(--input-border-error); }
.input--success { border-color: var(--input-border-success); }
.input:disabled {
    background: var(--input-bg-disabled);
    color: var(--text-disabled);
    cursor: not-allowed;
    opacity: 0.6;
}
```

---

## 7. shadcn/ui — Rules and Installation (2025+)

### Install command

```bash
# For new projects
npx shadcn@latest create

# Add individual components
npx shadcn@latest add button
npx shadcn@latest add dialog
npx shadcn@latest add table
npx shadcn@latest add badge
npx shadcn@latest add input
npx shadcn@latest add select
npx shadcn@latest add calendar
npx shadcn@latest add popover
npx shadcn@latest add dropdown-menu
npx shadcn@latest add toast
npx shadcn@latest add avatar
npx shadcn@latest add separator
npx shadcn@latest add skeleton
npx shadcn@latest add tooltip
npx shadcn@latest add sheet
npx shadcn@latest add card
npx shadcn@latest add form
npx shadcn@latest add checkbox
npx shadcn@latest add radio-group
npx shadcn@latest add switch
npx shadcn@latest add tabs
npx shadcn@latest add pagination
npx shadcn@latest add breadcrumb
npx shadcn@latest add scroll-area
npx shadcn@latest add command
npx shadcn@latest add alert
npx shadcn@latest add progress
npx shadcn@latest add sidebar  # new Oct 2024
npx shadcn@latest add field    # new Oct 2025 — replaces manual label+input+error patterns
npx shadcn@latest add input-group   # new Oct 2025
npx shadcn@latest add button-group  # new Oct 2025
npx shadcn@latest add empty         # new Oct 2025 — empty state pattern
npx shadcn@latest add kbd           # new Oct 2025
npx shadcn@latest add item          # new Oct 2025 — list items / cards
```

### Rules for shadcn/ui components

- **NEVER hand-edit** files in `shadcn/` — regenerate via CLI.
- If a component doesn't exist in shadcn/ui, check the [shadcn registry](https://ui.shadcn.com/registry) or use **Tavily** to search `site:ui.shadcn.com` before creating custom ones.
- Wrap shadcn components in `common/` abstractions when the raw API is too verbose for application code.
- **Never import shadcn components from `components/ui/` directly in pages** — always go through `common/` wrappers.
- shadcn/ui uses **Tailwind v4** by default since Feb 2025. Ensure your config matches.
- shadcn/ui now supports both **Radix UI** and **Base UI** as primitives — Radix is the default; use Base UI only when explicitly required.

### New components (Oct 2025) — mandatory for all new CRUD pages

| Component | Purpose | Install |
|-----------|---------|---------|
| `Spinner` | Loading indicator inside buttons and page states | `npx shadcn@latest add spinner` |
| `Field` | Wraps label + input + helper + error — use in ALL forms | `npx shadcn@latest add field` |
| `InputGroup` | Input with leading/trailing icons or buttons | `npx shadcn@latest add input-group` |
| `ButtonGroup` | Grouped actions, split buttons | `npx shadcn@latest add button-group` |
| `Empty` | Empty state pattern with icon + title + description + action | `npx shadcn@latest add empty` |
| `Item` | List item / card primitive with media + content | `npx shadcn@latest add item` |
| `Kbd` | Keyboard shortcut display | `npx shadcn@latest add kbd` |

### What shadcn/ui does NOT include — use Tavily to find alternatives

If you need a component that does not exist in shadcn/ui, search with Tavily before creating custom code:

- Date pickers with range selection → shadcn `Calendar` + `Popover` (already in library)
- Rich text editors → search `shadcn rich text editor registry`
- Drag-and-drop → `@dnd-kit/core` alongside shadcn primitives
- Data visualization / charts → `shadcn charts` (Recharts-based, in registry since 2024)
- File upload → `shadcn file-upload` in community registry

---

## 8. CRUD Table Rules — Every Index Page

### Total records counter

Every CRUD index page **MUST** display a total records count. Place it above or below the table:

```tsx
{/* ✅ Required in every IndexPage */}
<p className="text-sm" style={{ color: "var(--text-muted)" }}>
    {data?.meta.total ?? 0} total records
</p>
```

### Three action icons per row (mandatory pattern)

Every table row **MUST** show exactly these three icons (conditionally based on soft-delete state):

| State | Icons shown |
|-------|-------------|
| Active row | Eye (View) + Pencil (Edit) + Trash2 (Delete) |
| Soft-deleted row | Eye (View) + CheckCircle (Restore) |

```tsx
import { Eye, Pencil, Trash2, CheckCircle } from "lucide-react";

// In column definition:
cell: ({ row }) => {
    const isDeleted = !!row.original.deleted_at;
    return (
        <div className="flex items-center gap-1.5">
            {/* View — always visible */}
            <Link href={`/${module}/${row.original.uuid}`}>
                <Eye size={14} />
            </Link>

            {!isDeleted ? (
                <>
                    {/* Edit — active only */}
                    <Link href={`/${module}/${row.original.uuid}/edit`}>
                        <Pencil size={14} />
                    </Link>
                    {/* Delete — triggers modal, active only */}
                    <button onClick={() => onDeleteClick(row.original.uuid, row.original.name)}>
                        <Trash2 size={14} />
                    </button>
                </>
            ) : (
                {/* Restore — deleted only */}
                <button onClick={() => onRestoreClick(row.original.uuid, row.original.name)}>
                    <CheckCircle size={14} />
                </button>
            )}
        </div>
    );
}
```

### Soft-deleted row styling

Rows where `deleted_at` is truthy **MUST** be visually distinct using token-based styling — **never** `bg-red-600` or hex:

```css
/* In globals.css */
--deleted-row-bg: color-mix(in srgb, var(--accent-error) 8%, var(--bg-card));
--deleted-row-border: color-mix(in srgb, var(--accent-error) 25%, transparent);
--deleted-row-opacity: 0.65;
```

```tsx
// In DataTable row rendering:
<tr
    style={row.original.deleted_at ? {
        background: "var(--deleted-row-bg)",
        opacity: "var(--deleted-row-opacity)",
        borderLeft: "2px solid var(--deleted-row-border)",
    } : undefined}
>
```

### Delete modal — confirm before soft delete

**Never use `window.confirm()`**. Use the `DeleteConfirmModal` component:

```tsx
// common/data-table/DeleteConfirmModal.tsx
// Shows: entity name + email (or key identifier) before confirming soft delete
<DeleteConfirmModal
    isOpen={!!pendingDelete}
    entityLabel="user"
    entityName={pendingDelete?.name}
    entityIdentifier={pendingDelete?.email}
    onConfirm={() => handleDelete(pendingDelete.uuid)}
    onCancel={() => setPendingDelete(null)}
    isPending={deleteUser.isPending}
/>
```

The modal must display: what type of entity is being deleted, the entity's name/title, and a secondary identifier (email, ID, etc.). It uses `Dialog` from shadcn/ui.

### Restore modal — confirm before restore

Mirror of the delete modal, for restoring soft-deleted records:

```tsx
<RestoreConfirmModal
    isOpen={!!pendingRestore}
    entityLabel="user"
    entityName={pendingRestore?.name}
    entityIdentifier={pendingRestore?.email}
    onConfirm={() => handleRestore(pendingRestore.uuid)}
    onCancel={() => setPendingRestore(null)}
    isPending={restoreUser.isPending}
/>
```

---

## 9. Framer Motion Rules

Framer Motion lives exclusively in the web layer. **Never install in shared packages or backend.**

All `variants` and `transition` presets must be defined in `lib/motion.ts`:

```ts
// lib/motion.ts
import { useReducedMotion } from "framer-motion";

export const transitions = {
    default: { duration: 0.2, ease: "easeOut" },
    smooth:  { duration: 0.35, ease: [0.4, 0, 0.2, 1] },
    spring:  { type: "spring", stiffness: 300, damping: 30 },
    list:    { duration: 0.2, ease: "easeOut" },
} as const;

export const variants = {
    fadeIn:   { hidden: { opacity: 0 }, visible: { opacity: 1 } },
    slideUp:  { hidden: { opacity: 0, y: 8 }, visible: { opacity: 1, y: 0 } },
    slideDown:{ hidden: { opacity: 0, y: -8 }, visible: { opacity: 1, y: 0 } },
    scaleIn:  { hidden: { opacity: 0, scale: 0.96 }, visible: { opacity: 1, scale: 1 } },
    listItem: { hidden: { opacity: 0, x: -6 }, visible: { opacity: 1, x: 0 } },
} as const;

export function useSafeTransition(transition: object) {
    const reduce = useReducedMotion();
    return reduce ? { duration: 0 } : transition;
}
```

### Usage patterns

**Modals / Drawers:**
```tsx
<AnimatePresence>
    {isOpen && (
        <motion.div
            variants={variants.scaleIn}
            initial="hidden" animate="visible"
            exit={{ opacity: 0, scale: 0.96 }}
            transition={transitions.default}
        />
    )}
</AnimatePresence>
```

**Lists:**
```tsx
<motion.ul
    variants={{ visible: { transition: { staggerChildren: 0.06 } } }}
    initial="hidden" animate="visible"
>
    {items.map(item => (
        <motion.li key={item.id} variants={variants.listItem} transition={transitions.list} layout>
            {item.label}
        </motion.li>
    ))}
</motion.ul>
```

**Micro-interactions:**
```tsx
<motion.button
    whileHover={{ scale: 1.02 }}
    whileTap={{ scale: 0.97 }}
    transition={transitions.spring}
/>
```

### Hard rules

- **Never animate:** background colors, font sizes, border widths, shadows via Framer Motion — use CSS `var(--transition)` for those.
- **Never use:** `duration` above `0.4s`.
- **Never use:** `whileHover` scale above `1.04` or below `0.95`.
- **Always use `AnimatePresence`** when a component can unmount.
- **Never define inline variants** inside JSX — always import from `lib/motion.ts`.
- Use `'use client'` in any component using Framer Motion (Next.js / Inertia SSR).

---

## 10. Toast Notifications — Sileo React

Use **Sileo React** (`sileo`) for all toast notifications. Do not use Sonner, react-hot-toast, or any other toast library.

```bash
npm install sileo
```

```tsx
// In root layout — place once
import { SileoProvider } from "sileo";
<SileoProvider position="bottom-right" theme="dark" />
```

```tsx
// Usage in components
import { toast } from "sileo";

toast.success("User created successfully");
toast.error("Failed to delete user");
toast.warning("This action cannot be undone");
toast.info("Syncing data...");
toast.loading("Exporting PDF...");
```

Configure Sileo to use design tokens in `globals.css`:

```css
/* Override Sileo default vars to match design system tokens */
[data-sileo-container] {
    --sileo-bg:            var(--bg-card);
    --sileo-border:        var(--border-default);
    --sileo-text:          var(--text-primary);
    --sileo-success-color: var(--accent-success);
    --sileo-error-color:   var(--accent-error);
    --sileo-warning-color: var(--accent-warning);
    --sileo-info-color:    var(--accent-info);
    font-family:           var(--font-sans);
    font-size:             13px;
}
```

---

## 11. Sidebar Layout Pattern

Use shadcn/ui `Sidebar` component (added Aug 2024, stable for 2025+ projects):

```bash
npx shadcn@latest add sidebar
```

The sidebar is always token-based:

```css
.sidebar {
    background: var(--bg-surface);
    border-right: 1px solid var(--border-subtle);
    width: 240px;
}
.sidebar-item:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
}
.sidebar-item--active {
    background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
    color: var(--accent-primary);
    border-right: 2px solid var(--accent-primary);
}
```

---

## 12. Code Review Checklist — Styles

- [ ] No hex values in component files — only `var(--token)` references
- [ ] No `bg-red-600`, `text-gray-500`, or similar Tailwind color names in components (use mapped tokens)
- [ ] No `bg-[#hex]` arbitrary values
- [ ] All new components read `globals.css` tokens before being implemented
- [ ] New tokens added to `globals.css` AND mapped in `tailwind.config.js`
- [ ] `[data-theme="light"]` block updated when new tokens are added
- [ ] Deleted rows use `--deleted-row-bg` / `--deleted-row-opacity` tokens — not hardcoded colors
- [ ] Focus rings use `var(--accent-primary)` with `outline-offset: 2px`
- [ ] `prefers-reduced-motion` respected in all animations
- [ ] Toast notifications use Sileo React with design tokens applied
- [ ] shadcn components installed via CLI, never hand-edited
- [ ] Missing shadcn components searched via Tavily before building custom
