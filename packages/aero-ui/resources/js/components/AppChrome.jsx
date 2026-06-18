/**
 * @aero/ui — App Chrome Components
 *
 * Reusable elements that appear in the authenticated app shell:
 * brand mark, topbar title, and the user identity menu.
 *
 * All styling via engine CSS classes in shells/app-chrome.css.
 * No inline style props.
 */
import { Link, router, usePage } from '@inertiajs/react';
import { cx } from './Primitives.jsx';
import { Avatar } from './Display.jsx';
import { Text, Mono, HStack, VStack } from './Primitives.jsx';
import { Menu } from './Overlays.jsx';
import { BellIcon, MagnifyingGlassIcon, ChevronDownIcon } from '@heroicons/react/24/outline';

// ─── AeosLogo ─────────────────────────────────────────────────────────────────
/**
 * The aeos365 SVG logo mark — gradient tile with triangle glyph.
 * Used across app shell, auth layout, install layout, and registration layout.
 *
 * @prop {number} size  Width/height in px (default 28)
 */
export function AeosLogo({ size = 28, className }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 28 28"
      fill="none"
      aria-hidden="true"
      className={cx('aeos-logo-svg', className)}
    >
      <rect width="28" height="28" rx="7" fill="url(#aeos-logo-grad)" />
      <path d="M8 20L14 9l6 11H8z" fill="white" fillOpacity=".92" />
      <defs>
        <linearGradient id="aeos-logo-grad" x1="0" y1="0" x2="28" y2="28">
          <stop stopColor="var(--aeos-primary)" />
          <stop offset="1" stopColor="var(--aeos-tertiary)" />
        </linearGradient>
      </defs>
    </svg>
  );
}

// ─── AppBrand ─────────────────────────────────────────────────────────────────
/**
 * App brand link — logo mark centred in the sidebar rail.
 * Renders as an Inertia Link that navigates to the dashboard.
 *
 * @prop {string} href  Destination href (default '/dashboard')
 * @prop {number} size  Logo size in px (default 28)
 */
export function AppBrand({ href = '/dashboard', size = 28, className }) {
  return (
    <Link href={href} className={cx('aeos-app-brand', className)} aria-label="aeos365 home">
      <AeosLogo size={size} />
    </Link>
  );
}

// ─── AppTopbarTitle ───────────────────────────────────────────────────────────
/**
 * Page title rendered in the shell topbar.
 *
 * @prop {string} title  Page title text
 */
export function AppTopbarTitle({ title, className }) {
  if (!title) return null;
  return (
    <span className={cx('aeos-app-topbar-title', className)}>
      {title}
    </span>
  );
}

// ─── AppUserMenu ──────────────────────────────────────────────────────────────
/**
 * User identity pill for the shell topbar.
 * Shows avatar + name + role and a logout button.
 *
 * @prop {object} user   Inertia auth.user object
 * @prop {string} logoutRoute  Named route for logout (default 'logout')
 */
export function AppUserMenu({ user, logoutRoute = 'logout', className }) {
  if (!user) return null;

  // user.roles may be an array of role STRINGS or role OBJECTS ({ name, … })
  // depending on the shared-props shape — never render the raw object.
  const firstRole = user.roles?.[0];
  const roleLabel = typeof firstRole === 'string' ? firstRole : (firstRole?.name ?? firstRole?.title ?? null);

  const trigger = (
    <button
      type="button"
      className={cx('aeos-app-user-pill', className)}
      aria-haspopup="menu"
      aria-label="Open user menu"
    >
      <Avatar name={user.name ?? user.email} size={28} />
      <span className="aeos-app-user-info">
        <span className="aeos-app-user-name">{user.name ?? user.email}</span>
        {roleLabel && <span className="aeos-app-user-role">{roleLabel}</span>}
      </span>
      <ChevronDownIcon className="aeos-app-user-caret" aria-hidden="true" />
    </button>
  );

  const items = [
    { label: 'My Profile',       icon: 'user',       onClick: () => router.visit('/profile') },
    { label: 'My Notifications', icon: 'bell',       onClick: () => router.visit('/notifications') },
    { label: 'Settings',         icon: 'settings',   onClick: () => router.visit('/settings/system') },
    { divider: true },
    { label: 'Sign out',         icon: 'arrowRight', danger: true, onClick: () => router.post(route(logoutRoute)) },
  ];

  // Right-align: the trigger lives at the far right of the Global Bar, so a
  // left-aligned menu overflows the viewport edge (clipped on mobile).
  return <Menu trigger={trigger} items={items} align="end" />;
}

// ─── GlobalSearchTrigger ──────────────────────────────────────────────────────
/**
 * Discoverable search affordance for the Global Bar. Opens the global
 * SearchOverlay via a window event (decoupled), and advertises the Cmd/Ctrl-K
 * shortcut. Global Bar chrome is universal — it is NOT permission-gated; result
 * scoping is enforced server-side on the search endpoint.
 */
export function GlobalSearchTrigger({ className }) {
  const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform || '');

  return (
    <button
      type="button"
      className={cx('aeos-globalbar-search', className)}
      onClick={() => window.dispatchEvent(new CustomEvent('aeos:open-search'))}
      aria-label="Search"
      title="Search"
    >
      <MagnifyingGlassIcon className="aeos-icon-sm" aria-hidden="true" />
      <span className="aeos-globalbar-search-label">Search…</span>
      <kbd className="aeos-globalbar-search-kbd">{isMac ? '⌘K' : 'Ctrl K'}</kbd>
    </button>
  );
}

// ─── GlobalNotifications ──────────────────────────────────────────────────────
/**
 * Notifications bell for the Global Bar — a real Inertia Link (not a dead
 * button) to the notifications page, with an unread-count badge.
 */
export function GlobalNotifications({ count = 0, href = '/notifications', className }) {
  const n = Number(count) || 0;
  return (
    <Link
      href={href}
      className={cx('aeos-globalbar-bell', className)}
      aria-label={n > 0 ? `Notifications, ${n} unread` : 'Notifications'}
      title="Notifications"
    >
      <BellIcon className="aeos-icon-sm" aria-hidden="true" />
      {n > 0 && <span className="aeos-globalbar-bell-badge" aria-hidden="true">{n > 99 ? '99+' : n}</span>}
    </Link>
  );
}

// ─── GlobalActions ────────────────────────────────────────────────────────────
/**
 * Standardized right side of the Global Bar shared by every shell:
 * Search · Notifications · User menu. Pass as the shell `actions` slot.
 */
export function GlobalActions({ user, className }) {
  const page = usePage();
  const unread = page?.props?.auth?.unread_notifications
              ?? page?.props?.notifications_unread
              ?? 0;
  return (
    <HStack gap={2} align="center" className={cx('aeos-globalbar-actions', className)}>
      <GlobalSearchTrigger />
      <GlobalNotifications count={unread} />
      <AppUserMenu user={user} />
    </HStack>
  );
}
