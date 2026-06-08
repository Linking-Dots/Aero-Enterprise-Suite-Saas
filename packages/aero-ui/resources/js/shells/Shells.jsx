import { forwardRef, useState, useEffect, useCallback } from 'react';
import { useTheme } from '../theme/ThemeProvider.jsx';
import { Bars3Icon, ChevronRightIcon, ChevronDownIcon } from '@heroicons/react/24/outline';
import { cx } from '../components/Primitives.jsx';
import { Tooltip } from '../components/Overlays.jsx';

/* ── Shell preference persistence ───────────────────────────────── */
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
  } catch { /* quota/private mode — not fatal */ }
}

/* ── RecursiveNavItem ───────────────────────────────────────────── */
function RecursiveNavItem({ item, depth = 0, isCommand = false, expanded = true }) {
  const [isOpen, setIsOpen] = useState(item.hasActiveChild || false);
  const hasChildren = item.children && item.children.length > 0;

  if (item.divider) return <div className="aeos-shell-sidebar-divider" aria-hidden="true" />;
  if (item.spacer)  return <div className="aeos-shell-sidebar-spacer" aria-hidden="true" />;

  const toggle = (e) => {
    e.preventDefault();
    if (expanded || isCommand) setIsOpen(!isOpen);
  };

  const Tag = item.href && !hasChildren ? 'a' : 'button';

  // Depth indentation via CSS class — no inline style, no raw rem values
  const depthClass = depth > 0 && (expanded || isCommand) ? `aeos-nav-depth-${Math.min(depth, 3)}` : undefined;

  const itemClass = cx(
    isCommand ? 'aeos-shell-nav-item' : 'aeos-shell-sidebar-item',
    item.active && !hasChildren && 'active',
    item.hasActiveChild && 'active-parent',
    depthClass
  );

  const tagContent = (
    <Tag
      type={Tag === 'button' ? 'button' : undefined}
      href={Tag === 'a' ? item.href : undefined}
      onClick={hasChildren ? toggle : item.onClick}
      className={itemClass}
      style={{ position: 'relative' }}
      title={item.label}
      aria-label={item.label}
      aria-current={item.active && !hasChildren ? 'page' : undefined}
      aria-expanded={hasChildren ? isOpen : undefined}
    >
      {/* Active indicator — rendered as CSS class, no inline style */}
      {item.active && !hasChildren && (
        <div className="aeos-nav-active-indicator" aria-hidden="true" />
      )}

      {/* Icon — size driven by CSS (.aeos-shell-nav-icon), not inline style */}
      {item.icon && (
        <item.icon
          className={cx('aeos-shell-nav-icon', depth > 0 && 'aeos-shell-nav-icon--child')}
          aria-hidden="true"
        />
      )}

      {(!isCommand && expanded) || isCommand ? (
        <>
          <span className={isCommand ? '' : 'aeos-shell-sidebar-label'} style={{ flex: 1, textAlign: 'left', whiteSpace: 'nowrap' }}>
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
      ) : null}
    </Tag>
  );

  const wrappedContent = (!expanded && !isCommand && depth === 0)
    ? <Tooltip label={item.label} side="right">{tagContent}</Tooltip>
    : tagContent;

  return (
    <div className="aeos-nav-item-wrapper">
      {wrappedContent}

      {hasChildren && (expanded || isCommand) && (
        <div
          className="aeos-shell-sidebar-children"
          style={{
            display: 'grid',
            gridTemplateRows: isOpen ? '1fr' : '0fr',
            transition: `grid-template-rows var(--aeos-dur-base) var(--aeos-ease-out)`,
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

/* ── AppShell — auto-routes to the correct shell variant ────────── */
export function AppShell({ variant, ...props }) {
  const theme = useTheme();
  const shell = variant ?? theme.shell;
  switch (shell) {
    case 'topnav':   return <TopNavShell   {...props} />;
    case 'floating': return <FloatingShell {...props} />;
    case 'command':  return <CommandShell  {...props} />;
    default:         return <SidebarShell  {...props} />;
  }
}

/* ── SidebarShell ───────────────────────────────────────────────── */
export function SidebarShell({
  brand, nav = [], topbar, actions, footer,
  hideTopbar, maxWidth, children,
  expanded: expandedProp, onExpandedChange,
}) {
  // Load persisted collapse state from localStorage
  const [localExp, setLocalExp] = useState(() => {
    try { return loadShellPrefs().sidebarExpanded ?? false; } catch { return false; }
  });
  const [mobileOpen, setMobileOpen] = useState(false);
  const expanded = expandedProp ?? localExp;

  const toggle = useCallback(() => {
    const next = !expanded;
    setLocalExp(next);
    saveShellPrefs({ sidebarExpanded: next });
    onExpandedChange?.(next);
  }, [expanded, onExpandedChange]);

  // Close mobile overlay on Escape
  useEffect(() => {
    if (!mobileOpen) return;
    const onKey = (e) => { if (e.key === 'Escape') setMobileOpen(false); };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [mobileOpen]);

  return (
    <div
      data-aeos-shell="sidebar"
      className={cx(expanded && 'sidebar-expanded', mobileOpen && 'mobile-open')}
    >
      {/* Mobile overlay backdrop */}
      {mobileOpen && (
        <div
          className="aeos-shell-mobile-backdrop"
          onClick={() => setMobileOpen(false)}
          aria-hidden="true"
        />
      )}

      {/* Icon rail / expanded sidebar */}
      <aside className="aeos-shell-sidebar" aria-label="Side navigation">
        {brand && <div className="aeos-shell-sidebar-brand">{brand}</div>}
        {nav.map((item, i) => (
          <RecursiveNavItem key={i} item={item} expanded={expanded} />
        ))}
      </aside>

      {/* Main column */}
      <div className="aeos-shell-main">
        {!hideTopbar && (
          <div className="aeos-shell-topbar">
            <button
              type="button"
              className="aeos-icon-btn"
              onClick={() => {
                if (window.innerWidth < 768) {
                  setMobileOpen(v => !v);
                } else {
                  toggle();
                }
              }}
              aria-label={expanded ? 'Collapse sidebar' : 'Expand sidebar'}
            >
              <Bars3Icon className="aeos-icon-sm" aria-hidden="true" />
            </button>
            {topbar}
            <span className="aeos-shell-flex-spacer" />
            {actions}
          </div>
        )}
        <main className="aeos-shell-content">
          <div className="aeos-page-container" style={maxWidth ? { maxWidth } : undefined}>
            {children}
          </div>
        </main>
        {footer && <footer className="aeos-shell-footer">{footer}</footer>}
      </div>
    </div>
  );
}

/* ── TopNavShell ────────────────────────────────────────────────── */
export function TopNavShell({ brand, nav = [], actions, subbar, footer, maxWidth, children }) {
  return (
    <div data-aeos-shell="topnav">
      <header className="aeos-shell-topbar">
        {brand && <a className="aeos-shell-brand">{brand}</a>}
        <nav className="aeos-shell-nav" aria-label="Main navigation">
          {nav.map((item, i) => {
            const Tag = item.href ? 'a' : 'button';
            return (
              <Tag
                key={i}
                type={item.href ? undefined : 'button'}
                href={item.href}
                onClick={item.onClick}
                className={cx('aeos-shell-nav-item', item.active && 'active')}
                aria-current={item.active ? 'page' : undefined}
              >
                {item.label}
              </Tag>
            );
          })}
        </nav>
        {actions && <div className="aeos-shell-actions">{actions}</div>}
      </header>
      {subbar && <div className="aeos-shell-subbar">{subbar}</div>}
      <main className="aeos-shell-content">
        <div className="aeos-page-container" style={maxWidth ? { maxWidth } : undefined}>
          {children}
        </div>
      </main>
      {footer && <footer className="aeos-shell-footer">{footer}</footer>}
    </div>
  );
}

/* ── FloatingShell ──────────────────────────────────────────────── */
export function FloatingShell({
  brand, nav = [], actions, footer, maxWidth, children,
  expanded: expandedProp, onExpandedChange,
}) {
  const [localExp, setLocalExp] = useState(() => {
    try { return loadShellPrefs().floatingExpanded ?? false; } catch { return false; }
  });
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
      className={cx(expanded && 'sidebar-expanded')}
    >
      <nav className="aeos-shell-sidebar" aria-label="Side navigation">
        {brand && (
          <div className="aeos-shell-sidebar-brand">
            {brand}
            <button
              type="button"
              className="aeos-icon-btn"
              onClick={toggle}
              aria-label={expanded ? 'Collapse sidebar' : 'Expand sidebar'}
            >
              <Bars3Icon className="aeos-icon-sm" aria-hidden="true" />
            </button>
          </div>
        )}
        {nav.map((item, i) => (
          <RecursiveNavItem key={i} item={item} expanded={expanded} />
        ))}
      </nav>
      <main className="aeos-shell-content">
        {actions && (
          <div className="aeos-shell-topbar">
            <span className="aeos-shell-flex-spacer" />
            {actions}
          </div>
        )}
        <div className="aeos-page-container" style={maxWidth ? { maxWidth } : undefined}>
          {children}
        </div>
      </main>
    </div>
  );
}

/* ── CommandSection ─────────────────────────────────────────────── */
function CommandSection({ group }) {
  const [isOpen, setIsOpen] = useState(true);
  return (
    <div className="aeos-shell-cmd-nav-group">
      {group.title && (
        <div
          className="aeos-shell-nav-section"
          onClick={() => setIsOpen(!isOpen)}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') setIsOpen(!isOpen); }}
          aria-expanded={isOpen}
        >
          {group.title}
          <span className={cx('aeos-shell-nav-chevron', isOpen && 'is-open')} aria-hidden="true">
            <ChevronRightIcon />
          </span>
        </div>
      )}
      <div
        style={{
          display: 'grid',
          gridTemplateRows: isOpen ? '1fr' : '0fr',
          transition: `grid-template-rows var(--aeos-dur-base) var(--aeos-ease-out)`,
        }}
      >
        <div style={{ overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
          {(group.items ?? []).map((item, i) => (
            <RecursiveNavItem key={i} item={item} isCommand={true} expanded={true} />
          ))}
        </div>
      </div>
    </div>
  );
}

/* ── CommandShell ───────────────────────────────────────────────── */
export function CommandShell({
  brand, nav = [], topbar, actions,
  rail, railTitle, railWidth,
  footer, children,
}) {
  // Persist rail width to localStorage
  const [localRailWidth, setLocalRailWidth] = useState(() => {
    try { return loadShellPrefs().commandRailWidth ?? railWidth ?? null; } catch { return railWidth ?? null; }
  });

  const effectiveRailWidth = railWidth ?? localRailWidth;

  // Auto-wrap flat nav items into a single default group for backward compatibility
  const groups = nav.length > 0 && nav[0].items
    ? nav
    : [{ title: null, items: nav.filter(item => !item.divider && !item.spacer) }];

  return (
    <div data-aeos-shell="command">
      {/* Left nav panel */}
      <aside className="aeos-shell-left" aria-label="Navigation">
        {brand && <div className="aeos-shell-cmd-brand">{brand}</div>}
        {groups.map((group, gi) => (
          <CommandSection key={gi} group={group} />
        ))}
      </aside>

      {/* Center main panel */}
      <div className="aeos-shell-main">
        <header className="aeos-shell-topbar">
          {topbar}
          <span className="aeos-shell-flex-spacer" />
          {actions}
        </header>
        <div className="aeos-shell-content">{children}</div>
      </div>

      {/* Right context rail */}
      {rail && (
        <aside
          className="aeos-shell-right"
          style={effectiveRailWidth ? { width: effectiveRailWidth } : undefined}
          aria-label="Context panel"
        >
          {railTitle && <header className="aeos-shell-cmd-rail-header">{railTitle}</header>}
          <div className="aeos-shell-cmd-rail-body">{rail}</div>
        </aside>
      )}

      {footer && <footer className="aeos-shell-footer aeos-shell-cmd-footer">{footer}</footer>}
    </div>
  );
}
