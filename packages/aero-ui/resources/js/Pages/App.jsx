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
import { useTourEngine } from '../tour/useTour.jsx';

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
    // Semantic sub-group within a product section (see transformNavigation).
    group: item.nav_group ?? null,
    groupLabel: item.nav_group_label ?? null,
    groupOrder: item.nav_group_order ?? 500,
    groupIcon: item.nav_group_icon ?? null,
  };
}

// Sections framing the package-owned catalog (props.navSections): dashboards +
// my-workspace pin to the top; administration/modules are the tail fallbacks.
// The middle (core sections + subscribed-product sections) is data-driven, so
// adding a product/section needs no frontend change.
const PINNED_SECTIONS = [
  { key: 'dashboards',   title: null,           icon: null,             order: -100 },
  { key: 'my-workspace', title: 'My Workspace', icon: 'UserCircleIcon', order: -50 },
];
const TAIL_SECTIONS = [
  { key: 'administration', title: 'Administration', icon: 'RectangleGroupIcon', order: 9000 },
  { key: 'modules',        title: 'Modules',        icon: 'CubeIcon',           order: 9100 },
];

const humanize = (k) => String(k).replace(/[-_]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

/** Ordered section list built from the backend catalog (props.navSections). */
function buildSections(catalog) {
  const mid = (catalog ?? []).map((s) => ({ key: s.key, title: s.label, icon: s.icon, order: s.order ?? 500 }));
  return [...PINNED_SECTIONS, ...mid, ...TAIL_SECTIONS].sort((a, b) => (a.order ?? 500) - (b.order ?? 500));
}

function transformNavigation(backendNav, activeHref, sections) {
  if (!backendNav?.length) return null;

  const meta = new Map(sections.map((s) => [s.key, s]));
  const buckets = {};
  for (const item of backendNav) {
    const section = item.section ?? 'modules';
    // The self-service items arrive pre-aggregated under a single "My Workspace"
    // parent. That parent would sit inside the "My Workspace" section header —
    // doubled — so flatten its children directly under the header instead.
    if (section === 'my-workspace' && Array.isArray(item.children) && item.children.length) {
      for (const child of item.children) (buckets[section] ??= []).push(mapItem(child, activeHref));
    } else {
      (buckets[section] ??= []).push(mapItem(item, activeHref));
    }
  }

  // Catalog sections render in their declared order; any section not in the
  // catalog (e.g. a product with no declared section) sorts at 8000 — just
  // after the core catalog — so products always fall below core.
  const ordered = Object.keys(buckets)
    .map((key) => {
      const m = meta.get(key);
      return { key, title: m ? m.title : humanize(key), icon: m?.icon, order: m ? (m.order ?? 500) : 8000 };
    })
    .sort((a, b) => (a.order ?? 500) - (b.order ?? 500));

  // Drop a leaf that already appeared in an earlier section (e.g. "My Profile"
  // exists both as a self-service item and a top-level submodule). Earlier
  // section wins, so My Workspace keeps it.
  const seen = new Set();
  const dedup = (items) => items.filter((it) => {
    if (it.href && !(it.children && it.children.length)) {
      if (seen.has(it.href)) return false;
      seen.add(it.href);
    }
    return true;
  });

  const result = [];
  for (const { key, title, icon } of ordered) {
    const items = dedup(buckets[key] ?? []);
    if (!items.length) continue;
    if (!title) {
      // Untitled section (dashboards) — pinned at the top, no wrapper.
      result.push(...items);
      continue;
    }
    result.push({
      label: title,
      icon: icon ? mapIcon(icon) : undefined,
      isSection: true,
      hasActiveChild: items.some((it) => it.active || it.hasActiveChild),
      children: subGroup(items),
    });
  }

  return result.length ? result : null;
}

// Regroup a product section's features into its declared semantic sub-groups
// (nav_group). Each sub-group is itself a collapsible, icon'd section nested
// under the product header. Ungrouped items stay directly under the header.
function subGroup(items) {
  if (!items.some((it) => it.group)) return items;
  const groups = new Map();
  const ungrouped = [];
  for (const it of items) {
    if (!it.group) { ungrouped.push(it); continue; }
    if (!groups.has(it.group)) {
      groups.set(it.group, { label: it.groupLabel ?? humanize(it.group), order: it.groupOrder ?? 500, icon: it.groupIcon, items: [] });
    }
    groups.get(it.group).items.push(it);
  }
  const out = [...ungrouped];
  for (const g of [...groups.values()].sort((a, b) => (a.order ?? 500) - (b.order ?? 500))) {
    out.push({
      label: g.label,
      icon: g.icon ? mapIcon(g.icon) : undefined,
      isSection: true,
      isSubSection: true,
      hasActiveChild: g.items.some((it) => it.active || it.hasActiveChild),
      children: g.items,
    });
  }
  return out;
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
  const { auth, navigation, navigationGroups, navSections } = page.props;
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

  // Guided live-demo tour: auto-start once per browser on the demo tenant,
  // and resume across Inertia navigations. No-op on non-demo tenants.
  useTourEngine({
    isDemo: page.props.tenant?.demo === true,
    reduced: theme.motion !== 'full',
    url: currentUrl,
  });

  const isCommand = theme.shell === 'command';
  // Section titles/icons/order come from the package-owned catalog (props.navSections).
  const sections = buildSections(navSections);
  // Flat nav is the canonical list; the command shell uses grouped nav, but the
  // mobile shell always wants the flat tree regardless of the desktop variant.
  const flatNav = transformNavigation(navigation, activeHref, sections) ?? buildFallbackNav(activeHref);
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
