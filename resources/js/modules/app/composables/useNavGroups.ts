import { computed, type ComputedRef } from 'vue';

/**
 * A single sidebar navigation entry. Leaf items have a `href`; group items have
 * `children`. `icon` is a primeicons class string (never a component ref).
 * `permission` is optional — when set, the item only renders for users holding it.
 */
export interface NavItem {
    icon: string;
    label: string;
    href?: string;
    permission?: string;
    children?: NavItem[];
}

/**
 * Central sidebar nav configuration. Routes are forward-looking: entries appear
 * as their modules ship. Permissions gate visibility via <PermissionGuard>.
 */
export function useNavGroups(): { navItems: ComputedRef<NavItem[]> } {
    const navItems = computed<NavItem[]>(() => [
        { icon: 'pi pi-home', label: 'Dashboard', href: '/dashboard' },
        { icon: 'pi pi-users', label: 'Users', href: '/users', permission: 'VIEW_USER' },
        {
            icon: 'pi pi-inbox',
            label: 'Leads & Support',
            children: [
                { icon: 'pi pi-calendar', label: 'Appointments', href: '/appointments' },
                { icon: 'pi pi-clock', label: 'Availability', href: '/availability' },
                { icon: 'pi pi-envelope', label: 'Contact & Support', href: '/contact-support' },
            ],
        },
        {
            icon: 'pi pi-id-card',
            label: 'CRM',
            children: [
                { icon: 'pi pi-briefcase', label: 'Clients', href: '/clients' },
                { icon: 'pi pi-graduation-cap', label: 'Students', href: '/students' },
            ],
        },
        {
            icon: 'pi pi-megaphone',
            label: 'Content & Marketing',
            children: [
                { icon: 'pi pi-tags', label: 'Blog Categories', href: '/blog-categories' },
                { icon: 'pi pi-file-edit', label: 'Posts', href: '/posts' },
                { icon: 'pi pi-share-alt', label: 'Social Media', href: '/social-media' },
                { icon: 'pi pi-video', label: 'Campaigns', href: '/campaigns' },
            ],
        },
        { icon: 'pi pi-file-export', label: 'Video Export', href: '/video-export' },
        { icon: 'pi pi-building', label: 'Company Data', href: '/company-data' },
        {
            icon: 'pi pi-cog',
            label: 'Settings',
            children: [
                { icon: 'pi pi-briefcase', label: 'Portfolio', href: '/portfolio' },
                { icon: 'pi pi-shield', label: 'Roles', href: '/roles', permission: 'VIEW_ROLE' },
                { icon: 'pi pi-database', label: 'Backups', href: '/backups', permission: 'VIEW_ANY_BACKUPS' },
                { icon: 'pi pi-history', label: 'Activity Logs', href: '/activity-logs', permission: 'VIEW_ANY_ACTIVITY_LOGS' },
            ],
        },
    ]);

    return { navItems };
}
