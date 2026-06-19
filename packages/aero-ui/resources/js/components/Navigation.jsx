import { forwardRef, useState } from 'react';
import { Link as InertiaLink } from '@inertiajs/react';
import { ChevronRightIcon, ChevronLeftIcon } from '@heroicons/react/24/outline';
import { cx } from './Primitives.jsx';
import { Badge } from './Display.jsx';
import { Icon } from '../icons/icons.jsx';

/** Tabs — horizontal tab strip (controlled or uncontrolled). */
export function Tabs({ tabs = [], value, defaultValue, onChange, className, children }) {
  const [internal, setInternal] = useState(defaultValue ?? tabs[0]?.value);
  const active = value ?? internal;
  const setActive = v => { if (value === undefined) setInternal(v); onChange?.(v); };

  return (
    <div className={cx('aeos-tabs', className)}>
      <div role="tablist" className="aeos-tabs-list">
        {tabs.map(t => (
          <button
            key={t.value}
            role="tab"
            type="button"
            aria-selected={active === t.value}
            className={cx('aeos-tab', active === t.value && 'is-active')}
            onClick={() => setActive(t.value)}
          >
            {t.icon && <Icon name={t.icon} size={14} />}
            {t.label}
            {t.count != null && <span className="aeos-tab-count">{t.count}</span>}
          </button>
        ))}
      </div>
      {children}
    </div>
  );
}

/** Breadcrumb — navigation trail with chevron separators. */
export function Breadcrumb({ items = [], className }) {
  return (
    <nav aria-label="Breadcrumb" className={cx('aeos-breadcrumb', className)}>
      {items.map((it, i) => (
        <span key={i} className="aeos-breadcrumb-item">
          {i > 0 && (
            <span className="aeos-breadcrumb-sep" aria-hidden="true">
              <ChevronRightIcon className="aeos-icon-xs" />
            </span>
          )}
          {it.href ? (() => {
            const isInternal = !it.href.startsWith('http://') && !it.href.startsWith('https://') && !it.href.startsWith('//') && !it.href.startsWith('mailto:') && !it.href.startsWith('tel:');
            const LinkTag = isInternal ? InertiaLink : 'a';
            return <LinkTag href={it.href} className={cx('aeos-breadcrumb-link', i === items.length - 1 && 'is-current')}>{it.label}</LinkTag>;
          })() : (
            <span className={cx('aeos-breadcrumb-text', i === items.length - 1 && 'is-current')}>{it.label}</span>
          )}
        </span>
      ))}
    </nav>
  );
}

/** NavItem — sidebar or panel navigation link. */
export const NavItem = forwardRef(function NavItem(
  { icon, label, href, active, onClick, count, badge, indent, className, ...rest },
  ref
) {
  const isInternal = href && !href.startsWith('http://') && !href.startsWith('https://') && !href.startsWith('//') && !href.startsWith('mailto:') && !href.startsWith('tel:');
  const Tag = href ? (isInternal ? InertiaLink : 'a') : 'button';
  return (
    <Tag
      ref={ref}
      type={href ? undefined : 'button'}
      href={href}
      onClick={onClick}
      className={cx('aeos-nav-item', active && 'is-active', indent && 'is-indented', className)}
      aria-current={active ? 'page' : undefined}
      {...rest}
    >
      {icon && <Icon name={icon} size={16} />}
      <span className="aeos-nav-item-label">{label}</span>
      {count != null && <span className="aeos-nav-item-count">{count}</span>}
      {badge && <Badge intent={badge.intent ?? 'cyan'} size="sm" mono>{badge.label}</Badge>}
    </Tag>
  );
});

/** NavGroup — labelled group of NavItems. */
export function NavGroup({ title, children, className }) {
  return (
    <div className={cx('aeos-nav-group', className)}>
      {title && <div className="aeos-nav-group-title">{title}</div>}
      <div className="aeos-nav-group-items">{children}</div>
    </div>
  );
}

