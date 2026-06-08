import { forwardRef, useState, useId } from 'react';
import { MagnifyingGlassIcon, DocumentIcon } from '@heroicons/react/24/outline';
import { cx, Box } from './Primitives.jsx';
import { Icon } from '../icons/icons.jsx';

const renderIcon = (ico, sizeValue = 16) => {
  if (!ico) return null;
  if (typeof ico === 'string') {
    return <Icon name={ico} size={sizeValue} />;
  }
  return ico;
};

/** Field — label + hint + error wrapper. */
export const Field = forwardRef(function Field(
  { label, hint, error, required, htmlFor, className, children, ...rest },
  ref
) {
  return (
    <div ref={ref} className={cx('aeos-field', className)} {...rest}>
      {label && (
        <label
          htmlFor={htmlFor}
          className={cx('aeos-label', required && 'aeos-label-required')}
        >
          {label}
        </label>
      )}
      {children}
      {error  && <span className="aeos-field-error" role="alert">{error}</span>}
      {!error && hint && <span className="aeos-field-hint">{hint}</span>}
    </div>
  );
});

/** Input — text input with optional icon slots and error state. */
export const Input = forwardRef(function Input(
  { leftIcon, rightIcon, error, className, type = 'text', ...rest },
  ref
) {
  const inputEl = (
    <input
      ref={ref}
      type={type}
      className={cx('aeos-input', error && 'error', className)}
      {...rest}
    />
  );
  if (!leftIcon && !rightIcon) return inputEl;
  return (
    <div className="aeos-input-group">
      {leftIcon && (
        <span className="aeos-input-group-icon" aria-hidden="true">
          {renderIcon(leftIcon, 16)}
        </span>
      )}
      {inputEl}
      {rightIcon && (
        <span className="aeos-input-group-icon-right" aria-hidden="true">
          {renderIcon(rightIcon, 16)}
        </span>
      )}
    </div>
  );
});

/** Textarea — multiline input. */
export const Textarea = forwardRef(function Textarea({ error, className, ...rest }, ref) {
  return (
    <textarea
      ref={ref}
      className={cx('aeos-input', error && 'error', className)}
      {...rest}
    />
  );
});

/** Select — dropdown with options array or children. */
export const Select = forwardRef(function Select(
  { options = [], error, className, children, ...rest },
  ref
) {
  return (
    <select ref={ref} className={cx('aeos-input', error && 'error', className)} {...rest}>
      {children ?? options.map((o, i) =>
        typeof o === 'string'
          ? <option key={i} value={o}>{o}</option>
          : <option key={i} value={o.value} disabled={o.disabled}>{o.label}</option>
      )}
    </select>
  );
});

/** Checkbox — native checkbox with inline label. */
export const Checkbox = forwardRef(function Checkbox({ label, className, ...rest }, ref) {
  return (
    <label className={cx('aeos-check-label', className)}>
      <input ref={ref} type="checkbox" {...rest} />
      {label && <span>{label}</span>}
    </label>
  );
});

/** Radio — native radio button with inline label. */
export const Radio = forwardRef(function Radio({ label, className, ...rest }, ref) {
  return (
    <label className={cx('aeos-check-label', className)}>
      <input ref={ref} type="radio" {...rest} />
      {label && <span>{label}</span>}
    </label>
  );
});

/** RadioGroup — managed group of Radio inputs. */
export function RadioGroup({ name, value, onChange, options = [], dir = 'column' }) {
  return (
    <Box
      as="div"
      role="radiogroup"
      flex
      dir={dir}
      gap={dir === 'column' ? 2 : 4}
    >
      {options.map(o => (
        <Radio
          key={o.value}
          name={name}
          value={o.value}
          checked={value === o.value}
          onChange={e => onChange?.(e.target.value)}
          label={o.label}
        />
      ))}
    </Box>
  );
}

/** Toggle — accessible on/off switch. */
export function Toggle({ checked, onChange, disabled, label, id: idProp, ...rest }) {
  const autoId = useId();
  const id = idProp ?? autoId;
  const inner = (
    <span className="aeos-toggle">
      <input
        id={id}
        type="checkbox"
        checked={!!checked}
        onChange={onChange}
        disabled={disabled}
        role="switch"
        aria-checked={!!checked}
        {...rest}
      />
      <span className="aeos-toggle-slider" />
    </span>
  );
  if (!label) return inner;
  return (
    <label
      htmlFor={id}
      className="aeos-toggle-row"
      data-disabled={disabled || undefined}
    >
      {inner}
      <span className="aeos-text-sm aeos-text-primary">{label}</span>
    </label>
  );
}

/** SearchInput — input pre-decorated with a search icon and optional kbd shortcut. */
export const SearchInput = forwardRef(function SearchInput(
  { value, onChange, placeholder = 'Search…', shortcut, className, ...rest },
  ref
) {
  return (
    <div className="aeos-input-group">
      <span className="aeos-input-group-icon" aria-hidden="true">
        <MagnifyingGlassIcon className="aeos-icon-sm" />
      </span>
      <input
        ref={ref}
        type="search"
        className={cx('aeos-input', className)}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        {...rest}
      />
      {shortcut && (
        <kbd className="aeos-kbd aeos-search-shortcut">
          {shortcut}
        </kbd>
      )}
    </div>
  );
});

