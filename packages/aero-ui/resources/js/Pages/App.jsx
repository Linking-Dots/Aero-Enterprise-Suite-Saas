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
import { AppShell, AppBrand, AppTopbarTitle, GlobalActions, SearchOverlay } from '@aero/ui';
import { useTheme } from '../theme/ThemeProvider.jsx';

import * as HeroIcons from '@heroicons/react/24/outline';

function mapIcon(heroIconName) {
  if (!heroIconName) return HeroIcons.Squares2X2Icon;
  return HeroIcons[heroIconName] || HeroIcons.Squares2X2Icon;
}

/** Normalise an href/path to a bare pathname (drop query + hash). */
function normPath(href) {
  return href ? href.split('?')[0].split('#')[0] : href;
}

/**
 * Segment-aware match: an href matches the current URL only at a path
 * boundary, so "/settings" never matches "/settings-foo" and a parent path is
 * only a candidate for its own sub-tree. External links never match.
 */
function hrefMatches(href, currentUrl) {
  if (!href || href === '#' || /^(https?:|\/\/|mailto:|tel:)/.test(href)) return false;
  const h = normPath(href);
  if (h === '/dashboard') return currentUrl === '/dashboard' || currentUrl === '/';
  const base = h.replace(/\/$/, '');
  if (base === '') return false; // guard against a '/' path matching everything
  return currentUrl === base || currentUrl.startsWith(base + '/');
}

/** Recursively collect every navigable href in a nav tree. */
function collectHrefs(items, acc = []) {
  for (const item of items ?? []) {
    const p = item?.path ?? item?.href;
    if (p) acc.push(normPath(p));
    if (item?.children) collectHrefs(item.children, acc);
  }
  return acc;
}

/**
 * The single active href is the LONGEST href that matches the current URL.
 * Longest-match-wins guarantees exactly one active leaf — the actual page —
 * instead of every ancestor path lighting up alongside it.
 */
function computeActiveHref(currentUrl, ...navSources) {
  let best = null;
  for (const src of navSources) {
    for (const h of collectHrefs(src)) {
      if (hrefMatches(h, currentUrl) && (best === null || h.length > best.length)) {
        best = h;
      }
    }
  }
  return best;
}

function mapItem(item, activeHref) {
  let children;
  let hasActiveChild = false;
  if (item.children && item.children.length > 0) {
    children = item.children.map(child => mapItem(child, activeHref));
    hasActiveChild = children.some(c => c.active || c.hasActiveChild);
  }

  const href = item.path || undefined;
  const leafActive = href != null && activeHref != null && normPath(href) === activeHref;
  const active = leafActive || hasActiveChild;

  return {
    icon: mapIcon(item.icon),
    label: item.name ?? '',
    href,
    active,
    hasActiveChild,
    children,
  };
}

function transformNavigation(backendNav, activeHref) {
  if (!backendNav?.length) return null;

  const buckets = { dashboards: [], 'my-workspace': [], administration: [], modules: [], others: [] };

  for (const item of backendNav) {
    const section = item.section ?? 'others';
    if (Object.prototype.hasOwnProperty.call(buckets, section)) {
      buckets[section].push(mapItem(item, activeHref));
    } else {
      buckets.modules.push(mapItem(item, activeHref));
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

function transformNavigationGroups(backendGroups, activeHref) {
  if (!backendGroups?.length) return null;

  return backendGroups
    .map(group => ({
      title: group.title ?? '',
      items: (group.items ?? []).map(item => mapItem(item, activeHref)),
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

function buildFallbackNav(activeHref) {
  return FALLBACK_NAV.map(item => {
    if (item.divider || item.spacer) return item;
    return { ...item, active: item.href != null && normPath(item.href) === activeHref };
  });
}

// ─── App layout ───────────────────────────────────────────────────────────────
export default function App({ title, rail, railTitle = 'Context', children }) {
  const page = usePage();
  const { auth, navigation, navigationGroups } = page.props;
  const theme = useTheme();
  // Inertia's top-level `page.url` is the request PATH (e.g. "/tenants?p=2"),
  // whereas `page.props.url` is the FULL absolute URL from HandleInertiaRequests.
  // Active-state matching needs the path, so read the top-level url and strip
  // any query string / hash before comparing.
  const rawUrl = page.url ?? (typeof window !== 'undefined' ? window.location.pathname : '/dashboard');
  const currentUrl = rawUrl.split('?')[0].split('#')[0];

  // Determine the single active href across every nav source so exactly one
  // leaf highlights (longest-match-wins), regardless of which shell renders.
  const groupItems = (navigationGroups ?? []).flatMap(g => g.items ?? []);
  const activeHref = computeActiveHref(currentUrl, navigation ?? [], groupItems, FALLBACK_NAV);

  const isCommand = theme.shell === 'command';
  // Flat nav is the canonical list; the command shell uses grouped nav, but the
  // mobile shell always wants the flat tree regardless of the desktop variant.
  const flatNav = transformNavigation(navigation, activeHref) ?? buildFallbackNav(activeHref);
  const nav = isCommand
    ? (transformNavigationGroups(navigationGroups, activeHref) ?? [])
    : flatNav;

  return (
    <>
      {title && <Head title={`${title} · aeos365`} />}
      <AppShell
        brand={<AppBrand href="/dashboard" size={28} />}
        nav={nav}
        mobileNav={flatNav}
        topbar={<AppTopbarTitle title={title} />}
        actions={<GlobalActions user={auth?.user} />}
        rail={rail}
        railTitle={railTitle}
      >
        {children}
      </AppShell>
      <SearchOverlay />
    </>
  );
}
