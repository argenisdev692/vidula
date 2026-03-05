import * as React from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import type { AuthPageProps } from '@/types/auth';
import { PermissionGuard } from '@/modules/auth/components/PermissionGuard';

// ══════════════════════════════════════════════════════════════════
// Theme Hook
// ══════════════════════════════════════════════════════════════════
type Theme = 'dark' | 'light';

function useTheme(): [Theme, () => void] {
  const [theme, setTheme] = React.useState<Theme>(() => {
    if (typeof window === 'undefined') return 'dark';
    return (localStorage.getItem('aq-theme') as Theme) ?? 'dark';
  });

  const toggle = React.useCallback(() => {
    setTheme((prev) => {
      const next: Theme = prev === 'dark' ? 'light' : 'dark';
      localStorage.setItem('aq-theme', next);
      const root = document.documentElement;
      root.setAttribute('data-theme', next);
      next === 'dark' ? root.classList.add('dark') : root.classList.remove('dark');
      return next;
    });
  }, []);

  return [theme, toggle];
}

import {
  LayoutDashboard,
  Users,
  Building2,
  Sun,
  Moon,
  LogOut,
  Search,
  ChevronDown,
  ShieldCheck,
  Menu,
  Settings,
  ArrowLeft,
  X,
  GraduationCap,
  UserCheck,
  Package,
  BookOpen,
  FolderOpen,
} from 'lucide-react';

const icSize = 18;

const IconSun     = () => <Sun size={icSize} />;
const IconMoon    = () => <Moon size={icSize} />;
const IconLogout  = () => <LogOut size={16} />;
const IconSearch  = () => <Search size={14} />;
const IconCaret   = () => <ChevronDown size={14} />;
const IconShield  = () => <ShieldCheck size={20} className="text-white" />;
const IconMenu    = () => <Menu size={icSize} />;
const IconSettings = () => <Settings size={icSize} />;
const IconArrowLeft = () => <ArrowLeft size={16} />;
const IconClose = () => <X size={16} />;

// ══════════════════════════════════════════════════════════════════
// Nav Groups — Collapsible sections per §9.1
// ══════════════════════════════════════════════════════════════════
export interface NavItem {
  label: string;
  href: string;
  icon: React.ReactNode;
  description: string;
  permission?: string;
}

interface NavGroup {
  label: string;
  icon: React.ReactNode;
  items: NavItem[];
}

const NAV_GROUPS: NavGroup[] = [
  {
    label: 'Overview',
    icon: <LayoutDashboard size={14} />,
    items: [
      { label: 'Dashboard', href: '/dashboard', icon: <LayoutDashboard size={icSize} />, description: 'Overview & metrics' },
    ],
  },
  {
    label: 'People',
    icon: <Users size={14} />,
    items: [
      { label: 'Users', href: '/users', icon: <Users size={icSize} />, description: 'Manage system users', permission: 'VIEW ANY USERS' },
      { label: 'Students', href: '/students', icon: <GraduationCap size={icSize} />, description: 'Manage students', permission: 'VIEW ANY STUDENTS' },
      { label: 'Clients', href: '/clients', icon: <UserCheck size={icSize} />, description: 'Manage clients', permission: 'VIEW ANY CLIENTS' },
    ],
  },
  {
    label: 'Management',
    icon: <Package size={14} />,
    items: [
      { label: 'Company', href: '/company-data', icon: <Building2 size={icSize} />, description: 'Corporate entities', permission: 'VIEW_COMPANY_DATA' },
      { label: 'Products', href: '/products', icon: <Package size={icSize} />, description: 'Manage products', permission: 'VIEW ANY PRODUCTS' },
    ],
  },
  {
    label: 'Blog',
    icon: <BookOpen size={14} />,
    items: [
      { label: 'Categories', href: '/blog-categories', icon: <FolderOpen size={icSize} />, description: 'Manage blog categories', permission: 'VIEW ANY BLOG_CATEGORIES' },
    ],
  },
];

