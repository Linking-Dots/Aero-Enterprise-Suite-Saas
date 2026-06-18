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

import { forwardRef, useState, useEffect, useCallback, useRef } from 'react';
import { Link as InertiaLink } from '@inertiajs/react';
import { useTheme } from '../theme/ThemeProvider.jsx';
import { Bars3Icon, ChevronRightIcon } from '@heroicons/react/24/outline';
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

/* ─── RecursiveNavItem ──────────────────────────────────────────────────── */
function RecursiveNavItem({ item, depth = 0, isCommand = false, expanded = true }) {
  const [isOpen, setIsOpen] = useState(item.hasActiveChild ?? false);
  const hasChildren = Array.isArray(item.children) && item.children.length > 0;

  if (item.divider) return <div className="aeos-shell-sidebar-divider" aria-hidden="true" />;
  if (item.spacer)  return <div className="aeos-shell-sidebar-spacer"  aria-hidden="true" />;

  const toggle = useCallback((e) => {
    e.preventDefault();
    if (expanded || isCommand) setIsOpen(v => !v);
  }, [expanded, isCommand]);

  const isLink     = Boolean(item.href) && !hasChildren;
  const isInternal = isLink && !/^(https?:|\/\/|mailto:|tel:)/.test(item.href);
  const Tag        = isLink ? (isInternal ? InertiaLink : 'a') : 'button';

  const depthClass = depth > 0 && (expanded || isCommand)
    ? `aeos-nav-depth-${Math.min(depth, 3)}`
    : undefined;

  const baseClass = isCommand ? 'aeos-shell-nav-item' : 'aeos-shell-sidebar-item';
  const itemClass = cx(
    baseClass,
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

      {hasChildren && (expanded || isCommand) && (
        <div
          className="aeos-shell-sidebar-children"
          // Collapsed children are visually clipped (grid 0fr) but stay in the DOM.
          // `inert` removes them from tab order, pointer hit-testing and the a11y
          // tree so a collapsed group can't steal focus or intercept clicks.
          {...(isOpen ? {} : { inert: '' })}
          style={{
            display: 'grid',
            gridTemplateRows: isOpen ? '1fr' : '0fr',
            transition: 'grid-template-rows var(--aeos-dur-base) var(--aeos-ease-out)',
          }}
        >
          <div style={{ overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
            {item.children.map((child, i) => (
              <RecursiveNavItem key={i} item={child} depth={depth + 1} isCommand={isCommand} expanded={expanded} />
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

/* ─── AppShell — variant router ─────────────────────────────────────────── */
export function AppShell({ variant, children, ...props }) {
  const theme = useTheme();
  const shell = variant ?? theme.shell;
  // Page-scope boundary: contain a content-render error inside the shell so the
  // sidebar/topbar stay alive and the error shows in-place (never blank).
  const content = <AeosErrorBoundary scope="page">{children}</AeosErrorBoundary>;
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

/* ─── TopNavShell ───────────────────────────────────────────────────────── */
export function TopNavShell({ brand, nav = [], actions, subbar, footer, maxWidth, children }) {
  return (
    <div data-aeos-shell="topnav" className="aeos-app-chrome-active">
      <a href="#aeos-main-content" className="aeos-skip-link">Skip to content</a>

      <header className="aeos-shell-topbar" role="banner">
        {brand && <span className="aeos-shell-brand">{brand}</span>}

        <nav className="aeos-shell-nav" aria-label="Main navigation">
          {nav.map((item, i) => {
            const isInternal = item.href && !/^(https?:|\/\/|mailto:|tel:)/.test(item.href);
            const Tag = item.href ? (isInternal ? InertiaLink : 'a') : 'button';
            return (
              <Tag
                key={i}
                type={item.href ? undefined : 'button'}
                href={item.href}
                onClick={item.onClick}
                className={cx('aeos-shell-nav-item', item.active && 'active')}
                aria-current={item.active ? 'page' : undefined}
              >
                {item.icon && <item.icon className="aeos-shell-nav-icon" aria-hidden="true" />}
                {item.label}
                {item.count != null && <span className="aeos-tab-count">{item.count}</span>}
              </Tag>
            );
          })}
        </nav>

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

  return (
    <div
      data-aeos-shell="floating"
      className={cx('aeos-app-chrome-active', expanded && 'sidebar-expanded')}
    >
      <a href="#aeos-main-content" className="aeos-skip-link">Skip to content</a>

      <nav className="aeos-shell-sidebar" aria-label="Side navigation">
        {brand && (
          <div className="aeos-shell-sidebar-brand">
            {brand}
            <button
              type="button" className="aeos-icon-btn" onClick={toggle}
              aria-label={expanded ? 'Collapse sidebar' : 'Expand sidebar'}
              aria-expanded={expanded}
            >
              <Bars3Icon className="aeos-icon-sm" aria-hidden="true" />
            </button>
          </div>
        )}
        {nav.map((item, i) => <RecursiveNavItem key={i} item={item} expanded={expanded} />)}
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
  const [isOpen, setIsOpen] = useState(true);
  const toggle = useCallback(() => setIsOpen(v => !v), []);

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
          {children}
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
