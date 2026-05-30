import { useState, useEffect, useRef, useCallback } from 'react';
import { createPortal } from 'react-dom';
import { router } from '@inertiajs/react';
import {
  Modal,
  Input,
  Text,
  Badge,
  useHRMAC,
} from '@aero/ui';
import {
  UserIcon,
  DocumentIcon,
  LockClosedIcon,
  TagIcon,
} from '@heroicons/react/24/outline';

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
    <Modal
      open={open}
      onClose={closeOverlay}
      size="lg"
    >
      <Modal.Content>
        {/* Search input */}
        <Input
          ref={inputRef}
          type="search"
          placeholder="Search users, roles, audit logs…"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          leftIcon="search"
          autoComplete="off"
          autoFocus
        />

        {/* Results */}
        <div style={{ maxHeight: '60vh', overflowY: 'auto', marginTop: 16 }}>
          {results.length === 0 && !loading && query.trim() && (
            <Text tone="muted" style={{ padding: '24px 16px', textAlign: 'center' }}>
              No results for "{query}"
            </Text>
          )}
          {results.length === 0 && !query.trim() && (
            <Text size="sm" tone="muted" style={{ padding: '16px' }}>
              Type to search across users, roles, and audit logs.
              Press <kbd className="aeos-pill-surface">Enter</kbd> to view all results.
            </Text>
          )}

          {results.map((result, i) => (
            <div
              key={`${result.type}-${result.id}-${i}`}
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
              style={{
                padding: '12px 16px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: 12,
                borderBottom: i < results.length - 1 ? '1px solid var(--aeos-border-subtle)' : undefined,
                background: i === selectedIndex ? 'var(--aeos-surface-raised)' : undefined,
              }}
              onMouseEnter={() => setSelectedIndex(i)}
            >
              {mapResultIcon(result.icon, result.type)}
              <div className="aeos-flex-1">
                <Text style={{ fontWeight: 500, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                  {result.title}
                </Text>
                {result.subtitle && (
                  <Text size="sm" tone="muted" className="aeos-truncate">
                    {result.subtitle}
                  </Text>
                )}
              </div>
              <Badge intent="neutral" size="sm">{result.type}</Badge>
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
            gap: 12,
            fontSize: '0.75rem',
            color: 'var(--aeos-text-muted)',
          }}
        >
          <span><kbd className="aeos-pill-surface">↑↓</kbd> to navigate</span>
          <span><kbd className="aeos-pill-surface">↵</kbd> to select</span>
          <span><kbd className="aeos-pill-surface">esc</kbd> to close</span>
          <div className="aeos-flex-1" />
          <Text size="xs" tone="muted">Global Search</Text>
        </div>
      </Modal.Content>
    </Modal>,
    document.body
  );
}

/**
 * Map search result icons to engine icon names.
 */
function mapResultIcon(icon, type) {
  // If icon is already a React component, return it
  if (icon && typeof icon !== 'string') return icon;
  
  const fallback = {
    User: <UserIcon className="aeos-icon-md" />,
    'Audit Log': <DocumentIcon className="aeos-icon-md" />,
    Role: <LockClosedIcon className="aeos-icon-md" />,
    Tag: <TagIcon className="aeos-icon-md" />,
  };
  return fallback[type] || <DocumentIcon className="aeos-icon-md" />;
}