// ══════════════════════════════════════════════════════════════════
// ExpandableSearch — Desktop: expands to 320px. Mobile: full-width overlay.
// ══════════════════════════════════════════════════════════════════
function ExpandableSearch(): React.JSX.Element {
  const [expanded, setExpanded] = React.useState<boolean>(false);
  const [value, setValue] = React.useState<string>('');
  const inputRef = React.useRef<HTMLInputElement>(null);
  const mobileInputRef = React.useRef<HTMLInputElement>(null);

  function open(): void {
    setExpanded(true);
    setTimeout(() => {
      inputRef.current?.focus();
      mobileInputRef.current?.focus();
    }, 80);
  }
  function close(): void { if (!value) setExpanded(false); }
  function dismiss(): void { setValue(''); setExpanded(false); }

  return (
    <>
      {/* ── Desktop expandable (hidden on mobile) ── */}
      <div
        className="relative hidden items-center transition-all duration-300 lg:flex"
        style={{ width: expanded ? 320 : 36 }}
      >
        <button
          onClick={open}
          className="absolute left-0 z-10 flex h-9 w-9 items-center justify-center rounded-lg transition-all duration-200"
          style={{
            color: expanded ? 'var(--blue-400)' : 'var(--text-muted)',
            background: expanded ? 'color-mix(in srgb, var(--blue-500) 10%, transparent)' : 'var(--bg-elevated)',
            border: '1px solid var(--border-default)',
          }}
          aria-label="Search"
        >
          <IconSearch />
        </button>
        <input
          ref={inputRef}
          type="text"
          value={value}
          onChange={(e) => setValue(e.target.value)}
          onBlur={close}
          onKeyDown={(e) => { if (e.key === 'Escape') dismiss(); }}
          placeholder="Search..."
          className="h-9 w-full rounded-lg pl-10 pr-3 text-sm outline-none transition-all duration-300"
          style={{
            background: 'var(--bg-card)',
            border: '1px solid var(--border-default)',
            color: 'var(--text-primary)',
            fontFamily: 'var(--font-sans)',
            opacity: expanded ? 1 : 0,
            pointerEvents: expanded ? 'auto' : 'none',
          }}
        />
      </div>

      {/* ── Mobile: icon trigger (visible <lg) ── */}
      <button
        onClick={open}
        className="flex h-9 w-9 items-center justify-center rounded-lg transition-all duration-200 lg:hidden"
        style={{
          color: 'var(--text-muted)',
          background: 'var(--bg-card)',
          border: '1px solid var(--border-default)',
        }}
        aria-label="Search"
      >
        <IconSearch />
      </button>

      {/* ── Mobile: full-width overlay bar ── */}
      {expanded && (
        <div
          className="fixed inset-x-0 top-0 z-60 flex h-[60px] items-center gap-3 px-4 lg:hidden"
          style={{
            background: 'var(--bg-surface)',
            borderBottom: '1px solid var(--border-subtle)',
            boxShadow: '0 4px 20px color-mix(in srgb, #000 30%, transparent)',
          }}
        >
          <span style={{ color: 'var(--blue-400)' }}><IconSearch /></span>
          <input
            ref={mobileInputRef}
            type="text"
            value={value}
            onChange={(e) => setValue(e.target.value)}
            onKeyDown={(e) => { if (e.key === 'Escape') dismiss(); }}
            placeholder="Search..."
            className="h-9 flex-1 rounded-lg px-3 text-sm outline-none"
            style={{
              background: 'var(--bg-card)',
              border: '1px solid var(--border-default)',
              color: 'var(--text-primary)',
              fontFamily: 'var(--font-sans)',
            }}
          />
          <button
            onClick={dismiss}
            className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-all"
            style={{ color: 'var(--text-muted)', background: 'var(--bg-hover)', border: '1px solid var(--border-default)' }}
            aria-label="Close search"
          >
            <IconClose />
          </button>
        </div>
      )}
    </>
  );
}

