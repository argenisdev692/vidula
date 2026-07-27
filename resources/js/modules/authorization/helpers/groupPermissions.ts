/**
 * Groups a flat permission catalogue by module for the role permission picker.
 *
 * Permission names follow the project convention `{ACTION}_{MODULE}` (upper snake
 * case, e.g. `VIEW_ANY_BLOG_CATEGORIES`). Both the action (`VIEW_ANY`,
 * `BULK_DELETE`, …) and the module (`BLOG_CATEGORIES`, `COMPANY_DATA`, …) can
 * contain underscores, so we match against a known, longest-first action prefix
 * list rather than naively splitting on `_`. Anything that doesn't match a known
 * action falls back to an "Other" group keyed by the full name.
 */

/** Known action prefixes, longest-first so `VIEW_ANY` wins over `VIEW`. */
const ACTION_PREFIXES: readonly string[] = [
    'VIEW_ANY',
    'BULK_DELETE',
    'BULK_RESTORE',
    'BULK_FORCE_DELETE',
    'FORCE_DELETE',
    'ASSIGN_ROLES',
    'ASSIGN_PERMISSIONS',
    'VIEW',
    'CREATE',
    'UPDATE',
    'DELETE',
    'RESTORE',
    'EXPORT',
    'DOWNLOAD',
    'PUBLISH',
    'RUN',
];

/** A single permission split into its human-facing action + full name. */
export interface PermissionEntry {
    /** The raw permission name synced to the backend. */
    name: string;
    /** The action label (e.g. "View any"), title-cased for display. */
    action: string;
}

/** A module bucket of related permissions. */
export interface PermissionGroup {
    /** The module key (e.g. "BLOG_CATEGORIES"). */
    module: string;
    /** The module label (e.g. "Blog categories"), title-cased for display. */
    label: string;
    entries: PermissionEntry[];
}

/** Title-cases an upper-snake token: `VIEW_ANY` → `View any`. */
function humanize(token: string): string {
    const lower = token.toLowerCase().replace(/_/g, ' ');
    return lower.charAt(0).toUpperCase() + lower.slice(1);
}

/** Splits a permission name into `{ action, module }` using the known prefixes. */
function splitPermission(name: string): { action: string; module: string } {
    for (const prefix of ACTION_PREFIXES) {
        if (name === prefix) {
            return { action: prefix, module: 'GENERAL' };
        }
        if (name.startsWith(`${prefix}_`)) {
            return { action: prefix, module: name.slice(prefix.length + 1) };
        }
    }
    return { action: name, module: 'OTHER' };
}

/**
 * Groups the given permission names into module buckets, each sorted so its
 * actions read in a predictable order. Groups are returned alphabetically by
 * label.
 */
export function groupPermissions(names: readonly string[]): PermissionGroup[] {
    const buckets = new Map<string, PermissionEntry[]>();

    for (const name of names) {
        const { action, module } = splitPermission(name);
        const entry: PermissionEntry = { name, action: humanize(action) };
        const existing = buckets.get(module);
        if (existing) {
            existing.push(entry);
        } else {
            buckets.set(module, [entry]);
        }
    }

    return [...buckets.entries()]
        .map(([module, entries]) => ({
            module,
            label: humanize(module),
            entries: entries.sort((a, b) => a.name.localeCompare(b.name)),
        }))
        .sort((a, b) => a.label.localeCompare(b.label));
}
