/**
 * AEOS UI — Shells.jsx  (Overhaul v2)
 * ─────────────────────────────────────────────────────────────────────────
 * Four shell layout components. All visual decisions live in CSS — this file
 * only manages structure, state, and class names.
 *
 * Shells:
 *   SidebarShell   — 56px icon rail ↔ 240px expanded (default)
 *   TopNavShell    — Sticky full-width top bar
 *   FloatingShell  — Inset glass pill sidebar; mobile → bottom tab bar
 *   CommandShell   — Three-panel (left nav | center | right rail)
 *
 * Public API surface is backwards-compatible with v1.
 * ─────────────────────────────────────────────────────────────────────────
 */

import { forwardRef, useState, useEffect, useLayoutEffect, useCallback, useRef } from 'react';
import { Link as InertiaLink } from '@inertiajs/react';
import { useTheme } from '../theme/ThemeProvider.jsx';
import { Bars3Icon, ChevronRightIcon, ChevronDownIcon } from '@heroicons/react/24/outline';
import { cx } from '../components/Primitives.jsx';
import { Tooltip } from '../components/Overlays.jsx';
import { AeosErrorBoundary } from '../components/AeosErrorBoundary.jsx';

/* ─── Shell preference persistence ─────────────────────────────────────── */
const SHELL_PREFS_KEY = 'aeos-shell-prefs';

function loadShellPrefs() {
  try {
    const raw = typeof localStorage !== 'undefined' && localStorage.getItem(SHELL_PREFS_KEY);
    return raw ? JSON.parse(raw) : {};
  } catch { return {}; }
}

function saveShellPrefs(patch) {
  try {
    const current = loadShellPrefs();
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem(SHELL_PREFS_KEY, JSON.stringify({ ...current, ...patch }));
    }
  } catch { /* quota / private mode — non-fatal */ }
}

// Per-group open/closed state (nav sections + module accordions), persisted so a
// user's collapse choices survive reloads and Inertia navigations. Keyed by a
// stable id ('sec:<label>' / 'mod:<href|label>'); value is the explicit isOpen.
const NAV_STATE_KEY = 'aeos-nav-state';

function loadNavState() {
  try {
    const raw = typeof localStorage !== 'undefined' && localStorage.getItem(NAV_STATE_KEY);
    return raw ? JSON.parse(raw) : {};
  } catch { return {}; }
}

function saveNavState(key, open) {
  if (!key) return;
  try {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem(NAV_STATE_KEY, JSON.stringify({ ...loadNavState(), [key]: open }));
    }
  } catch { /* non-fatal */ }
}

