import { forwardRef, useCallback, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { XMarkIcon, SparklesIcon, ExclamationTriangleIcon, CheckCircleIcon, ExclamationCircleIcon } from '@heroicons/react/24/outline';
import { cx } from './Primitives.jsx';
import { Icon } from '../icons/icons.jsx';

/* ── Modal ────────────────────────────────────────────────────── */
const MODAL_SIZE = { sm: 'aeos-modal-sm', md: 'aeos-modal-md', lg: 'aeos-modal-lg' };

export function Modal({ open, onClose, title, description, footer, size = 'md', children }) {
  useEffect(() => {
    if (!open) return;
    const onKey = e => { if (e.key === 'Escape') onClose?.(); };
    document.addEventListener('keydown', onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = prev;
    };
  }, [open, onClose]);

  if (!open) return null;
  return createPortal(
    <div className="aeos-modal-root" role="dialog" aria-modal="true" aria-labelledby={title ? 'aeos-modal-title' : undefined}>
      <div className="aeos-modal-backdrop aeos-anim-fade-in" onClick={onClose} />
      <div className={cx('aeos-modal aeos-glass-strong aeos-anim-pop-in', MODAL_SIZE[size] ?? MODAL_SIZE.md)}>
        {(title || onClose) && (
          <div className="aeos-modal-header">
            <div>
              {title && <h3 id="aeos-modal-title" className="aeos-modal-title">{title}</h3>}
              {description && <p className="aeos-modal-desc">{description}</p>}
            </div>
            {onClose && (
              <button type="button" className="aeos-icon-btn" onClick={onClose} aria-label="Close modal">
                <XMarkIcon className="aeos-icon-sm" />
              </button>
            )}
          </div>
        )}
        <div className="aeos-modal-body">{children}</div>
        {footer && <div className="aeos-modal-footer">{footer}</div>}
      </div>
    </div>,
    document.body
  );
}

/* ── Drawer ───────────────────────────────────────────────────── */
export function Drawer({ open, onClose, side = 'right', width = 420, title, children, footer }) {
  useEffect(() => {
    if (!open) return;
    const onKey = e => { if (e.key === 'Escape') onClose?.(); };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [open, onClose]);

  if (!open) return null;
  return createPortal(
    <div className="aeos-drawer-root" role="dialog" aria-modal="true">
      <div className="aeos-modal-backdrop aeos-anim-fade-in" onClick={onClose} />
      <aside
        className={cx('aeos-drawer aeos-glass-strong', `aeos-drawer-${side}`)}
        style={{ width }}
      >
        {title && (
          <header className="aeos-drawer-header">
            <h3 className="aeos-drawer-title">{title}</h3>
            <button type="button" className="aeos-icon-btn" onClick={onClose} aria-label="Close drawer">
              <XMarkIcon className="aeos-icon-sm" />
            </button>
          </header>
        )}
        <div className="aeos-drawer-body">{children}</div>
        {footer && <div className="aeos-drawer-footer">{footer}</div>}
      </aside>
    </div>,
    document.body
  );
}

/* ── Tooltip ──────────────────────────────────────────────────── */
export function Tooltip({ label, side = 'top', children }) {
  return (
    <span className="aeos-tooltip-wrap" data-side={side}>
      {children}
      <span className="aeos-tooltip" role="tooltip">{label}</span>
    </span>
  );
}

/* ── Popover ──────────────────────────────────────────────────── */
export function Popover({ trigger, children, side = 'bottom', align = 'start' }) {
  const [open, setOpen] = useState(false);
  const [pos, setPos]   = useState(null);
  const triggerRef = useRef(null);
  const popRef     = useRef(null);

  // Position the (portaled) popover relative to the trigger, flipping above when
  // there isn't room below. Portaling to <body> means it escapes any ancestor
  // overflow:hidden (tables, cards, scroll areas) that would otherwise clip it.
  const place = useCallback(() => {
    const el = triggerRef.current;
    if (!el) return;
    const r = el.getBoundingClientRect();
    const gap = 6;
    const spaceBelow = window.innerHeight - r.bottom;
    const flipUp = spaceBelow < 220 && r.top > spaceBelow;
    setPos({
      top:    flipUp ? null : Math.round(r.bottom + gap),
      bottom: flipUp ? Math.round(window.innerHeight - r.top + gap) : null,
      left:   align === 'end' ? null : Math.round(r.left),
      right:  align === 'end' ? Math.round(window.innerWidth - r.right) : null,
    });
  }, [align]);

  const toggle = () => setOpen(o => { if (!o) place(); return !o; });

  useEffect(() => {
    if (!open) return;
    place();
    const onDown = e => {
      if (triggerRef.current?.contains(e.target) || popRef.current?.contains(e.target)) return;
      setOpen(false);
    };
    const onReflow = () => place();
    document.addEventListener('mousedown', onDown);
    window.addEventListener('resize', onReflow);
    window.addEventListener('scroll', onReflow, true);
    return () => {
      document.removeEventListener('mousedown', onDown);
      window.removeEventListener('resize', onReflow);
      window.removeEventListener('scroll', onReflow, true);
    };
  }, [open, place]);

  return (
    <span className="aeos-popover-wrap">
      <span ref={triggerRef} onClick={toggle}>{trigger}</span>
      {open && pos && createPortal(
        <div
          ref={popRef}
          className="aeos-popover aeos-glass-strong aeos-anim-pop-in"
          style={{
            position: 'fixed',
            top:    pos.top    ?? undefined,
            bottom: pos.bottom ?? undefined,
            left:   pos.left   ?? undefined,
            right:  pos.right  ?? undefined,
          }}
        >
          {typeof children === 'function' ? children({ close: () => setOpen(false) }) : children}
        </div>,
        document.body,
      )}
    </span>
  );
}

/* ── Menu ─────────────────────────────────────────────────────── */
export function Menu({ trigger, items = [], side = 'bottom', align = 'start' }) {
  return (
    <Popover trigger={trigger} side={side} align={align}>
      {({ close }) => (
        <ul className="aeos-menu" role="menu">
          {items.map((it, i) =>
            it.divider
              ? <li key={i} className="aeos-menu-divider" role="separator" />
              : (
                <li
                  key={i}
                  role="menuitem"
                  className={cx('aeos-menu-item', it.danger && 'is-danger')}
                  onClick={() => { it.onClick?.(); close(); }}
                  tabIndex={0}
                  onKeyDown={e => { if (e.key === 'Enter') { it.onClick?.(); close(); } }}
                >
                  {it.icon && <Icon name={it.icon} size={14} />}
                  <span>{it.label}</span>
                  {it.shortcut && <kbd className="aeos-kbd">{it.shortcut}</kbd>}
                </li>
              )
          )}
        </ul>
      )}
    </Popover>
  );
}

/* ── Banner ───────────────────────────────────────────────────── */
const BANNER_ICON = {
  info: <SparklesIcon className="aeos-icon-md" />,
  success: <CheckCircleIcon className="aeos-icon-md" />,
  warning: <ExclamationTriangleIcon className="aeos-icon-md" />,
  danger: <ExclamationCircleIcon className="aeos-icon-md" />,
};

export function Banner({ intent = 'info', icon, title, children, actions, onClose, className }) {
  const iconComponent = icon ?? BANNER_ICON[intent] ?? BANNER_ICON.info;
  
  return (
    <div
      className={cx('aeos-banner', `aeos-banner-${intent}`, className)}
      role={intent === 'danger' ? 'alert' : 'status'}
    >
      <div className="aeos-banner-icon">
        {typeof iconComponent === 'string' ? (
          <Icon name={iconComponent} size={20} className="aeos-icon-md" />
        ) : (
          iconComponent
        )}
      </div>
      <div className="aeos-banner-body">
        {title && <strong className="aeos-banner-title">{title}</strong>}
        {children && <div className="aeos-banner-text">{children}</div>}
      </div>
      {actions && <div className="aeos-banner-actions">{actions}</div>}
      {onClose && (
        <button type="button" className="aeos-icon-btn" onClick={onClose} aria-label="Dismiss">
          <XMarkIcon className="aeos-icon-sm" />
        </button>
      )}
    </div>
  );
}

/* ── ConfirmDialog ────────────────────────────────────────────── */
export function ConfirmDialog({
  open, onClose, onConfirm,
  title, description,
  confirmLabel = 'Confirm', cancelLabel = 'Cancel',
  intent = 'danger',
}) {
  useEffect(() => {
    if (!open) return;
    const onKey = e => { if (e.key === 'Escape') onClose?.(); };
    document.addEventListener('keydown', onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = prev;
    };
  }, [open, onClose]);

  if (!open) return null;
  return createPortal(
    <div className="aeos-modal-root" role="alertdialog" aria-modal="true" aria-labelledby="aeos-confirm-title">
      <div className="aeos-modal-backdrop aeos-anim-fade-in" onClick={onClose} />
      <div className="aeos-modal aeos-glass-strong aeos-anim-pop-in aeos-modal-confirm">
        <div className="aeos-confirm-dialog">
          <div className="aeos-confirm-icon">
            {intent === 'danger' ? <ExclamationTriangleIcon className="aeos-icon-xl" /> : <SparklesIcon className="aeos-icon-xl" />}
          </div>
          <div className="aeos-confirm-body">
            <h3 id="aeos-confirm-title" className="aeos-confirm-title">{title}</h3>
            {description && <p className="aeos-confirm-desc">{description}</p>}
          </div>
          <div className="aeos-confirm-actions">
            <button type="button" className="aeos-btn aeos-btn-ghost" onClick={onClose}>
              {cancelLabel}
            </button>
            <button
              type="button"
              className={cx('aeos-btn', intent === 'danger' ? 'aeos-btn-danger' : 'aeos-btn-primary')}
              onClick={() => { onConfirm?.(); onClose?.(); }}
            >
              {confirmLabel}
            </button>
          </div>
        </div>
      </div>
    </div>,
    document.body
  );
}
