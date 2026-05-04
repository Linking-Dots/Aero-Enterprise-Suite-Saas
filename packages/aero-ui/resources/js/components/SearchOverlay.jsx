import { useState, useEffect, useRef, useCallback } from 'react';
import { createPortal } from 'react-dom';
import { router } from '@inertiajs/react';
import { Icon } from '../icons/icons.jsx';
import { cx, Text, Flex1 } from './Primitives.jsx';
import { useHRMAC } from '../hooks/useHRMAC.js';

/**
 * SearchOverlay — Global command-palette search (Cmd+K / Ctrl+K)
 *
 * Opens a modal overlay at the top of the viewport with live search
 * suggestions. Navigate to a result on Enter, or press Escape / backdrop
 * to close. Only renders if the user has the global_search.search_ui.use
 * permission.
 *
 * Usage: Mount once in App.jsx so it is available on every page.
 */
export default function SearchOverlay() {
  const canSearch = useHRMAC('core.global_search.search_ui.use');
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedIndex, setSelectedIndex] = useState(0);
  const inputRef = useRef(null);
  const abortRef = useRef(null);

  const openOverlay = useCallback(() => {
    if (!canSearch) return;
    setOpen(true);
    setQuery('');
    setResults([]);
    setSelectedIndex(0);
    setTimeout(() => inputRef.current?.focus(), 50);
  }, [canSearch]);

  const closeOverlay = useCallback(() => {
    setOpen(false);
    setQuery('');
    setResults([]);
    setSelectedIndex(0);
  }, []);

  // Cmd+K / Ctrl+K keyboard shortcut
  useEffect(() => {
    const onKey = (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        if (open) closeOverlay();
        else openOverlay();
      }
      if (e.key === 'Escape' && open) {
        closeOverlay();
      }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open, openOverlay, closeOverlay]);

  // Fetch suggestions on query change (debounced)
  useEffect(() => {
    if (!open || !query.trim()) {
      setResults([]);
      setSelectedIndex(0);
      return;
    }

    const timer = setTimeout(() => {
      setLoading(true);
      if (abortRef.current) abortRef.current.abort();
      const controller = new AbortController();
      abortRef.current = controller;

      fetch(`${route('core.search.suggestions')}?q=${encodeURIComponent(query)}`, {
        signal: controller.signal,
        headers: { Accept: 'application/json' },
      })
        .then((res) => res.json())
        .then((data) => {
          setResults(data.results || []);
          setSelectedIndex(0);
        })
        .catch(() => {
          setResults([]);
        })
        .finally(() => setLoading(false));
    }, 200);

    return () => clearTimeout(timer);
  }, [query, open]);

  // Arrow key navigation
  useEffect(() => {
    if (!open) return;
    const onKey = (e) => {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setSelectedIndex((i) => Math.min(i + 1, results.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setSelectedIndex((i) => Math.max(i - 1, 0));
      } else if (e.key === 'Enter') {
        e.preventDefault();
        const result = results[selectedIndex];
        if (result?.url) {
          closeOverlay();
          router.visit(result.url);
        } else if (result) {
          closeOverlay();
          router.get(route('core.search.index'), { q: query });
        }
      }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open, results, selectedIndex, query, closeOverlay]);

  if (!canSearch) return null;

  return createPortal(
    <>
      {open && (
        <div className="aeos-modal-root" role="dialog" aria-modal="true" aria-label="Global search">
          <div className="aeos-modal-backdrop aeos-anim-fade-in" onClick={closeOverlay} />
          <div
            className={cx(
              'aeos-glass-strong aeos-anim-pop-in'
            )}
            style={{
              position: 'fixed',
              top: '12vh',
              left: '50%',
              transform: 'translateX(-50%)',
              width: '100%',
              maxWidth: '640px',
              zIndex: 9999,
              borderRadius: 'var(--aeos-radius-lg, 12px)',
              boxShadow: '0 24px 48px rgba(0,0,0,0.25)',
              overflow: 'hidden',
            }}
          >
            {/* Search input */}
            <div className="aeos-input-group" style={{ position: 'relative', padding: '12px 16px', borderBottom: '1px solid var(--aeos-border)' }}>
              <span className="aeos-input-group-icon" aria-hidden="true">
                <Icon name="search" size={18} />
              </span>
              <input
                ref={inputRef}
                type="search"
                className="aeos-input"
                style={{ fontSize: '1.125rem', paddingLeft: '2.5rem' }}
                placeholder="Search users, roles, audit logs…"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                autoComplete="off"
              />
              {loading && (
                <span style={{ position: 'absolute', right: 16, top: '50%', transform: 'translateY(-50%)' }}>
                  <Icon name="refresh" size={16} className="aeos-spin" />
                </span>
              )}
              {!loading && query && (
                <button
                  type="button"
                  className="aeos-icon-btn"
                  style={{ position: 'absolute', right: 12, top: '50%', transform: 'translateY(-50%)' }}
                  onClick={() => setQuery('')}
                  aria-label="Clear search"
                >
                  <Icon name="x" size={14} />
                </button>
              )}
            </div>

            {/* Results */}
            <div style={{ maxHeight: '60vh', overflowY: 'auto' }}>
              {results.length === 0 && !loading && query.trim() && (
                <div style={{ padding: '24px 16px', textAlign: 'center' }}>
                  <Text tone="muted">No results for "{query}"</Text>
                </div>
              )}
              {results.length === 0 && !query.trim() && (
                <div style={{ padding: '16px' }}>
                  <Text size="sm" tone="muted">
                    Type to search across users, roles, and audit logs.
                    Press <kbd className="aeos-kbd">Enter</kbd> to view all results.
                  </Text>
                </div>
              )}

              {results.map((result, i) => (
                <div
                  key={`${result.type}-${result.id}-${i}`}
                  className={cx(
                    'aeos-menu-item',
                    i === selectedIndex && 'is-active'
                  )}
                  style={{
                    padding: '10px 16px',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '12px',
                    borderBottom: i < results.length - 1 ? '1px solid var(--aeos-border-subtle)' : undefined,
                  }}
                  onMouseEnter={() => setSelectedIndex(i)}
                  onClick={() => {
                    if (result.url) {
                      closeOverlay();
                      router.visit(result.url);
                    } else {
                      closeOverlay();
                      router.get(route('core.search.index'), { q: query });
                    }
                  }}
                  role="option"
                  aria-selected={i === selectedIndex}
                >
                  <Icon name={mapResultIcon(result.icon, result.type)} size={18} />
                  <div style={{ minWidth: 0, flex: 1 }}>
                    <div style={{ fontWeight: 500, fontSize: '0.9375rem', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                      {result.title}
                    </div>
                    {result.subtitle && (
                      <Text size="sm" tone="muted" style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        {result.subtitle}
                      </Text>
                    )}
                  </div>
                  <Pill size="sm" tone="secondary">{result.type}</Pill>
                </div>
              ))}
            </div>

            {/* Footer hints */}
            <div
              style={{
                padding: '8px 16px',
                borderTop: '1px solid var(--aeos-border)',
                display: 'flex',
                alignItems: 'center',
                gap: '12px',
                fontSize: '0.75rem',
                color: 'var(--aeos-text-muted)',
              }}
            >
              <span><kbd className="aeos-kbd">↑↓</kbd> to navigate</span>
              <span><kbd className="aeos-kbd">↵</kbd> to select</span>
              <span><kbd className="aeos-kbd">esc</kbd> to close</span>
              <Flex1 />
              <Text size="xs" tone="muted">Global Search</Text>
            </div>
          </div>
        </div>
      )}
    </>,
    document.body
  );
}

/**
 * Map search result icons to engine icon names.
 */
function mapResultIcon(icon, type) {
  if (icon) return icon;
  const fallback = {
    User: 'user',
    'Audit Log': 'document',
    Role: 'lockClosed',
  };
  return fallback[type] || 'document';
}

/**
 * Inline Pill for result type badges (minimal, no external dependency).
 */
function Pill({ size = 'sm', tone = 'secondary', children }) {
  const sizeClass = size === 'sm' ? { fontSize: '0.6875rem', padding: '2px 8px' } : { fontSize: '0.75rem', padding: '4px 10px' };
  const toneStyle =
    tone === 'secondary'
      ? { background: 'var(--aeos-surface-raised)', color: 'var(--aeos-text-secondary)' }
      : { background: 'var(--aeos-surface)', color: 'var(--aeos-text-muted)' };

  return (
    <span
      style={{
        borderRadius: '999px',
        fontWeight: 500,
        lineHeight: 1,
        whiteSpace: 'nowrap',
        ...sizeClass,
        ...toneStyle,
      }}
    >
      {children}
    </span>
  );
}