/* ─── RecursiveNavItem ──────────────────────────────────────────────────── */
function RecursiveNavItem({ item, depth = 0, isCommand = false, expanded = true }) {
  const hasChildren = Array.isArray(item.children) && item.children.length > 0;
  // Stable key for persisting this group's open/closed state.
  const navKey = item.isSection
    ? (item.label ? `sec:${item.label}` : null)
    : (hasChildren ? `mod:${item.href ?? item.label ?? ''}` : null);

  // Restore the saved open state; else default (sections open, modules closed
  // unless they contain the active page).
  const [isOpen, setIsOpen] = useState(() => {
    const saved = navKey ? loadNavState()[navKey] : undefined;
    if (saved !== undefined) return saved;
    // Industry-standard (Guardian) default: everything collapsed except the
    // branch that contains the current page.
    return item.hasActiveChild ?? false;
  });
  // Hooks must run unconditionally before any early return (React rules-of-hooks):
  // `expanded` changes at runtime, so a return above this would drop a hook.
  const toggle = useCallback((e) => {
    e.preventDefault();
    if (expanded || isCommand) {
      setIsOpen(v => { const next = !v; saveNavState(navKey, next); return next; });
    }
  }, [expanded, isCommand, navKey]);
  // Guardian pattern: navigating INTO a branch auto-expands it so the active
  // item is always visible. Ephemeral (not persisted) — a user's explicit
  // collapse while on the page sticks until they navigate into it again.
  useEffect(() => {
    if (item.hasActiveChild) setIsOpen(true);
  }, [item.hasActiveChild]);

  if (item.divider) return <div className="aeos-shell-sidebar-divider" aria-hidden="true" />;
  if (item.spacer)  return <div className="aeos-shell-sidebar-spacer"  aria-hidden="true" />;
  // Section heading. Collapsed (icon-only) rail can't show text, so degrade to a
  // divider; the command shell (isCommand) always has room for the label.
  if (item.heading) {
    return (expanded || isCommand)
      ? <div className="aeos-shell-sidebar-heading">{item.heading}</div>
      : <div className="aeos-shell-sidebar-divider" aria-hidden="true" />;
  }
  // A collapsible section on an icon-only rail has no room for its header —
  // render the modules directly so the rail stays usable.
  if (item.isSection && !expanded && !isCommand) {
    return (
      <>
        {(item.children ?? []).map((child, i) => (
          <RecursiveNavItem key={i} item={child} depth={0} isCommand={isCommand} expanded={expanded} />
        ))}
      </>
    );
  }

  const isLink     = Boolean(item.href) && !hasChildren;
  const isInternal = isLink && !/^(https?:|\/\/|mailto:|tel:)/.test(item.href);
  const Tag        = isLink ? (isInternal ? InertiaLink : 'a') : 'button';

  const depthClass = depth > 0 && (expanded || isCommand)
    ? `aeos-nav-depth-${Math.min(depth, 3)}`
    : undefined;

  const baseClass = isCommand ? 'aeos-shell-nav-item' : 'aeos-shell-sidebar-item';
  const itemClass = cx(
    baseClass,
    item.isSection                && 'is-section',
    item.isSubSection             && 'is-subsection',
    item.active && !hasChildren && 'active',
    item.hasActiveChild           && 'active-parent',
    depthClass,
  );

  const tagContent = (
    <Tag
      type={Tag === 'button' ? 'button' : undefined}
      href={isLink ? item.href : undefined}
      onClick={hasChildren ? toggle : item.onClick}
      className={itemClass}
      title={item.label}
      aria-label={item.label}
      aria-current={item.active && !hasChildren ? 'page' : undefined}
      aria-expanded={hasChildren ? isOpen : undefined}
    >
      {item.active && !hasChildren && (
        <span className="aeos-nav-active-indicator" aria-hidden="true" />
      )}

      {item.icon && (
        <item.icon
          className={cx('aeos-shell-nav-icon', depth > 0 && 'aeos-shell-nav-icon--child')}
          aria-hidden="true"
        />
      )}

      {(isCommand || expanded) && (
        <>
          <span className={isCommand ? 'aeos-shell-nav-item-label' : 'aeos-shell-sidebar-label'}>
            {item.label}
          </span>

          {item.count != null && (
            <span className={cx('aeos-shell-cmd-nav-count', hasChildren && 'aeos-shell-cmd-nav-count--spaced')}>
              {item.count}
            </span>
          )}

          {hasChildren && (
            <span className={cx('aeos-shell-nav-chevron', isOpen && 'is-open')} aria-hidden="true">
              <ChevronRightIcon />
            </span>
          )}
        </>
      )}
    </Tag>
  );

  const content = (!expanded && !isCommand && depth === 0)
    ? <Tooltip label={item.label} side="right">{tagContent}</Tooltip>
    : tagContent;

  return (
    <div className="aeos-nav-item-wrapper">
      {content}

      {/* Children render only when open — a conditional (not an overflow:hidden
          collapse) so nothing clips horizontally and the rail can auto-size to
          the widest, deepest item. A light fade-in stands in for the slide. */}
      {hasChildren && (expanded || isCommand) && isOpen && (
        <div className="aeos-shell-sidebar-children aeos-nav-children-reveal">
          {item.children.map((child, i) => (
            <RecursiveNavItem key={i} item={child} depth={depth + 1} isCommand={isCommand} expanded={expanded} />
          ))}
        </div>
      )}
    </div>
  );
}