// ══════════════════════════════════════════════════════════════════
// Avatar Dropdown — Simplified: Settings + Sign out only
// ══════════════════════════════════════════════════════════════════
function AvatarDropdown(): React.JSX.Element {
  const { auth } = usePage<AuthPageProps>().props;
  const user = auth.user;
  const [open, setOpen] = React.useState<boolean>(false);
  const ref = React.useRef<HTMLDivElement>(null);

  // Close on outside click
  React.useEffect(() => {
    function handler(e: MouseEvent): void {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    }
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  const initials = [
    (user?.name?.[0] ?? 'U').toUpperCase(),
    (user?.last_name?.[0] ?? '').toUpperCase(),
  ].join('');

  const hasPhoto = !!user?.profile_photo_path;

  return (
    <div ref={ref} className="relative">
      <button
        onClick={() => setOpen((p) => !p)}
        className="flex items-center gap-2 rounded-lg p-1 pr-2 transition-all duration-150"
        style={{
          background: open ? 'var(--bg-hover)' : 'transparent',
          border: '1px solid',
          borderColor: open ? 'var(--border-hover)' : 'var(--border-subtle)',
        }}
        aria-label="Account menu"
      >
        {/* Avatar */}
        {hasPhoto ? (
          <img
            src={user!.profile_photo_path!}
            alt={user?.name}
            className="h-7 w-7 rounded-md object-cover"
          />
        ) : (
          <div
            className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-[11px] font-bold"
            style={{
              background: 'var(--grad-primary)',
              color: '#ffffff',
            }}
          >
            {initials}
          </div>
        )}
        {/* Name */}
        <span
          className="hidden text-[12px] font-semibold sm:block"
          style={{ color: 'var(--text-secondary)', fontFamily: 'var(--font-sans)' }}
        >
          {user?.name}
        </span>
        <span style={{ color: 'var(--text-disabled)' }}><IconCaret /></span>
      </button>

      {/* Dropdown */}
      {open && (
        <div
          className="absolute right-0 top-full z-50 mt-2 w-48 rounded-xl p-1"
          style={{
            background: 'var(--bg-surface)',
            border: '1px solid var(--border-default)',
            boxShadow: '0 8px 32px color-mix(in srgb, #000 24%, transparent)',
          }}
        >
          {/* User info header */}
          <div
            className="mb-1 rounded-lg px-3 py-2.5"
            style={{ background: 'var(--bg-card)' }}
          >
            <p className="text-[13px] font-semibold truncate" style={{ color: 'var(--text-primary)' }}>
              {user?.name} {user?.last_name ?? ''}
            </p>
            <p className="text-[11px] truncate" style={{ color: 'var(--text-disabled)' }}>
              {user?.email}
            </p>
          </div>

          {/* Profile → /profile */}
          <Link
            href="/profile"
            onClick={() => setOpen(false)}
            className="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors"
            style={{ color: 'var(--text-secondary)', fontFamily: 'var(--font-sans)' }}
            onMouseEnter={(e) => { (e.currentTarget as HTMLAnchorElement).style.background = 'var(--bg-hover)'; }}
            onMouseLeave={(e) => { (e.currentTarget as HTMLAnchorElement).style.background = 'transparent'; }}
          >
            <IconSettings />
            Profile
          </Link>

          {/* Divider */}
          <div className="my-1 h-px" style={{ background: 'var(--border-subtle)' }} />

          {/* Logout */}
          <button
            onClick={() => router.post('/logout')}
            className="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors"
            style={{ color: 'var(--accent-error)', fontFamily: 'var(--font-sans)' }}
            onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = 'color-mix(in srgb, var(--accent-error) 10%, transparent)'; }}
            onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = 'transparent'; }}
          >
            <IconLogout />
            Sign out
          </button>
        </div>
      )}
    </div>
  );
}

