/**
 * AEOS UI — Display Components
 * -----------------------------------------------------------------------
 * Exports: Badge, Status, Pill, Avatar, AvatarStack, Kbd, Tag, Progress,
 *          Skeleton, Alert
 *
 * All static styles come from CSS classes in app.css.
 * Inline styles are used ONLY for dynamic, prop-driven values.
 * -----------------------------------------------------------------------
 */

import { useEffect, useRef } from 'react';
import {
  XMarkIcon,
  InformationCircleIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ExclamationCircleIcon,
  SparklesIcon,
} from '@heroicons/react/24/outline';
import { cx } from './Primitives.jsx';

/* ── Intent → CSS class maps ──────────────────────────────────────────── */

const BADGE_INTENT_CLASS = {
  primary:   'aeos-badge-cyan',
  cyan:      'aeos-badge-cyan',
  amber:     'aeos-badge-amber',
  warning:   'aeos-badge-amber',
  indigo:    'aeos-badge-indigo',
  success:   'aeos-badge-success',
  danger:    'aeos-badge-danger',
  neutral:   'aeos-badge-neutral',
  default:   'aeos-badge-neutral',
  mono:      'aeos-badge-mono',
};

const BADGE_SIZE_CLASS = {
  sm: 'aeos-badge-sm',
  md: '',
  lg: 'aeos-badge-lg',
};

/* ════════════════════════════════════════════════════════════════════
   BADGE
   ════════════════════════════════════════════════════════════════════ */

/**
 * `Badge` — semantic status or category label.
 *
 * @param {object}   props
 * @param {'primary'|'cyan'|'amber'|'warning'|'indigo'|'success'|'danger'|'neutral'|'default'|'mono'} [props.intent='neutral']
 * @param {'sm'|'md'|'lg'} [props.size='md']
 * @param {boolean}  [props.dot]       - Show pulsing status dot
 * @param {boolean}  [props.mono]      - Force monospace font style
 * @param {React.ReactNode} [props.leftIcon]  - Icon component to render before text
 * @param {string}   [props.className]
 */
export function Badge({
  intent = 'neutral',
  size = 'md',
  dot = false,
  mono = false,
  leftIcon,
  className,
  children,
  ...rest
}) {
  const intentClass = BADGE_INTENT_CLASS[intent] ?? 'aeos-badge-neutral';
  const sizeClass   = BADGE_SIZE_CLASS[size] ?? '';

  return (
    <span
      className={cx(
        'aeos-badge',
        intentClass,
        sizeClass,
        mono && 'aeos-badge-mono',
        className
      )}
      {...rest}
    >
      {dot && <span className="aeos-badge-dot" aria-hidden="true" />}
      {leftIcon && (
        <span className="aeos-badge-icon">
          {leftIcon}
        </span>
      )}
      {children}
    </span>
  );
}

/* ════════════════════════════════════════════════════════════════════
   STATUS
   ════════════════════════════════════════════════════════════════════ */

/**
 * `Status` — compact dot + label shorthand.
 * Renders as a small mono badge with a pulsing dot.
 *
 * @param {object}   props
 * @param {'success'|'danger'|'warning'|'neutral'|'primary'} [props.intent='neutral']
 * @param {string}   [props.className]
 */
export function Status({ intent = 'neutral', className, children, ...rest }) {
  return (
    <Badge
      intent={intent}
      size="sm"
      dot
      mono
      className={className}
      {...rest}
    >
      {children}
    </Badge>
  );
}

/* ════════════════════════════════════════════════════════════════════
   PILL
   ════════════════════════════════════════════════════════════════════ */

/**
 * `Pill` — semantic alias for Badge. Same API, same CSS.
 * Use when the semantic meaning is a rounded category chip.
 */
export function Pill(props) {
  return <Badge {...props} />;
}

/* ════════════════════════════════════════════════════════════════════
   AVATAR
   ════════════════════════════════════════════════════════════════════ */

const AVATAR_TONE_CLASS = {
  cyan:    '',
  default: '',
  amber:   'aeos-avatar-amber',
  indigo:  'aeos-avatar-indigo',
  rose:    'aeos-avatar-rose',
  success: 'aeos-avatar-success',
};

/**
 * Extract up to 2 initials from a full name string.
 *
 * @param {string} name
 * @returns {string}
 */
function getInitials(name = '') {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return '?';
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
}

/**
 * `Avatar` — user identity mark showing either an image or initials.
 *
 * @param {object}   props
 * @param {string}   [props.name]    - Full name; initials are extracted automatically
 * @param {string}   [props.src]     - Image URL (takes priority over initials)
 * @param {number}   [props.size=32] - Width/height in px
 * @param {'cyan'|'amber'|'indigo'|'rose'|'success'} [props.tone='cyan']
 * @param {string}   [props.className]
 */