/* ─── useIsMobile — matchMedia hook ─────────────────────────────────────── */
function useIsMobile(query = '(max-width: 768px)') {
  const read = () =>
    typeof window !== 'undefined' && window.matchMedia ? window.matchMedia(query).matches : false;
  const [isMobile, setIsMobile] = useState(read);
  useEffect(() => {
    if (typeof window === 'undefined' || !window.matchMedia) return;
    const mq = window.matchMedia(query);
    const on = () => setIsMobile(mq.matches);
    on();
    mq.addEventListener('change', on);
    return () => mq.removeEventListener('change', on);
  }, [query]);
  return isMobile;
}

/* ─── MobileShell — the single mobile layout every variant collapses to ────
   Industry standard (MDN/NN-g): shell variants are a desktop concept; on a
   phone they all converge to one app-bar + hamburger drawer. This replaces the
   four shells' individual (and inconsistent) mobile treatments below 768px. */
export function MobileShell({ brand, nav = [], actions, children }) {
  const [open, setOpen] = useState(false);
  useEffect(() => {
    if (!open) return;
    const onKey = (e) => { if (e.key === 'Escape') setOpen(false); };
    document.addEventListener('keydown', onKey);
    document.body.style.overflow = 'hidden';
    return () => { document.removeEventListener('keydown', onKey); document.body.style.overflow = ''; };
  }, [open]);

  const items = nav.filter(item => !item.divider && !item.spacer);

  return (
    <div data-aeos-shell="mobile" className={cx('aeos-app-chrome-active', open && 'mobile-open')}>
      <a href="#aeos-main-content" className="aeos-skip-link">Skip to content</a>

      <header className="aeos-shell-topbar" role="banner">
        <button
          type="button"
          className="aeos-icon-btn aeos-mobile-burger"
          onClick={() => setOpen(v => !v)}
          aria-label={open ? 'Close navigation' : 'Open navigation'}
          aria-expanded={open}
        >
          <Bars3Icon className="aeos-icon-sm" aria-hidden="true" />
        </button>
        {brand && <span className="aeos-shell-brand">{brand}</span>}
        <span className="aeos-shell-flex-spacer" />
        {actions && <div className="aeos-shell-actions">{actions}</div>}
      </header>

      {open && (
        <div className="aeos-shell-mobile-backdrop" onClick={() => setOpen(false)} aria-hidden="true" />
      )}

      <nav
        className="aeos-mobile-drawer"
        aria-label="Navigation"
        onClick={(e) => { if (e.target.closest('a')) setOpen(false); }}
      >
        {items.map((item, i) => <RecursiveNavItem key={i} item={item} expanded />)}
      </nav>

      <main id="aeos-main-content" className="aeos-shell-content" tabIndex={-1}>
        <div className="aeos-page-container">{children}</div>
      </main>
    </div>
  );
}

/* ─── AppShell — variant router ─────────────────────────────────────────── */
export function AppShell({ variant, children, mobileNav, ...props }) {
  const theme = useTheme();
  const isMobile = useIsMobile();
  // Page-scope boundary: contain a content-render error inside the shell so the
  // sidebar/topbar stay alive and the error shows in-place (never blank).
  const content = <AeosErrorBoundary scope="page">{children}</AeosErrorBoundary>;

  // On a phone every variant collapses to the same mobile shell.
  if (isMobile) {
    return (
      <MobileShell brand={props.brand} nav={mobileNav ?? props.nav} actions={props.actions}>
        {content}
      </MobileShell>
    );
  }

  // theme.previewShell lets Theme Studio hover-preview swap the whole shell
  // component (not just the CSS layout), so the preview matches what clicking
  // will commit.
  const shell = theme.previewShell ?? variant ?? theme.shell;
  switch (shell) {
    case 'topnav':   return <TopNavShell   {...props}>{content}</TopNavShell>;
    case 'floating': return <FloatingShell {...props}>{content}</FloatingShell>;
    case 'command':  return <CommandShell  {...props}>{content}</CommandShell>;
    default:         return <SidebarShell  {...props}>{content}</SidebarShell>;
  }
}