// ══════════════════════════════════════════════════════════════════
// Theme Toggle Button (shared between desktop sidebar & mobile drawer)
// ══════════════════════════════════════════════════════════════════
function ThemeToggle({ theme, onToggle }: { theme: Theme; onToggle: () => void }): React.JSX.Element {
  return (
    <button
      onClick={onToggle}
      className="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 transition-all duration-150"
      style={{ color: 'var(--text-muted)', border: '1px solid transparent', fontFamily: 'var(--font-sans)' }}
      onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = 'var(--bg-hover)'; }}
      onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = 'transparent'; }}
    >
      <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md" style={{ background: 'var(--bg-hover)' }}>
        {theme === 'dark' ? <IconSun /> : <IconMoon />}
      </span>
      <div className="flex-1 text-left">
        <span className="block text-[13px] font-semibold leading-none"
          style={{ color: 'var(--text-secondary)', fontFamily: 'var(--font-sans)' }}>
          {theme === 'dark' ? 'Light Mode' : 'Dark Mode'}
        </span>
        <span className="block text-[11px] leading-none mt-0.5" style={{ color: 'var(--text-disabled)' }}>
          {theme === 'dark' ? 'Switch to light' : 'Switch to dark'}
        </span>
      </div>
      {/* Animated pill */}
      <span className="relative inline-flex h-5 w-9 shrink-0 rounded-full transition-colors duration-200"
        style={{
          background: theme === 'light' ? 'color-mix(in srgb, var(--blue-500) 30%, transparent)' : 'var(--bg-overlay)',
          border: '1px solid var(--border-default)',
        }}>
        <span className="inline-block h-3.5 w-3.5 rounded-full transition-transform duration-200"
          style={{
            background: theme === 'light' ? 'var(--blue-500)' : 'var(--text-disabled)',
            transform: theme === 'light' ? 'translate(18px, 2px)' : 'translate(2px, 2px)',
          }} />
      </span>
    </button>
  );
}