export function Avatar({
  name,
  src,
  size = 32,
  tone = 'cyan',
  className,
  ...rest
}) {
  const toneClass = AVATAR_TONE_CLASS[tone] ?? '';
  const initials  = getInitials(name);

  return (
    <span
      className={cx('aeos-avatar', toneClass, className)}
      style={{ width: size, height: size, fontSize: Math.round((size || 32) * 0.38) }}
      title={name}
      aria-label={name}
      {...rest}
    >
      {src ? (
        <img
          src={src}
          alt={name ?? ''}
        />
      ) : (
        <span className="aeos-avatar-initials" aria-hidden="true">
          {initials}
        </span>
      )}
    </span>
  );
}

/* ════════════════════════════════════════════════════════════════════
   AVATAR STACK
   ════════════════════════════════════════════════════════════════════ */

/**
 * `AvatarStack` — overlapping row of avatars with overflow indicator.
 *
 * @param {object}   props
 * @param {{ name: string, src?: string, tone?: string }[]} [props.users=[]]
 * @param {number}   [props.max=4]
 * @param {number}   [props.size=28]
 * @param {string}   [props.className]
 */
export function AvatarStack({
  users = [],
  max = 4,
  size = 28,
  className,
  ...rest
}) {
  const visible  = users.slice(0, max);
  const overflow = users.length - visible.length;
  const overlap  = Math.round(size * 0.35);

  return (
    <div
      className={cx('aeos-avatar-stack', className)}
      {...rest}
    >
      {visible.map((user, i) => (
        <Avatar
          key={i}
          name={user.name}
          src={user.src}
          tone={user.tone}
          size={size}
          style={{
            marginLeft: i === 0 ? 0 : -overlap,
            zIndex: visible.length - i,
            boxSizing: 'content-box',
          }}
        />
      ))}
      {overflow > 0 && (
        <span
          className="aeos-avatar aeos-avatar-overflow"
          style={{
            width:      size,
            height:     size,
            fontSize:   Math.round(size * 0.36),
            marginLeft: -overlap,
            zIndex:     0,
          }}
          aria-label={`${overflow} more`}
        >
          +{overflow}
        </span>
      )}
    </div>
  );
}

/* ════════════════════════════════════════════════════════════════════
   KBD
   ════════════════════════════════════════════════════════════════════ */

/**
 * `Kbd` — keyboard shortcut key display.
 *
 * @param {object}  props
 * @param {string}  [props.className]
 */
export function Kbd({ className, children, ...rest }) {
  return (
    <kbd className={cx('aeos-kbd', className)} {...rest}>
      {children}
    </kbd>
  );
}

/* ════════════════════════════════════════════════════════════════════
   TAG
   ════════════════════════════════════════════════════════════════════ */

const TAG_INTENT_CLASS = {
  neutral: 'aeos-tag-neutral',
  cyan:    'aeos-tag-cyan',
  amber:   'aeos-tag-amber',
};

/**
 * `Tag` — removable label chip.
 *
 * @param {object}    props
 * @param {'neutral'|'cyan'|'amber'} [props.intent='neutral']
 * @param {Function}  [props.onRemove] - If provided, shows an X remove button
 * @param {string}    [props.className]
 */
export function Tag({
  intent = 'neutral',
  onRemove,
  className,
  children,
  ...rest
}) {
  const intentClass = TAG_INTENT_CLASS[intent] ?? 'aeos-tag-neutral';

  return (
    <span className={cx('aeos-tag', intentClass, className)} {...rest}>
      <span className="aeos-tag-label">{children}</span>
      {onRemove && (
        <button
          type="button"
          className="aeos-tag-remove"
          onClick={onRemove}
          aria-label="Remove"
        >
          <XMarkIcon className="aeos-icon-xs" />
        </button>
      )}
    </span>
  );
}

/* ════════════════════════════════════════════════════════════════════
   PROGRESS
   ════════════════════════════════════════════════════════════════════ */

const PROGRESS_INTENT_CLASS = {
  cyan:    'aeos-progress-fill-cyan',
  amber:   'aeos-progress-fill-amber',
  success: 'aeos-progress-fill-success',
};

/**
 * `Progress` — horizontal progress bar.
 *
 * @param {object}   props
 * @param {number}   [props.value=0]   - Percentage 0–100
 * @param {'cyan'|'amber'|'success'} [props.intent='cyan']
 * @param {boolean}  [props.showLabel] - Show percentage label
 * @param {string}   [props.label]     - Override label text
 * @param {string}   [props.className]
 */
