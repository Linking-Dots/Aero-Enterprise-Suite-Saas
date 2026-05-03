/**
 * App — Authenticated tenant + platform admin app layout.
 *
 * Uses AppShell (auto-selects shell variant from ThemeProvider preference)
 * with engine AppChrome components for brand, topbar title, and user menu.
 * The ThemeDrawer is always visible so users can customise the full theme.
 *
 * Usage on any authenticated page:
 *   import App from '../App.jsx';
 *   MyPage.layout = page => <App title="Page Title">{page}</App>;
 */
import { usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { AppShell, AppBrand, AppTopbarTitle, AppUserMenu } from '@aero/ui';
import { useTheme } from '../theme/ThemeProvider.jsx';

// ─── HeroIcon → engine icon name map ─────────────────────────────────────────
const HERO_TO_ENGINE = {
  HomeIcon:                   'home',
  UserIcon:                   'user',
  UserCircleIcon:             'user',
  UsersIcon:                  'users',
  ShieldCheckIcon:            'shield',
  ShieldIcon:                 'shield',
  CogIcon:                    'settings',
  CogWrenchIcon:              'settings',
  WrenchScrewdriverIcon:      'settings',
  WrenchIcon:                 'settings',
  BellIcon:                   'bell',
  BellAlertIcon:              'bell',
  FolderIcon:                 'folder',
  FolderOpenIcon:             'folder',
  ChartBarIcon:               'chartBar',
  ChartBarSquareIcon:         'chartBar',
  ChartSquareBarIcon:         'chartBar',
  PresentationChartBarIcon:   'chartBar',
  DocumentIcon:               'document',
  DocumentTextIcon:           'document',
  ClipboardDocumentListIcon:  'document',
  DocumentMagnifyingGlassIcon:'document',
  ClipboardIcon:              'document',
  CubeIcon:                   'cube',
  CubeTransparentIcon:        'cube',
  TruckIcon:                  'truck',
  ShoppingCartIcon:           'shoppingCart',
  BeakerIcon:                 'beaker',
  BoltIcon:                   'bolt',
  LockClosedIcon:             'lock',
  LockOpenIcon:               'lockOpen',
  ArrowPathIcon:              'refresh',
  PuzzlePieceIcon:            'puzzle',
  CircleStackIcon:            'database',
  BuildingOffice2Icon:        'home',
  BuildingOfficeIcon:         'home',
  CurrencyDollarIcon:         'chartBar',
  BanknotesIcon:              'chartBar',
  GlobeAltIcon:               'globe',
  GlobeAmericasIcon:          'globe',
  PhoneIcon:                  'phone',
  LinkIcon:                   'link',
  StarIcon:                   'star',
  SparklesIcon:               'sparkles',
  MagnifyingGlassIcon:        'search',
  XMarkIcon:                  'x',
  Bars3Icon:                  'menu',
  RectangleGroupIcon:         'layout',
  TableCellsIcon:             'layout',
  Squares2X2Icon:             'layout',
  CalendarIcon:               'calendar',
  CalendarDaysIcon:           'calendar',
  ClockIcon:                  'clock',
  MapPinIcon:                 'pin',
  MapIcon:                    'globe',
  TagIcon:                    'tag',
  InboxIcon:                  'mail',
  EnvelopeIcon:               'mail',
  ChatBubbleLeftIcon:         'mail',
  MegaphoneIcon:              'bell',
  ExclamationCircleIcon:      'alertCircle',
  CheckCircleIcon:            'checkCircle',
  InformationCircleIcon:      'alertCircle',
  ArrowTrendingUpIcon:        'trending',
  default:                    'layout',
};

function mapIcon(heroIconName) {
  if (!heroIconName) return 'layout';
  return HERO_TO_ENGINE[heroIconName] ?? HERO_TO_ENGINE.default;
}

function isActive(href, currentUrl) {
  if (!href || href === '#') return false;
  if (href === '/dashboard') return currentUrl === '/dashboard' || currentUrl === '/';
  return currentUrl.startsWith(href);
}

function mapItem(item, currentUrl) {
  const href = item.path || item.children?.[0]?.path || '#';
  return { icon: mapIcon(item.icon), label: item.name ?? '', href, active: isActive(href, currentUrl) };
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
  const href = item.path || item.children?.[0]?.path || '#';
  return { icon: mapIcon(item.icon), label: item.name ?? '', href, active: isActive(href, currentUrl) };
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
    </>
  );
}