/** FileInput — styled file picker showing selected filename. */
export function FileInput({ accept, multiple, onChange, label = 'Choose file', className }) {
  const [name, setName] = useState('');
  return (
    <label className={cx('aeos-file-input', className)}>
      <input
        type="file"
        accept={accept}
        multiple={multiple}
        className="aeos-file-input-control"
        onChange={e => {
          setName(Array.from(e.target.files ?? []).map(f => f.name).join(', '));
          onChange?.(e);
        }}
      />
      <span className="aeos-btn aeos-btn-soft aeos-btn-sm">
        <DocumentIcon className="aeos-icon-sm" />
        {label}
      </span>
      {name && (
        <span className="aeos-text-sm aeos-text-secondary aeos-file-input-name">
          {name}
        </span>
      )}
    </label>
  );
}

/** DatePicker — native date input styled as an AEOS input. */
export const DatePicker = forwardRef(function DatePicker(
  { value, onChange, placeholder, error, className, ...rest },
  ref
) {
  return (
    <input
      ref={ref}
      type="date"
      className={cx('aeos-input', error && 'error', className)}
      value={value}
      onChange={onChange}
      placeholder={placeholder}
      {...rest}
    />
  );
});

/* ════════════════════════════════════════════════════════════════════
   OTP INPUT — multi-digit code entry
   ════════════════════════════════════════════════════════════════════ */

export function OtpInput({
  value = '',
  onChange,
  digits = 6,
  error = false,
  disabled = false,
  autoFocus = false,
  className,
}) {
  const inputs = Array.from({ length: digits }, (_, i) => i);

  function getDigit(index) {
    return value[index] ?? '';
  }

  function setDigit(index, char) {
    const arr = value.split('');
    arr[index] = char;
    const next = arr.join('').slice(0, digits);
    onChange?.(next);
  }

  function focusNext(index) {
    const next = document.getElementById(`otp-${index + 1}`);
    if (next) next.focus();
  }

  function focusPrev(index) {
    const prev = document.getElementById(`otp-${index - 1}`);
    if (prev) prev.focus();
  }

  function handleChange(index, e) {
    const char = e.target.value.replace(/\D/g, '').slice(-1);
    if (!char) return;
    setDigit(index, char);
    if (index < digits - 1) focusNext(index);
  }

  function handleKeyDown(index, e) {
    if (e.key === 'Backspace') {
      if (getDigit(index)) {
        setDigit(index, '');
      } else if (index > 0) {
        focusPrev(index);
      }
    } else if (e.key === 'ArrowLeft' && index > 0) {
      focusPrev(index);
    } else if (e.key === 'ArrowRight' && index < digits - 1) {
      focusNext(index);
    }
  }

  function handlePaste(e) {
    e.preventDefault();
    const raw = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, digits);
    if (!raw) return;
    onChange?.(raw.padEnd(digits, '').slice(0, digits));
    const targetIndex = Math.min(raw.length, digits - 1);
    const target = document.getElementById(`otp-${targetIndex}`);
    if (target) target.focus();
  }

  return (
    <div className={cx('aeos-otp-grid', className)}>
      {inputs.map(i => (
        <input
          key={i}
          id={`otp-${i}`}
          type="text"
          inputMode="numeric"
          pattern="[0-9]*"
          maxLength={1}
          disabled={disabled}
          autoFocus={autoFocus && i === 0}
          className={cx('aeos-otp-input', error && 'error')}
          value={getDigit(i)}
          onChange={e => handleChange(i, e)}
          onKeyDown={e => handleKeyDown(i, e)}
          onPaste={handlePaste}
          aria-label={`Digit ${i + 1} of ${digits}`}
        />
      ))}
    </div>
  );
}

/* ════════════════════════════════════════════════════════════════════
   PASSWORD STRENGTH METER
   ════════════════════════════════════════════════════════════════════ */

const STRENGTH_LABELS = ['Weak', 'Fair', 'Good', 'Strong'];

function computeStrength(password = '') {
  if (!password) return 0;
  let score = 0;
  if (password.length >= 8) score++;
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
  if (/\d/.test(password)) score++;
  if (/[^A-Za-z0-9]/.test(password)) score++;
  // Cap at 4, but require length
  if (password.length < 6) score = Math.min(score, 1);
  return Math.min(score, 4);
}

export function PasswordStrength({ value = '', className }) {
  const strength = computeStrength(value);
  const label = strength > 0 ? STRENGTH_LABELS[strength - 1] : '';

  return (
    <div className={className}>
      <div className="aeos-strength-bars">
        {[0, 1, 2, 3].map(i => {
          const state = i < strength
            ? strength === 1 ? 'is-weak'
              : strength === 2 ? 'is-fair'
                : strength === 3 ? 'is-good'
                  : 'is-strong'
            : '';
          return (
            <div
              key={i}
              className={cx('aeos-strength-bar', state)}
            />
          );
        })}
      </div>
      {label && (
        <div className="aeos-strength-label">{label} password</div>
      )}
    </div>
  );
}
