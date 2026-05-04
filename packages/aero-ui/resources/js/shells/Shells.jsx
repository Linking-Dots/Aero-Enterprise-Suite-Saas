import { forwardRef, useState } from 'react';
import { useTheme } from '../theme/ThemeProvider.jsx';
import { Icon } from '../icons/icons.jsx';
import { cx } from '../components/Primitives.jsx';

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
  const [localExp, setLocalExp] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const expanded = expandedProp ?? localExp;
  const toggle = () => {
    const next = !expanded;
    setLocalExp(next);
    onExpandedChange?.(next);
  };

  return (
    <div
      data-aeos-shell="sidebar"
      className={cx(expanded && 'sidebar-expanded')}
    >
      {/* Icon rail / expanded sidebar */}
      <aside className="aeos-shell-sidebar">
        {brand && <div className="aeos-shell-sidebar-brand">{brand}</div>}
        {nav.map((item, i) => {
          if (item.divider) return <div key={i} className="aeos-shell-sidebar-divider" aria-hidden="true" />;
          if (item.spacer)  return <div key={i} className="aeos-shell-sidebar-spacer" aria-hidden="true" />;
          const Tag = item.href ? 'a' : 'button';
          return (
            <Tag
              key={i}
              type={item.href ? undefined : 'button'}
              href={item.href}
              onClick={item.onClick}
              className={cx('aeos-shell-sidebar-item', item.active && 'active')}
              title={item.label}
              aria-label={item.label}
              aria-current={item.active ? 'page' : undefined}
            >
              {item.icon && <Icon name={item.icon} size={18} />}
              <span className="aeos-shell-sidebar-label">{item.label}</span>
            </Tag>
          );
        })}
      </aside>

      {/* Main column */}
      <div className="aeos-shell-main">
        {!hideTopbar && (
          <div className="aeos-shell-topbar">
            <button
              type="button"
              className="aeos-icon-btn"
              onClick={() => {
                const isMobile = window.innerWidth < 768;
                if (isMobile) {
                  setMobileOpen(v => !v);
                } else {
                  toggle();
                }
              }}
              aria-label={expanded ? 'Collapse sidebar' : 'Expand sidebar'}
            >
              <Icon name="menu" size={16} />
            </button>
            {topbar}
            <span style={{ flex: 1 }} />
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
  const [localExp, setLocalExp] = useState(false);
  const expanded = expandedProp ?? localExp;
  const toggle = () => {
    const next = !expanded;
    setLocalExp(next);
    onExpandedChange?.(next);
  };

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
              <Icon name="menu" size={16} />
            </button>
          </div>
        )}
        {nav.map((item, i) => {
          if (item.divider) return <div key={i} className="aeos-shell-sidebar-divider" aria-hidden="true" />;
          if (item.spacer)  return <div key={i} className="aeos-shell-sidebar-spacer" aria-hidden="true" />;
          const Tag = item.href ? 'a' : 'button';
          return (
            <Tag
              key={i}
              type={item.href ? undefined : 'button'}
              href={item.href}
              onClick={item.onClick}
              className={cx('aeos-shell-sidebar-item', item.active && 'active')}
              title={item.label}
              aria-label={item.label}
              aria-current={item.active ? 'page' : undefined}
            >
              {item.icon && <Icon name={item.icon} size={18} />}
              <span className="aeos-shell-sidebar-label">{item.label}</span>
            </Tag>
          );
        })}
      </nav>
      <main className="aeos-shell-content">
        {actions && (
          <div className="aeos-shell-topbar">
            <span style={{ flex: 1 }} />
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

/* ── CommandShell ───────────────────────────────────────────────── */
export function CommandShell({
  brand, nav = [], topbar, actions,
  rail, railTitle, railWidth,
  footer, children,
}) {
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
          <div key={gi} className="aeos-shell-cmd-nav-group">
            {group.title && (
              <div className="aeos-shell-nav-section">{group.title}</div>
            )}
            {(group.items ?? []).map((item, i) => {
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
                  {item.icon && <Icon name={item.icon} size={15} />}
                  <span>{item.label}</span>
                  {item.count != null && (
                    <span className="aeos-shell-cmd-nav-count">{item.count}</span>
                  )}
                </Tag>
              );
            })}
          </div>
        ))}
      </aside>

      {/* Center main panel */}
      <div className="aeos-shell-main">
        <header className="aeos-shell-topbar">
          {topbar}
          <span style={{ flex: 1 }} />
          {actions}
        </header>
        <div className="aeos-shell-content">{children}</div>
      </div>

      {/* Right context rail */}
      {rail && (
        <aside
          className="aeos-shell-right"
          style={railWidth ? { width: railWidth } : undefined}
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
