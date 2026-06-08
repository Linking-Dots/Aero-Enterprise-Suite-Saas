/**
 * App — Authenticated tenant + platform admin app layout.
 *
 * Uses AppShell (auto-selects shell variant from ThemeProvider preference)
 * with engine AppChrome components for brand, topbar title, and user menu.
 * The ThemeDrawer is always visible so users can customise the full theme.
 *
 * Usage on any authenticated page:
 *   import App from '@/Pages/App.jsx';
 *   MyPage.layout = page => <App title="Page Title">{page}</App>;
 */
import { usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { AppShell, AppBrand, AppTopbarTitle, AppUserMenu, SearchOverlay } from '@aero/ui';
import { useTheme } from '../theme/ThemeProvider.jsx';

import * as HeroIcons from '@heroicons/react/24/outline';

function mapIcon(heroIconName) {
  if (!heroIconName) return HeroIcons.Squares2X2Icon;
  return HeroIcons[heroIconName] || HeroIcons.Squares2X2Icon;
}

function isActive(href, currentUrl) {
  if (!href || href === '#') return false;
  if (href === '/dashboard') return currentUrl === '/dashboard' || currentUrl === '/';
  return currentUrl.startsWith(href);
}

function mapItem(item, currentUrl) {
  let children;
  let hasActiveChild = false;
  if (item.children && item.children.length > 0) {
    children = item.children.map(child => mapItem(child, currentUrl));
    hasActiveChild = children.some(c => c.active || c.hasActiveChild);
  }

  const href = item.path || undefined;
  const active = isActive(href, currentUrl) || hasActiveChild;

  return {
    icon: mapIcon(item.icon),
    label: item.name ?? '',
    href,
    active,
    hasActiveChild,
    children,
  };
}

function transformNavigation(backendNav, currentUrl) {
  if (!backendNav?.length) return null;

  const buckets = { dashboards: [], 'my-workspace': [], administration: [], modules: [], others: [] };

  for (const item of backendNav) {
    const section = item.section ?? 'others';
    if (Object.prototype.hasOwnProperty.call(buckets, section)) {
      buckets[section].push(mapItem(item, currentUrl));
    } else {
      buckets.modules.push(mapItem(item, currentUrl));
    }
  }

  const result = [];
  if (buckets.dashboards.length)             result.push(...buckets.dashboards);
  if (buckets['my-workspace'].length)        { if (result.length) result.push({ divider: true }); result.push(...buckets['my-workspace']); }
  if (buckets.administration.length)         { if (result.length) result.push({ divider: true }); result.push(...buckets.administration); }
  if (buckets.modules.length)               { if (result.length) result.push({ divider: true }); result.push(...buckets.modules); }
  if (buckets.others.length)                result.push(...buckets.others);

  return result.length ? result : null;
}

function mapGroupItem(item, currentUrl) {
  return mapItem(item, currentUrl);
}

function transformNavigationGroups(backendGroups, currentUrl) {
  if (!backendGroups?.length) return null;

  return backendGroups
    .map(group => ({
      title: group.title ?? '',
      items: (group.items ?? []).map(item => mapGroupItem(item, currentUrl)),
    }))
    .filter(g => g.items.length > 0);
}

const FALLBACK_NAV = [
  { icon: 'layout',   label: 'Dashboard', href: '/dashboard'       },
  { divider: true },
  { icon: 'users',    label: 'Users',     href: '/users'           },
  { icon: 'shield',   label: 'Roles',     href: '/roles'           },
  { icon: 'chartBar', label: 'Audit',     href: '/audit-logs'      },
  { icon: 'folder',   label: 'Files',     href: '/files'           },
  { spacer: true },
  { icon: 'settings', label: 'Settings',  href: '/settings/system' },
];

function buildFallbackNav(currentUrl) {
  return FALLBACK_NAV.map(item => {
    if (item.divider || item.spacer) return item;
    return { ...item, active: isActive(item.href, currentUrl) };
  });
}

// ─── App layout ───────────────────────────────────────────────────────────────
export default function App({ title, children }) {
  const { auth, navigation, navigationGroups, url } = usePage().props;
  const theme = useTheme();
  const currentUrl = url ?? (typeof window !== 'undefined' ? window.location.pathname : '/dashboard');

  const isCommand = theme.shell === 'command';
  const nav = isCommand
    ? (transformNavigationGroups(navigationGroups, currentUrl) ?? [])
    : (transformNavigation(navigation, currentUrl) ?? buildFallbackNav(currentUrl));

  return (
    <>
      {title && <Head title={`${title} · aeos365`} />}
      <AppShell
        brand={<AppBrand href="/dashboard" size={28} />}
        nav={nav}
        topbar={<AppTopbarTitle title={title} />}
        actions={<AppUserMenu user={auth?.user} />}
      >
        {children}
      </AppShell>
      <SearchOverlay />
    </>
  );
}