/** SectionHeader — eyebrow + title + description + actions row. */
export function SectionHeader({ eyebrow, title, description, actions, divider, className }) {
  return (
    <div className={cx('aeos-section-header', divider && 'has-divider', className)}>
      <div>
        {eyebrow && <div className="aeos-eyebrow aeos-eyebrow-primary">{eyebrow}</div>}
        {title && <h2 className="aeos-section-title">{title}</h2>}
        {description && <p className="aeos-section-desc">{description}</p>}
      </div>
      {actions && <div className="aeos-section-actions">{actions}</div>}
    </div>
  );
}

/** PageHeader — page-level header with breadcrumb, title, status chip, and actions. */
export function PageHeader({ breadcrumb, title, eyebrow, description, status, actions, tabs, className }) {
  return (
    <header className={cx('aeos-page-header', className)}>
      {breadcrumb && <Breadcrumb items={breadcrumb} />}
      <div className="aeos-page-header-row">
        <div className="aeos-page-header-text">
          {eyebrow && <div className="aeos-eyebrow aeos-eyebrow-primary">{eyebrow}</div>}
          <div className="aeos-page-header-title-row">
            <h1 className="aeos-page-title">{title}</h1>
            {status && <div className="aeos-page-status">{status}</div>}
          </div>
          {description && <p className="aeos-page-desc">{description}</p>}
        </div>
        {actions && <div className="aeos-page-actions">{actions}</div>}
      </div>
      {tabs && <div className="aeos-page-header-tabs">{tabs}</div>}
    </header>
  );
}

/** Pagination — prev/next page controls. */
export function Pagination({ page = 1, total = 1, onChange, className }) {
  const go = p => onChange?.(Math.max(1, Math.min(total, p)));
  return (
    <nav
      role="navigation"
      aria-label="Pagination"
      className={cx('aeos-pagination', className)}
    >
      <button
        type="button"
        className="aeos-btn aeos-btn-ghost aeos-btn-sm"
        onClick={() => go(page - 1)}
        disabled={page <= 1}
        aria-label="Previous page"
      >
        <ChevronLeftIcon className="aeos-icon-sm" />
        Prev
      </button>
      <span className="aeos-pagination-status aeos-text-mono">
        {page} / {total}
      </span>
      <button
        type="button"
        className="aeos-btn aeos-btn-ghost aeos-btn-sm"
        onClick={() => go(page + 1)}
        disabled={page >= total}
        aria-label="Next page"
      >
        Next
        <ChevronRightIcon className="aeos-icon-sm" />
      </button>
    </nav>
  );
}

/* ════════════════════════════════════════════════════════════════════
   STEPS — horizontal step progress indicator
   ════════════════════════════════════════════════════════════════════ */

export function Steps({ steps = [], currentStep, className }) {
  const currentIndex = steps.findIndex(s =>
    typeof s === 'string' ? s === currentStep : s?.key === currentStep
  );

  return (
    <nav className={cx('aeos-steps', className)} aria-label="Signup progress">
      {steps.map((step, i) => {
        const key = typeof step === 'string' ? step : (step?.key ?? i);
        const label = typeof step === 'string' ? step : (step?.label ?? step?.key ?? '');
        const isDone = i < currentIndex;
        const isCurrent = i === currentIndex;
        const state = isDone ? 'is-done' : isCurrent ? 'is-current' : 'is-pending';

        return (
          <div key={key || i} className={cx('aeos-step', state)}>
            {i > 0 && <div className="aeos-step-line" aria-hidden="true" />}
            <div className="aeos-step-dot" aria-current={isCurrent ? 'step' : undefined}>
              {isDone ? (
                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
                  <path d="M2 5l2 2 4-4" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" />
                </svg>
              ) : (
                <span>{i + 1}</span>
              )}
            </div>
            <span className="aeos-step-label">{label}</span>
          </div>
        );
      })}
    </nav>
  );
}
