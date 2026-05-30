import { useCallback, useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { XMarkIcon, SparklesIcon, CheckCircleIcon, ExclamationTriangleIcon, ExclamationCircleIcon } from '@heroicons/react/24/outline';
import { cx } from './Primitives.jsx';

/* ── Event bus ────────────────────────────────────────────────── */
const listeners = new Set();
let toastId = 0;

/** useToast — returns a push function that displays a toast notification. */
export function useToast() {
  return useCallback((options) => {
    listeners.forEach(fn => fn({ id: ++toastId, ...options }));
  }, []);
}

/* ── Toast component ──────────────────────────────────────────── */
const TOAST_ICON = {
  info: <SparklesIcon className="aeos-icon-sm" />,
  success: <CheckCircleIcon className="aeos-icon-sm" />,
  warning: <ExclamationTriangleIcon className="aeos-icon-sm" />,
  danger: <ExclamationCircleIcon className="aeos-icon-sm" />,
};

export function Toast({ intent = 'info', title, icon, onClose, children, className }) {
  const iconComponent = icon ?? TOAST_ICON[intent] ?? TOAST_ICON.info;
  
  return (
    <div
      className={cx('aeos-toast', `aeos-toast-${intent}`, 'aeos-anim-slide-in-right', className)}
      role="status"
      aria-live="polite"
    >
      <div className="aeos-toast-icon">
        {typeof iconComponent === 'string' ? (
          // Fallback for string icons during migration
          <span className="aeos-toast-icon-string">{iconComponent}</span>
        ) : (
          iconComponent
        )}
      </div>
      <div className="aeos-toast-body">
        {title && <strong className="aeos-toast-title">{title}</strong>}
        {children && <div className="aeos-toast-text">{children}</div>}
      </div>
      {onClose && (
        <button type="button" className="aeos-icon-btn" onClick={onClose} aria-label="Dismiss">
          <XMarkIcon className="aeos-icon-xs" />
        </button>
      )}
    </div>
  );
}

/* ── ToastManager — singleton mounted once to document.body ───── */
function ToastManager() {
  const [toasts, setToasts] = useState([]);

  useEffect(() => {
    const push = toast => {
      setToasts(prev => [...prev, toast]);
      if (toast.duration !== Infinity) {
        setTimeout(() => {
          setToasts(prev => prev.filter(t => t.id !== toast.id));
        }, toast.duration ?? 4000);
      }
    };
    listeners.add(push);
    return () => listeners.delete(push);
  }, []);

  const remove = id => setToasts(prev => prev.filter(t => t.id !== id));

  return (
    <div className="aeos-toast-container">
      {toasts.map(t => (
        <Toast key={t.id} intent={t.intent} title={t.title} onClose={() => remove(t.id)}>
          {t.message ?? t.children}
        </Toast>
      ))}
    </div>
  );
}

/* Mount ToastManager once when this module is first imported. */
if (typeof document !== 'undefined' && !window.__aeosToastMounted) {
  window.__aeosToastMounted = true;
  const el = document.createElement('div');
  document.body.appendChild(el);
  import('react-dom/client').then(({ createRoot }) => {
    createRoot(el).render(<ToastManager />);
  });
}
