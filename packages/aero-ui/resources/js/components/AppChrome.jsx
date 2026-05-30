/**
 * @aero/ui — App Chrome Components
 *
 * Reusable elements that appear in the authenticated app shell:
 * brand mark, topbar title, and the user identity menu.
 *
 * All styling via engine CSS classes in shells/app-chrome.css.
 * No inline style props.
 */
import { Link, router } from '@inertiajs/react';
import { cx } from './Primitives.jsx';
import { Avatar } from './Display.jsx';
import { Text, Mono, HStack, VStack } from './Primitives.jsx';
import { Button } from './Actions.jsx';
import { BellIcon, ArrowRightIcon } from '@heroicons/react/24/outline';

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

  const initials = (user.name ?? user.email ?? '?')
    .split(' ')
    .slice(0, 2)
    .map(p => p[0])
    .join('')
    .toUpperCase();

  function handleLogout(e) {
    e.preventDefault();
    router.post(route(logoutRoute));
  }

  return (
    <HStack gap={2} align="center" className={cx('aeos-app-user-menu', className)}>
      {/* Notifications */}
      <Button
        intent="ghost"
        size="sm"
        aria-label="Notifications"
        title="Notifications"
        onClick={() => {}}
      >
        <BellIcon className="aeos-icon-sm" />
      </Button>

      {/* User pill */}
      <HStack gap={2} align="center" className="aeos-app-user-pill">
        <Avatar initials={initials} size="sm" />
        <VStack gap={0} className="aeos-app-user-info">
          <Text as="span" size="sm">{user.name ?? user.email}</Text>
          {user.roles?.[0] && (
            <Mono as="span" size="sm" tone="tertiary">{user.roles[0]}</Mono>
          )}
        </VStack>
        <Button
          intent="ghost"
          size="sm"
          onClick={handleLogout}
          aria-label="Sign out"
          title="Sign out"
        >
          <ArrowRightIcon className="aeos-icon-xs" />
        </Button>
      </HStack>
    </HStack>
  );
}