// ══════════════════════════════════════════════════════════════════
// Sidebar Content (shared between desktop and mobile)
// Per §9.1: collapsible groups + PermissionGuard
// ══════════════════════════════════════════════════════════════════
function SidebarContent({ onClose }: { onClose?: () => void }): React.JSX.Element {
  const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

  // Determine which groups are expanded: auto-expand if current route is inside
  const [expandedGroups, setExpandedGroups] = React.useState<Record<string, boolean>>(() => {
    const saved: Record<string, boolean> = {};
    try {
      const stored = typeof window !== 'undefined' ? localStorage.getItem('sidebar-groups') : null;
      if (stored) Object.assign(saved, JSON.parse(stored));
    } catch { /* ignore */ }

    // Auto-expand the group containing the active route
    for (const group of NAV_GROUPS) {
      const hasActiveItem = group.items.some(
        (item) => currentPath === item.href || currentPath.startsWith(item.href + '/')
      );
      if (hasActiveItem) saved[group.label] = true;
      if (saved[group.label] === undefined) saved[group.label] = group.label === 'Overview'; // Blog and others default closed
    }
    return saved;
  });

  function toggleGroup(label: string): void {
    setExpandedGroups((prev) => {
      const next = { ...prev, [label]: !prev[label] };
      try { localStorage.setItem('sidebar-groups', JSON.stringify(next)); } catch { /* ignore */ }
      return next;
    });
  }

  return (
    <>
      {/* Logo + close arrow (arrow only visible on mobile) */}
      <div className="flex h-[60px] items-center justify-between px-5 shrink-0"
        style={{ borderBottom: '1px solid var(--border-subtle)' }}>
        <div className="flex items-center gap-3">
          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
            style={{ background: 'var(--grad-primary)' }}>
            <IconShield />
          </div>
          <div>
            <span className="block text-[13px] font-bold tracking-tight leading-none"
              style={{ color: 'var(--text-primary)', fontFamily: 'var(--font-sans)' }}>
              Vidula
            </span>
            <span className="block text-[10px] font-semibold uppercase tracking-widest leading-none mt-0.5"
              style={{ color: 'var(--purple-500)' }}>
              CRM
            </span>
          </div>
        </div>

        {/* Close arrow — only rendered in mobile drawer */}
        {onClose && (
          <button
            onClick={onClose}
            className="flex h-7 w-7 items-center justify-center rounded-lg transition-all duration-150"
            style={{
              color: 'var(--text-muted)',
              background: 'var(--bg-hover)',
              border: '1px solid var(--border-default)',
            }}
            aria-label="Close menu"
          >
            <IconArrowLeft />
          </button>
        )}
      </div>

      {/* ── Nav Groups ── */}
      <nav className="flex-1 overflow-y-auto px-3 pt-4 pb-2">
        {NAV_GROUPS.map((group) => {
          const isExpanded = expandedGroups[group.label] ?? (group.label === 'Overview');
          const hasActiveChild = group.items.some(
            (item) => currentPath === item.href || currentPath.startsWith(item.href + '/')
          );

          return (
            <div key={group.label} className="mb-2">
              {/* ── Group Header (clickable toggle) ── */}
              <button
                onClick={() => toggleGroup(group.label)}
                className="flex w-full items-center gap-2 rounded-lg px-3 py-2 transition-all duration-150"
                style={{
                  color: hasActiveChild ? 'var(--text-primary)' : 'var(--text-secondary)',
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  fontFamily: 'var(--font-sans)',
                }}
                aria-expanded={isExpanded}
                aria-label={`Toggle ${group.label} section`}
              >
                <span style={{ color: hasActiveChild ? 'var(--accent-primary)' : 'var(--text-secondary)' }}>
                  {group.icon}
                </span>
                <span className="flex-1 text-left text-[10px] font-semibold uppercase tracking-[1.8px]">
                  {group.label}
                </span>
                <ChevronDown
                  size={12}
                  className="transition-transform duration-200"
                  style={{
                    transform: isExpanded ? 'rotate(0deg)' : 'rotate(-90deg)',
                    color: 'var(--text-secondary)',
                  }}
                />
              </button>

              {/* ── Group Items (collapsible) ── */}
              <div
                className="overflow-hidden transition-all duration-200"
                style={{
                  maxHeight: isExpanded ? `${group.items.length * 60}px` : '0px',
                  opacity: isExpanded ? 1 : 0,
                }}
              >
                <div className="space-y-1 pl-1 pt-1">
                  {group.items.map((item) => {
                    const active = currentPath === item.href || currentPath.startsWith(item.href + '/');

                    const linkElement = (
                      <Link
                        key={item.href}
                        href={item.href}
                        onClick={onClose}
                        className={`group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 transition-all duration-200 ${active ? 'sidebar-active shadow-sm' : ''}`}
                        style={{
                          color: active ? 'var(--text-primary)' : 'var(--text-muted)',
                        }}
                      >
                        <span
                          className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg shadow-sm transition-all duration-200"
                          style={{
                            background: active ? 'var(--grad-primary)' : 'rgba(255, 255, 255, 0.03)',
                            color: active ? '#ffffff' : 'var(--text-muted)',
                            border: active ? 'none' : '1px solid var(--border-default)',
                          }}
                        >
                          {item.icon}
                        </span>
                        <div className="min-w-0 flex-1">
                          <span
                            className="block text-[13px] font-semibold leading-none uppercase tracking-wide"
                            style={{
                              color: active ? 'var(--text-primary)' : 'var(--text-secondary)',
                              fontFamily: 'var(--font-sans)',
                            }}
                          >
                            {item.label}
                          </span>
                          <span
                            className="block text-[10px] normal-case leading-none mt-1"
                            style={{ color: active ? 'var(--purple-400)' : 'var(--text-disabled)' }}
                          >
                            {item.description}
                          </span>
                        </div>
                      </Link>
                    );

                    // Wrap with PermissionGuard if permission is required (§9.1 rule 1)
                    if (item.permission) {
                      return (
                        <PermissionGuard key={item.href} permissions={[item.permission]}>
                          {linkElement}
                        </PermissionGuard>
                      );
                    }

                    return linkElement;
                  })}
                </div>
              </div>
            </div>
          );
        })}
      </nav>
    </>
  );
}

// ══════════════════════════════════════════════════════════════════
// AppLayout
// ══════════════════════════════════════════════════════════════════
interface AppLayoutProps { children: React.ReactNode; }