export function Progress({
  value = 0,
  intent = 'cyan',
  showLabel = false,
  label,
  className,
  ...rest
}) {
  const clamped    = Math.min(100, Math.max(0, value));
  const intentClass = PROGRESS_INTENT_CLASS[intent] ?? 'aeos-progress-fill-cyan';
  const displayLabel = label ?? `${Math.round(clamped)}%`;

  return (
    <div className={cx('aeos-progress-wrap', className)} {...rest}>
      {showLabel && (
        <div className="aeos-progress-label-row">
          <span className="aeos-progress-label">{displayLabel}</span>
        </div>
      )}
      <div
        className="aeos-progress-track"
        role="progressbar"
        aria-valuenow={clamped}
        aria-valuemin={0}
        aria-valuemax={100}
      >
        <div
          className={cx('aeos-progress-fill', intentClass)}
          style={{ width: `${clamped}%` }}
        />
      </div>
    </div>
  );
}

/* ════════════════════════════════════════════════════════════════════
   SKELETON
   ════════════════════════════════════════════════════════════════════ */

const SKELETON_RADIUS_CLASS = {
  sm: 'aeos-skeleton-sm',
  md: 'aeos-skeleton-md',
  lg: 'aeos-skeleton-lg',
};

/**
 * `Skeleton` — pulsing loading placeholder.
 *
 * @param {object}        props
 * @param {string|number} [props.w='100%']   - Width (CSS value or px number)
 * @param {number}        [props.h=16]       - Height in px
 * @param {'sm'|'md'|'lg'} [props.radius='md']
 * @param {string}        [props.className]
 */
export function Skeleton({
  w = '100%',
  h = 16,
  radius = 'md',
  className,
  style,
  ...rest
}) {
  const radiusClass = SKELETON_RADIUS_CLASS[radius] ?? 'aeos-skeleton-md';
  const width  = typeof w === 'number' ? `${w}px` : w;
  const height = typeof h === 'number' ? `${h}px` : h;

  return (
    <span
      className={cx('aeos-skeleton', radiusClass, className)}
      style={{ width, height, display: 'block', ...style }}
      aria-hidden="true"
      {...rest}
    />
  );
}

/* ════════════════════════════════════════════════════════════════════
   ALERT
   ════════════════════════════════════════════════════════════════════ */

const ALERT_INTENT_CLASS = {
  info:    'aeos-alert-info',
  success: 'aeos-alert-success',
  warning: 'aeos-alert-warning',
  danger:  'aeos-alert-danger',
};

const ALERT_DEFAULT_ICON = {
  info:    'sparkles',
  success: 'checkCircle',
  warning: 'alertTriangle',
  danger:  'alertCircle',
};

const ALERT_ICON_MAP = {
  sparkles: <SparklesIcon className="aeos-icon-sm" />,
  info: <InformationCircleIcon className="aeos-icon-sm" />,
  checkCircle: <CheckCircleIcon className="aeos-icon-sm" />,
  alertTriangle: <ExclamationTriangleIcon className="aeos-icon-sm" />,
  alertCircle: <ExclamationCircleIcon className="aeos-icon-sm" />,
};

/**
 * `Alert` — contextual message with intent-driven styling.
 *
 * @param {object}   props
 * @param {'info'|'success'|'warning'|'danger'} [props.intent='info']
 * @param {string}   [props.title]    - Bold heading text
 * @param {React.ReactNode} [props.icon]     - Override icon component
 * @param {Function} [props.onClose]  - If provided, shows a close button
 * @param {string}   [props.className]
 */
export function Alert({
  intent = 'info',
  title,
  icon,
  onClose,
  className,
  children,
  ...rest
}) {
  const intentClass = ALERT_INTENT_CLASS[intent] ?? 'aeos-alert-info';
  const iconComponent = icon ?? ALERT_ICON_MAP[ALERT_DEFAULT_ICON[intent]] ?? ALERT_ICON_MAP.info;

  return (
    <div
      className={cx('aeos-alert', intentClass, className)}
      role="alert"
      {...rest}
    >
      <span className="aeos-alert-icon" aria-hidden="true">
        {iconComponent}
      </span>
      <div className="aeos-alert-body">
        {title && <p className="aeos-alert-title">{title}</p>}
        {children && <div className="aeos-alert-content">{children}</div>}
      </div>
      {onClose && (
        <button
          type="button"
          className="aeos-alert-close"
          onClick={onClose}
          aria-label="Dismiss alert"
        >
          <XMarkIcon className="aeos-icon-sm" />
        </button>
      )}
    </div>
  );
}