/* ─── SidebarShell ──────────────────────────────────────────────────────── */
export function SidebarShell({
  brand, nav = [], topbar, actions, footer,
  hideTopbar = false, maxWidth,
  expanded: expandedProp, onExpandedChange, children,
}) {
  const [localExp, setLocalExp] = useState(() => loadShellPrefs().sidebarExpanded ?? false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const expanded = expandedProp ?? localExp;

  const toggle = useCallback(() => {
    const next = !expanded;
    setLocalExp(next);
    saveShellPrefs({ sidebarExpanded: next });
    onExpandedChange?.(next);
  }, [expanded, onExpandedChange]);

  useEffect(() => {
    if (!mobileOpen) return;
    const onKey = (e) => { if (e.key === 'Escape') setMobileOpen(false); };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [mobileOpen]);

  useEffect(() => {
    document.body.style.overflow = mobileOpen ? 'hidden' : '';
    return () => { document.body.style.overflow = ''; };
  }, [mobileOpen]);

  return (
    <div
      data-aeos-shell="sidebar"
      className={cx('aeos-app-chrome-active', expanded && 'sidebar-expanded', mobileOpen && 'mobile-open')}
    >
      <a href="#aeos-main-content" className="aeos-skip-link">Skip to content</a>

      {mobileOpen && (
        <div className="aeos-shell-mobile-backdrop" onClick={() => setMobileOpen(false)} aria-hidden="true" />
      )}

      <aside className="aeos-shell-sidebar" aria-label="Side navigation">
        {brand && <div className="aeos-shell-sidebar-brand">{brand}</div>}
        {nav.map((item, i) => (
          // The mobile drawer is a full overlay — always show labels there, even
          // when the desktop rail is collapsed (icon-only is useless in a drawer).
          <RecursiveNavItem key={i} item={item} expanded={mobileOpen || expanded} />
        ))}
      </aside>

      <div className="aeos-shell-main">
        {!hideTopbar && (
          <div className="aeos-shell-topbar">
            <button
              type="button"
              className="aeos-icon-btn"
              onClick={() => { if (window.innerWidth < 768) setMobileOpen(v => !v); else toggle(); }}
              aria-label={expanded ? 'Collapse sidebar' : 'Expand sidebar'}
              aria-expanded={expanded}
            >
              <Bars3Icon className="aeos-icon-sm" aria-hidden="true" />
            </button>
            {topbar}
            <span className="aeos-shell-flex-spacer" />
            {actions}
          </div>
        )}

        <main id="aeos-main-content" className="aeos-shell-content" tabIndex={-1}>
          <div className="aeos-page-container" style={maxWidth ? { maxWidth } : undefined}>
            {children}
          </div>
        </main>

        {footer && <footer className="aeos-shell-footer">{footer}</footer>}
      </div>
    </div>
  );
}

/* ─── TopNavItem — a top-bar entry that opens a dropdown when it has children ── */
function isExternalHref(href) {
  return !href || /^(https?:|\/\/|mailto:|tel:)/.test(href);
}

function TopNavLink({ item, role, className, onNavigate }) {
  const Tag = item.href ? (isExternalHref(item.href) ? 'a' : InertiaLink) : 'button';
  return (
    <Tag
      type={item.href ? undefined : 'button'}
      href={item.href}
      role={role}
      onClick={() => { onNavigate?.(); item.onClick?.(); }}
      className={className}
      aria-current={item.active ? 'page' : undefined}
    >
      {item.icon && <item.icon className="aeos-shell-nav-icon" aria-hidden="true" />}
      <span className="aeos-shell-nav-item-label">{item.label}</span>
      {item.count != null && <span className="aeos-tab-count">{item.count}</span>}
    </Tag>
  );
}

/* A collapsible group inside the top-nav dropdown — same folder-item pattern as
   the rest of the nav (icon + label + count badge + chevron), not a static
   uppercase label. Default collapsed unless it holds the active page. */
function TopNavMenuGroup({ item, onNavigate }) {
  const [open, setOpen] = useState(item.hasActiveChild ?? false);
  return (
    <div className="aeos-topnav-menu-group">
      <button
        type="button"
        className={cx('aeos-topnav-menu-item aeos-topnav-menu-groupbtn', item.hasActiveChild && 'active-parent')}
        aria-expanded={open}
        onClick={() => setOpen(v => !v)}
      >
        {item.icon && <item.icon className="aeos-shell-nav-icon" aria-hidden="true" />}
        <span className="aeos-shell-nav-item-label">{item.label}</span>
        {item.count != null && <span className="aeos-shell-cmd-nav-count">{item.count}</span>}
        <span className={cx('aeos-shell-nav-chevron', open && 'is-open')} aria-hidden="true">
          <ChevronRightIcon />
        </span>
      </button>
      {open && (
        <div className="aeos-topnav-menu-children">
          <TopNavMenuItems items={item.children} onNavigate={onNavigate} />
        </div>
      )}
    </div>
  );
}

/* Recursive dropdown body — nested sub-groups render as collapsible folder
   groups (consistent with the sidebar), so a 3rd/4th-level item (HR → People →
   Employees → …) is grouped, not dropped. */
function TopNavMenuItems({ items, onNavigate }) {
  return items.map((child, i) =>
    (Array.isArray(child.children) && child.children.length > 0) ? (
      <TopNavMenuGroup key={i} item={child} onNavigate={onNavigate} />
    ) : (
      <TopNavLink
        key={i}
        item={child}
        role="menuitem"
        className={cx('aeos-topnav-menu-item', child.active && 'active')}
        onNavigate={onNavigate}
      />
    )
  );
}

function TopNavItem({ item }) {
  const [open, setOpen] = useState(false);
  const [coords, setCoords] = useState(null);
  const ref = useRef(null);
  const btnRef = useRef(null);
  const hasChildren = Array.isArray(item.children) && item.children.length > 0;

  // The top-bar nav is an `overflow-x: auto` scroll strip, so an absolutely
  // positioned menu would be clipped. Position it `fixed` from the trigger's
  // viewport rect instead so it can escape the scroll container.
  const place = useCallback(() => {
    const r = btnRef.current?.getBoundingClientRect();
    if (r) setCoords({ left: Math.round(r.left), top: Math.round(r.bottom) });
  }, []);

  useEffect(() => {
    if (!open) return;
    const onDoc = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
    const onKey = (e) => { if (e.key === 'Escape') setOpen(false); };
    document.addEventListener('mousedown', onDoc);
    document.addEventListener('keydown', onKey);
    return () => { document.removeEventListener('mousedown', onDoc); document.removeEventListener('keydown', onKey); };
  }, [open]);

  if (!hasChildren) {
    return (
      <TopNavLink
        item={item}
        className={cx('aeos-shell-nav-item', item.active && 'active')}
      />
    );
  }

  const show = () => { place(); setOpen(true); };

  return (
    <div
      className="aeos-topnav-dropdown"
      ref={ref}
      onMouseEnter={show}
      onMouseLeave={() => setOpen(false)}
    >
      <button
        ref={btnRef}
        type="button"
        className={cx('aeos-shell-nav-item', (item.active || item.hasActiveChild) && 'active-parent')}
        aria-haspopup="menu"
        aria-expanded={open}
        onClick={() => (open ? setOpen(false) : show())}
      >
        {item.icon && <item.icon className="aeos-shell-nav-icon" aria-hidden="true" />}
        <span className="aeos-shell-nav-item-label">{item.label}</span>
        <span className={cx('aeos-shell-nav-chevron', open && 'is-open')} aria-hidden="true">
          <ChevronDownIcon />
        </span>
      </button>

      {open && coords && (
        <div
          className="aeos-topnav-menu"
          role="menu"
          style={{ position: 'fixed', left: coords.left, top: coords.top }}
        >
          <TopNavMenuItems items={item.children} onNavigate={() => setOpen(false)} />
        </div>
      )}
    </div>
  );
}

/* ─── MeasureItem — invisible width probe matching a real top-nav entry ────── */
function MeasureItem({ item }) {
  const hasChildren = Array.isArray(item.children) && item.children.length > 0;
  return (
    <span className="aeos-shell-nav-item" aria-hidden="true">
      {item.icon && <item.icon className="aeos-shell-nav-icon" aria-hidden="true" />}
      <span className="aeos-shell-nav-item-label">{item.label}</span>
      {hasChildren && (
        <span className="aeos-shell-nav-chevron" aria-hidden="true"><ChevronDownIcon /></span>
      )}
    </span>
  );
}

/* ─── MoreMenu — Priority+ overflow entry: the items that don't fit ────────── */
function MoreMenu({ items }) {
  const [open, setOpen] = useState(false);
  const [coords, setCoords] = useState(null);
  const ref = useRef(null);
  const btnRef = useRef(null);
  const anyActive = items.some(it => it.active || it.hasActiveChild);

  const place = useCallback(() => {
    const r = btnRef.current?.getBoundingClientRect();
    if (r) setCoords({ left: Math.max(8, Math.round(r.right - 260)), top: Math.round(r.bottom) });
  }, []);

  useEffect(() => {
    if (!open) return;
    const onDoc = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
    const onKey = (e) => { if (e.key === 'Escape') setOpen(false); };
    document.addEventListener('mousedown', onDoc);
    document.addEventListener('keydown', onKey);
    return () => { document.removeEventListener('mousedown', onDoc); document.removeEventListener('keydown', onKey); };
  }, [open]);

  if (items.length === 0) return null;
  const show = () => { place(); setOpen(true); };

  return (
    <div
      className="aeos-topnav-dropdown aeos-topnav-more"
      ref={ref}
      onMouseEnter={show}
      onMouseLeave={() => setOpen(false)}
    >
      <button
        ref={btnRef}
        type="button"
        className={cx('aeos-shell-nav-item', anyActive && 'active-parent')}
        aria-haspopup="menu"
        aria-expanded={open}
        onClick={() => (open ? setOpen(false) : show())}
      >
        <span className="aeos-shell-nav-item-label">More</span>
        <span className={cx('aeos-shell-nav-chevron', open && 'is-open')} aria-hidden="true">
          <ChevronDownIcon />
        </span>
      </button>

      {open && coords && (
        <div
          className="aeos-topnav-menu aeos-topnav-menu--more"
          role="menu"
          style={{ position: 'fixed', left: coords.left, top: coords.top }}
        >
          <TopNavMenuItems items={items} onNavigate={() => setOpen(false)} />
        </div>
      )}
    </div>
  );
}

/* ─── TopNavShell ───────────────────────────────────────────────────────── */
export function TopNavShell({ brand, nav = [], actions, subbar, footer, maxWidth, children }) {
  const items = nav.filter(item => !item.divider && !item.spacer);
  const navRef = useRef(null);
  const measureRef = useRef(null);
  const [visibleCount, setVisibleCount] = useState(items.length);

  // Priority+ overflow: render as many entries as fit the nav width and
  // collapse the rest into a single "More" menu. Horizontal scrolling of a
  // primary nav hides items with poor discoverability (NN/g), so we measure an
  // off-screen copy of the full list and compute the cut-off on resize.
  useLayoutEffect(() => {
    const navEl = navRef.current;
    const measureEl = measureRef.current;
    if (!navEl || !measureEl) return;

    const RESERVE_MORE = 112; // px kept free for the "More" button when overflowing
    const SAFETY = 8;         // absorb sub-pixel probe/real width rounding so "More" never clips

    const compute = () => {
      const cs = getComputedStyle(navEl);
      const padX = (parseFloat(cs.paddingLeft) || 0) + (parseFloat(cs.paddingRight) || 0);
      const available = navEl.clientWidth - padX; // clientWidth includes padding; items can't use it
      const probes = [...measureEl.children];
      const total = probes.reduce((sum, el) => sum + el.offsetWidth, 0);

      if (total <= available - SAFETY) { setVisibleCount(probes.length); return; }

      let used = 0;
      let count = 0;
      for (let i = 0; i < probes.length; i++) {
        used += probes[i].offsetWidth;
        if (used + RESERVE_MORE + SAFETY <= available) count++;
        else break;
      }
      setVisibleCount(Math.max(0, count));
    };

    compute();
    const ro = new ResizeObserver(compute);
    ro.observe(navEl);
    return () => ro.disconnect();
  }, [items]);

  const visible = items.slice(0, visibleCount);
  const overflow = items.slice(visibleCount);

  // Note: below 768px AppShell renders <MobileShell> instead of this shell, so
  // Top-Nav needs no mobile drawer of its own.
  return (
    <div data-aeos-shell="topnav" className="aeos-app-chrome-active">
      <a href="#aeos-main-content" className="aeos-skip-link">Skip to content</a>

      <header className="aeos-shell-topbar" role="banner">
        {brand && <span className="aeos-shell-brand">{brand}</span>}

        <nav className="aeos-shell-nav" aria-label="Main navigation" ref={navRef}>
          {visible.map((item, i) => <TopNavItem key={i} item={item} />)}
          {overflow.length > 0 && <MoreMenu items={overflow} />}
        </nav>

        {/* Off-screen probe of the full list, used only to measure widths. */}
        <div className="aeos-topnav-measure" aria-hidden="true" ref={measureRef}>
          {items.map((item, i) => <MeasureItem key={i} item={item} />)}
        </div>

        <span className="aeos-shell-flex-spacer" />
        {actions && <div className="aeos-shell-actions">{actions}</div>}
      </header>

      {subbar && (
        <div className="aeos-shell-subbar" role="navigation" aria-label="Sub navigation">
          {subbar}
        </div>
      )}

      <main id="aeos-main-content" className="aeos-shell-content" tabIndex={-1}>
        <div className="aeos-page-container" style={maxWidth ? { maxWidth } : undefined}>
          {children}
        </div>
      </main>

      {footer && <footer className="aeos-shell-footer">{footer}</footer>}
    </div>
  );
}

/* ─── FloatingShell ─────────────────────────────────────────────────────── */
export function FloatingShell({
  brand, nav = [], actions, footer, maxWidth, children,
  expanded: expandedProp, onExpandedChange,
}) {
  const [localExp, setLocalExp] = useState(() => loadShellPrefs().floatingExpanded ?? false);
  const expanded = expandedProp ?? localExp;

  const toggle = useCallback(() => {
    const next = !expanded;
    setLocalExp(next);
    saveShellPrefs({ floatingExpanded: next });
    onExpandedChange?.(next);
  }, [expanded, onExpandedChange]);

  // Hover-peek: temporarily reveal labels on a collapsed rail WITHOUT changing
  // the pinned state. Accessibility standard is click-to-toggle (the toggle
  // above); hover is only an enhancement, so gate it to fine-pointer desktops
  // (never fires on touch, where there is no hover).
  const [peek, setPeek] = useState(false);
  const effectiveExpanded = expanded || peek;
  const startPeek = useCallback(() => {
    if (expanded) return;
    if (typeof window !== 'undefined' && window.matchMedia
        && !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
    setPeek(true);
  }, [expanded]);
  const endPeek = useCallback(() => setPeek(false), []);

  return (
    <div
      data-aeos-shell="floating"
      className={cx('aeos-app-chrome-active', effectiveExpanded && 'sidebar-expanded', peek && 'is-peeking')}
    >
      <a href="#aeos-main-content" className="aeos-skip-link">Skip to content</a>

      <nav
        className="aeos-shell-sidebar"
        aria-label="Side navigation"
        onMouseEnter={startPeek}
        onMouseLeave={endPeek}
      >
        {brand && <div className="aeos-shell-sidebar-brand">{brand}</div>}

        {/* Destinations scroll here; brand (top) and toggle (bottom) stay pinned
            so they never fight for space or clip. */}
        <div className="aeos-shell-sidebar-scroll">
          {nav.map((item, i) => <RecursiveNavItem key={i} item={item} expanded={effectiveExpanded} />)}
        </div>

        <div className="aeos-shell-sidebar-foot">
          <button
            type="button" className="aeos-icon-btn" onClick={toggle}
            aria-label={expanded ? 'Collapse sidebar' : 'Expand sidebar'}
            aria-expanded={expanded}
          >
            <Bars3Icon className="aeos-icon-sm" aria-hidden="true" />
          </button>
        </div>
      </nav>

      <main id="aeos-main-content" className="aeos-shell-content" tabIndex={-1}>
        {actions && (
          <div className="aeos-shell-topbar" role="toolbar" aria-label="Page actions">
            <span className="aeos-shell-flex-spacer" />
            {actions}
          </div>
        )}
        <div className="aeos-page-container" style={maxWidth ? { maxWidth } : undefined}>
          {children}
        </div>
        {footer && <footer className="aeos-shell-footer">{footer}</footer>}
      </main>
    </div>
  );
}

/* ─── CommandSection ────────────────────────────────────────────────────── */
function CommandSection({ group }) {
  // Share the persisted open state with the sidebar (same 'sec:<title>' key).
  const navKey = group.title ? `sec:${group.title}` : null;
  const [isOpen, setIsOpen] = useState(() => {
    const saved = navKey ? loadNavState()[navKey] : undefined;
    return saved !== undefined ? saved : true;
  });
  const toggle = useCallback(() => setIsOpen(v => { const next = !v; saveNavState(navKey, next); return next; }), [navKey]);

  return (
    <div className="aeos-shell-cmd-nav-group">
      {group.title && (
        <div
          className="aeos-shell-nav-section"
          role="button" tabIndex={0}
          onClick={toggle}
          onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') toggle(); }}
          aria-expanded={isOpen}
        >
          {group.title}
          <span className={cx('aeos-shell-nav-chevron', isOpen && 'is-open')} aria-hidden="true">
            <ChevronRightIcon />
          </span>
        </div>
      )}

      <div style={{
        display: 'grid',
        gridTemplateRows: isOpen ? '1fr' : '0fr',
        transition: 'grid-template-rows var(--aeos-dur-base) var(--aeos-ease-out)',
      }}>
        <div style={{ overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
          {(group.items ?? []).map((item, i) => (
            <RecursiveNavItem key={i} item={item} isCommand expanded />
          ))}
        </div>
      </div>
    </div>
  );
}

/* ─── CommandShell ──────────────────────────────────────────────────────── */
export function CommandShell({
  brand, nav = [], topbar, actions,
  rail, railTitle, railWidth,
  footer, children,
}) {
  const [localRailWidth] = useState(() => loadShellPrefs().commandRailWidth ?? railWidth ?? null);
  const effectiveRailWidth = railWidth ?? localRailWidth;

  const groups = nav.length > 0 && nav[0]?.items
    ? nav
    : [{ title: null, items: nav.filter(item => !item.divider && !item.spacer) }];

  // Note: below 768px AppShell renders <MobileShell>, so the Command shell needs
  // no mobile nav of its own.
  return (
    <div data-aeos-shell="command" className="aeos-app-chrome-active">
      <a href="#aeos-main-content" className="aeos-skip-link">Skip to content</a>

      <aside className="aeos-shell-left" aria-label="Navigation">
        {brand && <div className="aeos-shell-cmd-brand">{brand}</div>}
        {groups.map((group, i) => <CommandSection key={i} group={group} />)}
      </aside>

      <div className="aeos-shell-main">
        <header className="aeos-shell-topbar" role="banner">
          {topbar}
          <span className="aeos-shell-flex-spacer" />
          {actions}
        </header>

        <main id="aeos-main-content" className="aeos-shell-content" tabIndex={-1}>
          <div className="aeos-page-container">{children}</div>
        </main>

        {footer && <footer className="aeos-shell-footer aeos-shell-cmd-footer">{footer}</footer>}
      </div>

      {rail && (
        <aside
          className="aeos-shell-right"
          aria-label="Context panel"
          style={effectiveRailWidth ? { '--aeos-shell-rail-w': effectiveRailWidth } : undefined}
        >
          {railTitle && <header className="aeos-shell-cmd-rail-header">{railTitle}</header>}
          <div className="aeos-shell-cmd-rail-body">{rail}</div>
        </aside>
      )}
    </div>
  );
}