export default function AppLayout({ children }: AppLayoutProps): React.JSX.Element {
  const [theme, toggleTheme] = useTheme();
  const [mobileOpen, setMobileOpen] = React.useState<boolean>(false);

  return (
    <div className="min-h-screen" style={{ background: 'var(--bg-app)', fontFamily: 'var(--font-sans)' }}>

      {/* ── Desktop Sidebar (hidden on mobile) ── */}
      <aside
        className="fixed left-0 top-0 z-40 hidden h-screen w-64 flex-col lg:flex"
        style={{ background: 'var(--bg-surface)', borderRight: '1px solid var(--border-subtle)' }}
      >
        <div className="flex flex-1 flex-col overflow-y-auto">
          <SidebarContent />
        </div>

        {/* Desktop theme toggle */}
        <div className="shrink-0 space-y-2 px-3 pb-4 pt-3" style={{ borderTop: '1px solid var(--border-subtle)' }}>
          <ThemeToggle theme={theme} onToggle={toggleTheme} />
        </div>
      </aside>

      {/* ── Mobile Drawer Overlay (always in DOM, animated via CSS) ── */}
      <div
        className="fixed inset-0 z-50 lg:hidden"
        style={{
          pointerEvents: mobileOpen ? 'auto' : 'none',
          visibility: mobileOpen ? 'visible' : 'hidden',
        }}
      >
        {/* Backdrop — fade in */}
        <div
          className="absolute inset-0"
          onClick={() => setMobileOpen(false)}
          style={{
            background: 'color-mix(in srgb, #000 60%, transparent)',
            opacity: mobileOpen ? 1 : 0,
            transition: 'opacity 300ms cubic-bezier(0.4, 0, 0.2, 1)',
          }}
        />

        {/* Drawer — slide in from left */}
        <aside
          className="absolute left-0 top-0 flex h-full w-72 flex-col"
          style={{
            background: 'var(--bg-surface)',
            borderRight: '1px solid var(--border-subtle)',
            transform: mobileOpen ? 'translateX(0)' : 'translateX(-100%)',
            transition: 'transform 300ms cubic-bezier(0.4, 0, 0.2, 1)',
            willChange: 'transform',
          }}
        >
          <div className="flex flex-1 flex-col overflow-y-auto">
            <SidebarContent onClose={() => setMobileOpen(false)} />
          </div>
          {/* Mobile theme toggle */}
          <div className="shrink-0 px-3 pb-4 pt-3" style={{ borderTop: '1px solid var(--border-subtle)' }}>
            <ThemeToggle theme={theme} onToggle={toggleTheme} />
          </div>
        </aside>
      </div>

      {/* ── Main content area ── */}
      <div className="flex flex-col min-h-screen lg:pl-64">


        {/* ── Top Bar ── */}
        <header
          className="sticky top-0 z-30 flex h-[60px] items-center justify-between gap-4 px-4 md:px-6"
          style={{
            background: 'var(--bg-surface)',
            borderBottom: '1px solid var(--border-subtle)',
            backdropFilter: 'blur(12px)',
          }}
        >
          {/* Left: hamburger (mobile) */}
          <button
            className="flex h-9 w-9 items-center justify-center rounded-lg lg:hidden transition-all"
            style={{ color: 'var(--text-muted)', border: '1px solid var(--border-default)', background: 'var(--bg-card)' }}
            onClick={() => setMobileOpen(true)}
            aria-label="Open menu"
          >
            <IconMenu />
          </button>

          {/* Center: logo on mobile */}
          <div className="flex items-center gap-2 lg:hidden">
            <span className="text-[13px] font-bold" style={{ color: 'var(--text-primary)', fontFamily: 'var(--font-sans)' }}>
            Vidula
          </span>
        </div>

          {/* Right side: search + avatar */}
          <div className="ml-auto flex items-center gap-3">
            <ExpandableSearch />
            <AvatarDropdown />
          </div>
        </header>

        {/* ── Page Content ── */}
        <main className="flex-1" style={{ background: 'var(--bg-app)' }}>

          <div className="p-4 md:p-6 lg:p-8">{children}</div>
        </main>
      </div>
    </div>
  );
}
