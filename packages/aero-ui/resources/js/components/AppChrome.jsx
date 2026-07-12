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
import { useTheme } from '../theme/ThemeProvider.jsx';
import lockupLight from '../assets/brand/lockup-horizontal.svg';
import lockupDark from '../assets/brand/lockup-horizontal-dark.svg';
import { cx } from './Primitives.jsx';
import { Avatar } from './Display.jsx';
import { Text, Mono, HStack, VStack } from './Primitives.jsx';
import { Menu } from './Overlays.jsx';
import { BellIcon, MagnifyingGlassIcon, ChevronDownIcon } from '@heroicons/react/24/outline';

// ─── AeosLogo ─────────────────────────────────────────────────────────────────
/**
 * The Meridian mark — core disc in open orbit with the signal node
 * (branding/svg/mark.svg geometry). The universal brand fallback: rendered
 * whenever no white-label logo is configured. Ink follows currentColor so it
 * adapts to light/dark chrome; the node stays Signal orange.
 *
 * @prop {number} size  Width/height in px (default 28)
 */
export function AeosLogo({ size = 28, className }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      aria-hidden="true"
      className={cx('aeos-logo-svg', className)}
    >
      <circle cx="12" cy="12" r="4.6" fill="currentColor" />
      <path
        d="M20.65 8.33A9.4 9.4 0 1 1 15.67 3.35"
        stroke="currentColor"
        strokeWidth="2.1"
        strokeLinecap="round"
      />
      <circle cx="18.65" cy="5.35" r="2.35" fill="#FF7A1F" />
    </svg>
  );
}

// ─── AppBrand ─────────────────────────────────────────────────────────────────
/**
 * App brand link — white-label aware. Renders the workspace's configured
 * logo (resolved through the tenant → platform → Meridian chain shared as
 * the `branding` page prop) and falls back to the Meridian mark.
 *
 * @prop {string} href  Destination href (default '/dashboard')
 * @prop {number} size  Logo size in px (default 28)
 */
export function AppBrand({ href = '/dashboard', size = 28, className }) {
  const { branding } = usePage().props;
  const theme = useTheme();
  const darkGround = theme?.isDark ?? true;

  const logo = darkGround
    ? (branding?.logo_dark || branding?.logo_light || null)
    : (branding?.logo_light || branding?.logo_dark || null);
  const name = branding?.name || 'aeos365';

  return (
    <Link href={href} className={cx('aeos-app-brand', className)} aria-label={`${name} home`}>
      {logo ? (
        // White-labeled: dedicated icon logo (falling back favicon → lockup) in
        // the collapsed rail; the FULL lockup image everywhere else — never a
        // composed icon + text. CSS drives the switch.
        <>
          <img src={branding?.logo_icon || branding?.favicon || logo} alt="" className="aeos-brand-icon" />
          <img src={logo} alt={name} className="aeos-brand-img" />
        </>
      ) : (
        // Default: the OFFICIAL Meridian assets — mark in the collapsed rail,
        // lockup image everywhere else. ALWAYS an image, never mark + text.
        <>
          <AeosLogo size={size} className="aeos-brand-icon" />
          <img src={darkGround ? lockupDark : lockupLight} alt={name} className="aeos-brand-img" />
        </>
      )}
    </Link>
  );
}

// ─── BrandLockup ──────────────────────────────────────────────────────────────
/**
 * The FULL horizontal brand lockup for wide surfaces (login, installer,
 * registration, public site header/footer). White-label aware through the
 * shared `branding` page prop: uploaded lockup → official Meridian lockup
 * image. ALWAYS an image — never a composed mark + text wordmark.
 *
 * @prop {string} className  Class applied to the lockup <img>
 */
export function BrandLockup({ className }) {
  const { branding } = usePage().props;
  const theme = useTheme();
  const darkGround = theme?.isDark ?? true;

  const logo = darkGround
    ? (branding?.logo_dark || branding?.logo_light || null)
    : (branding?.logo_light || branding?.logo_dark || null);
  const name = branding?.name || 'aeos365';

  if (logo) return <img src={logo} alt={name} className={className} />;
  return <img src={darkGround ? lockupDark : lockupLight} alt={name} className={className} />;
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
