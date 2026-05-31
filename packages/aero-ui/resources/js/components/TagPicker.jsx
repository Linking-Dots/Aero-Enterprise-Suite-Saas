import { useState, useEffect, useRef, useCallback } from 'react';
import { XMarkIcon, CheckIcon, PlusIcon } from '@heroicons/react/24/outline';
import { cx, Text } from './Primitives.jsx';

/**
 * TagPicker — Multi-select tag picker with search and keyboard navigation.
 *
 * @param {object} props
 * @param {string[]}  [props.value=[]]      — Selected tag names
 * @param {(string[]) => void} props.onChange — Callback when selection changes
 * @param {boolean}   [props.multiple=true] — Allow multiple selections
 * @param {boolean}   [props.creatable=false] — Allow creating new tags
 * @param {string}    [props.placeholder="Add tags..."]
 * @param {Array<{name:string,color?:string}>} [props.options] — Preloaded tag options
 */
export default function TagPicker({
  value = [],
  onChange,
  multiple = true,
  creatable = false,
  placeholder = 'Add tags...',
  options: initialOptions = [],
}) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [selected, setSelected] = useState(new Set(value));
  const [options, setOptions] = useState(initialOptions);
  const [activeIndex, setActiveIndex] = useState(0);
  const containerRef = useRef(null);
  const inputRef = useRef(null);

  const normalizedQuery = query.trim().toLowerCase();

  const filtered = normalizedQuery
    ? options.filter((opt) => opt.name.toLowerCase().includes(normalizedQuery))
    : options;

  const showCreate = creatable && normalizedQuery && !options.some((o) => o.name.toLowerCase() === normalizedQuery);

  const toggleTag = useCallback(
    (name) => {
      setSelected((prev) => {
        const next = new Set(prev);
        if (next.has(name)) {
          next.delete(name);
        } else {
          if (!multiple) next.clear();
          next.add(name);
        }
        onChange?.([...next]);
        return next;
      });
    },
    [multiple, onChange]
  );

  const removeTag = useCallback(
    (name) => {
      setSelected((prev) => {
        const next = new Set(prev);
        next.delete(name);
        onChange?.([...next]);
        return next;
      });
    },
    [onChange]
  );

  const handleCreate = useCallback(() => {
    if (!normalizedQuery) return;
    const name = normalizedQuery;
    setOptions((prev) => [...prev, { name, color: '#0ea5e9' }]);
    toggleTag(name);
    setQuery('');
  }, [normalizedQuery, toggleTag]);

  useEffect(() => {
    setActiveIndex(0);
  }, [query]);

  useEffect(() => {
    const onKey = (e) => {
      if (!open) return;
      const total = filtered.length + (showCreate ? 1 : 0);
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActiveIndex((i) => Math.min(i + 1, total - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActiveIndex((i) => Math.max(i - 1, 0));
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (showCreate && activeIndex === filtered.length) {
          handleCreate();
        } else if (filtered[activeIndex]) {
          toggleTag(filtered[activeIndex].name);
          if (!multiple) setOpen(false);
        }
      } else if (e.key === 'Escape') {
        setOpen(false);
      } else if (e.key === 'Backspace' && !query && selected.size > 0) {
        const last = [...selected].pop();
        removeTag(last);
      }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open, filtered, showCreate, activeIndex, query, selected, toggleTag, removeTag, handleCreate, multiple]);

  useEffect(() => {
    const onClick = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, []);

  return (
    <div ref={containerRef} className="aeos-tag-picker" style={{ position: 'relative', width: '100%' }}>
      {/* Selected tags + input */}
      <div
        className={cx('aeos-input-group', open && 'is-focused')}
        style={{
          display: 'flex',
          flexWrap: 'wrap',
          alignItems: 'center',
          gap: '6px',
          padding: '6px 10px',
          cursor: 'text',
          minHeight: '40px',
        }}
        onClick={() => {
          setOpen(true);
          inputRef.current?.focus();
        }}
      >
        {Array.from(selected).map((name) => {
          const opt = options.find((o) => o.name === name);
          return (
            <span
              key={name}
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: '4px',
                padding: '2px 8px',
                borderRadius: 'var(--aeos-radius-sm, 6px)',
                fontSize: '0.8125rem',
                fontWeight: 500,
                background: opt?.color || '#0ea5e9',
                color: '#fff',
              }}
            >
              {name}
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  removeTag(name);
                }}
                style={{
                  background: 'none',
                  border: 'none',
                  color: 'inherit',
                  cursor: 'pointer',
                  padding: 0,
                  fontSize: '0.75rem',
                  lineHeight: 1,
                }}
                aria-label={`Remove ${name}`}
              >
                <XMarkIcon className="aeos-icon-xs" />
              </button>
            </span>
          );
        })}
        <input
          ref={inputRef}
          type="text"
          className="aeos-input"
          style={{
            flex: 1,
            minWidth: '80px',
            border: 'none',
            background: 'transparent',
            padding: '2px 4px',
            fontSize: '0.875rem',
            outline: 'none',
          }}
          placeholder={selected.size === 0 ? placeholder : ''}
          value={query}
          onChange={(e) => {
            setQuery(e.target.value);
            setOpen(true);
          }}
          onFocus={() => setOpen(true)}
        />
      </div>

      {/* Dropdown */}
      {open && (filtered.length > 0 || showCreate) && (
        <div
          className="aeos-dropdown"
          style={{
            position: 'absolute',
            top: 'calc(100% + 4px)',
            left: 0,
            right: 0,
            zIndex: 50,
            background: 'var(--aeos-surface)',
            border: '1px solid var(--aeos-border)',
            borderRadius: 'var(--aeos-radius-md, 8px)',
            boxShadow: '0 8px 24px rgba(0,0,0,0.12)',
            maxHeight: '240px',
            overflowY: 'auto',
          }}
        >
          {filtered.map((opt, i) => {
            const isActive = i === activeIndex;
            const isSelected = selected.has(opt.name);
            return (
              <div
                key={opt.name}
                role="option"
                aria-selected={isSelected}
                className={cx('aeos-menu-item', isActive && 'is-active')}
                style={{
                  padding: '8px 12px',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                }}
                onMouseEnter={() => setActiveIndex(i)}
                onClick={() => {
                  toggleTag(opt.name);
                  if (!multiple) setOpen(false);
                  setQuery('');
                }}
              >
                <span
                  style={{
                    width: '10px',
                    height: '10px',
                    borderRadius: '50%',
                    background: opt.color || '#0ea5e9',
                    flexShrink: 0,
                  }}
                />
                <Text size="sm" className="aeos-flex-1">{opt.name}</Text>
                {isSelected && <CheckIcon className="aeos-icon-xs" />}
              </div>
            );
          })}
          {showCreate && (
            <div
              role="option"
              className={cx('aeos-menu-item', activeIndex === filtered.length && 'is-active')}
              style={{
                padding: '8px 12px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                borderTop: filtered.length > 0 ? '1px solid var(--aeos-border-subtle)' : undefined,
              }}
              onMouseEnter={() => setActiveIndex(filtered.length)}
              onClick={handleCreate}
            >
              <PlusIcon className="aeos-icon-xs" />
              <Text size="sm">Create "{query.trim()}"</Text>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
