/**
 * @aero/ui — Workbench: the shared command-center table kit.
 *
 * One headless hook (useWorkbench) + composable presentational pieces so
 * every platform command-center page (Subscriptions, Invoices, Products,
 * Entitlements, Modules…) inherits search / segmented + facet filters /
 * saved views / bulk selection / sortable columns / pagination / detail
 * drawer / context menus instead of re-implementing them.
 *
 * The hook is pure client-side state over an in-memory row array (command
 * centers ship bounded row sets via Inertia props). Row identity defaults
 * to `row.id`; pass getId for composite keys.
 *
 * Custom saved views persist to localStorage under `aeos.wb.<storageKey>`
 * — platform-admin pages must not depend on tenant-scoped saved-view routes.
 */
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { cx } from './Primitives.jsx';
import './workbench.css';

/* ================================================================
   useWorkbench
   ================================================================ */

/**
 * @param rows        full row array
 * @param searchText  (row) => string haystack for the search box
 * @param views       [{ id, label, test(row) }] built-in views (first = default)
 * @param facets      { id: { value, test(row, value) } } initial facet map
 * @param sortKey     initial sort column key
 * @param sortVal     (row, key) => comparable value
 * @param perPage     initial page size
 * @param getId       (row) => unique id (default row.id)
 * @param storageKey  localStorage suffix for custom saved views
 */
export function useWorkbench({
  rows = [],
  searchText = (r) => Object.values(r).join(' '),
  views = [],
  facets = {},
  sortKey = null,
  sortVal = (r, k) => r[k],
  perPage = 10,
  getId = (r) => r.id,
  storageKey = null,
}) {
  const [q, setQ] = useState('');
  const [view, setView] = useState(views[0]?.id ?? 'all');
  const [facetValues, setFacetValues] = useState(
    () => Object.fromEntries(Object.entries(facets).map(([k, f]) => [k, f.value ?? 'all']))
  );
  const [sort, setSort] = useState({ key: sortKey, dir: 1 });
  const [page, setPage] = useState(1);
  const [per, setPer] = useState(perPage);
  const [selection, setSelection] = useState(() => new Set());
  const [hiddenCols, setHiddenCols] = useState(() => new Set());

  const toggleCol = (key) => setHiddenCols((h) => {
    const n = new Set(h);
    n.has(key) ? n.delete(key) : n.add(key);
    return n;
  });
  const isColHidden = (key) => hiddenCols.has(key);

  /* custom saved views (localStorage) */
  const lsKey = storageKey ? `aeos.wb.${storageKey}` : null;
  const [customViews, setCustomViews] = useState(() => {
    if (!lsKey) return [];
    try { return JSON.parse(localStorage.getItem(lsKey) || '[]'); } catch { return []; }
  });
  const persistCustom = (next) => {
    setCustomViews(next);
    if (lsKey) { try { localStorage.setItem(lsKey, JSON.stringify(next)); } catch { /* quota */ } }
  };
  const saveView = (name) => {
    if (!name?.trim()) return;
    const id = `custom:${Date.now()}`;
    persistCustom([...customViews, { id, label: name.trim(), snapshot: { q, facetValues } }]);
    setView(id);
  };
  const deleteView = (id) => {
    persistCustom(customViews.filter((v) => v.id !== id));
    if (view === id) setView(views[0]?.id ?? 'all');
  };
  const activateView = (id) => {
    setView(id);
    setPage(1);
    const custom = customViews.find((v) => v.id === id);
    if (custom) {
      setQ(custom.snapshot.q ?? '');
      setFacetValues((prev) => ({ ...prev, ...custom.snapshot.facetValues }));
    }
  };

  const setFacet = (id, value) => { setFacetValues((f) => ({ ...f, [id]: value })); setPage(1); };
  const setSearch = (value) => { setQ(value); setPage(1); };
  const resetFilters = () => {
    setQ('');
    setFacetValues(Object.fromEntries(Object.keys(facets).map((k) => [k, 'all'])));
    setView(views[0]?.id ?? 'all');
    setPage(1);
  };
  const toggleSort = (key) => {
    setSort((s) => (s.key === key ? { key, dir: -s.dir } : { key, dir: 1 }));
  };

  const filtered = useMemo(() => {
    const builtIn = views.find((v) => v.id === view);
    let out = builtIn?.test ? rows.filter(builtIn.test) : [...rows];
    for (const [id, f] of Object.entries(facets)) {
      const val = facetValues[id];
      if (val != null && val !== 'all') out = out.filter((r) => f.test(r, val));
    }
    if (q.trim()) {
      const needle = q.trim().toLowerCase();
      out = out.filter((r) => searchText(r).toLowerCase().includes(needle));
    }
    if (sort.key) {
      out.sort((a, b) => {
        const va = sortVal(a, sort.key);
        const vb = sortVal(b, sort.key);
        if (va == null && vb == null) return 0;
        if (va == null) return 1;
        if (vb == null) return -1;
        return (typeof va === 'string' ? va.localeCompare(vb) : va - vb) * sort.dir;
      });
    }
    return out;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [rows, view, facetValues, q, sort, customViews]);

  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / per));
  const safePage = Math.min(page, pages);
  const from = total === 0 ? 0 : (safePage - 1) * per + 1;
  const to = Math.min(safePage * per, total);
  const pageRows = filtered.slice(from - 1, to);

  /* selection */
  const isSelected = (r) => selection.has(getId(r));
  const toggleSelect = (r) => setSelection((s) => {
    const n = new Set(s);
    const id = getId(r);
    n.has(id) ? n.delete(id) : n.add(id);
    return n;
  });
  const pageAllSelected = pageRows.length > 0 && pageRows.every(isSelected);
  const toggleSelectPage = () => setSelection((s) => {
    const n = new Set(s);
    pageRows.forEach((r) => (pageAllSelected ? n.delete(getId(r)) : n.add(getId(r))));
    return n;
  });
  const clearSelection = () => setSelection(new Set());
  const selectedRows = useMemo(() => rows.filter((r) => selection.has(getId(r))), [rows, selection]); // eslint-disable-line react-hooks/exhaustive-deps

  return {
    q, setQ: setSearch,
    view, views, customViews, activateView, saveView, deleteView,
    facetValues, setFacet, resetFilters,
    sort, toggleSort,
    page: safePage, setPage, per, setPer: (n) => { setPer(n); setPage(1); },
    pages, from, to, total, pageRows, filtered,
    selection, selectedRows, isSelected, toggleSelect, toggleSelectPage, pageAllSelected, clearSelection,
    hiddenCols, toggleCol, isColHidden,
    getId,
  };
}

/* ================================================================
   toolbar pieces
   ================================================================ */

export function WbToolbar({ className, children }) {
  return <div className={cx('wb-toolbar', className)}>{children}</div>;
}

/** Search box with a `/` focus hotkey (skipped while typing elsewhere). */
export function WbSearch({ value, onChange, placeholder = 'Search…', hotkey = '/', ariaLabel = 'Search' }) {
  const ref = useRef(null);
  useEffect(() => {
    if (!hotkey) return undefined;
    const onKey = (e) => {
      if (e.key === hotkey && !/INPUT|SELECT|TEXTAREA/.test(document.activeElement?.tagName ?? '')) {
        e.preventDefault();
        ref.current?.focus();
      }
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [hotkey]);
  return (
    <div className="wb-search">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
        <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
      </svg>
      <input ref={ref} type="search" value={value} onChange={(e) => onChange(e.target.value)} placeholder={placeholder} aria-label={ariaLabel} />
      {hotkey && <kbd>{hotkey}</kbd>}
    </div>
  );
}

/** Saved-view chips: built-in views + user views with an inline save form. */
export function WbViews({ wb, label = 'Views' }) {
  const [adding, setAdding] = useState(false);
  const [name, setName] = useState('');
  const submit = (e) => {
    e.preventDefault();
    wb.saveView(name);
    setName('');
    setAdding(false);
  };
  return (
    <div className="wb-views" role="group" aria-label="Saved views">
      <span className="wb-views__label">{label}</span>
      {wb.views.map((v) => (
        <button key={v.id} type="button" className="wb-view" aria-pressed={wb.view === v.id} onClick={() => wb.activateView(v.id)}>
          {v.label}
        </button>
      ))}
      {wb.customViews.map((v) => (
        <button key={v.id} type="button" className="wb-view" aria-pressed={wb.view === v.id} onClick={() => wb.activateView(v.id)}>
          {v.label}
          <span
            className="wb-view__x"
            role="button"
            tabIndex={-1}
            aria-label={`Delete view ${v.label}`}
            onClick={(e) => { e.stopPropagation(); wb.deleteView(v.id); }}
          >
            ✕
          </span>
        </button>
      ))}
      {adding ? (
        <form className="wb-views__form" onSubmit={submit}>
          <input autoFocus value={name} onChange={(e) => setName(e.target.value)} placeholder="View name…" aria-label="New view name"
            onKeyDown={(e) => e.key === 'Escape' && setAdding(false)} />
          <button type="submit" className="wb-view">Save</button>
        </form>
      ) : (
        <button type="button" className="wb-view wb-view--add" onClick={() => setAdding(true)}>＋ Save view</button>
      )}
    </div>
  );
}

/** Column chooser — toggle visibility of the toggleable columns. */
export function WbColumns({ wb, columns = [], label = '⚙ Columns' }) {
  const [open, setOpen] = useState(false);
  const ref = useRef(null);
  useEffect(() => {
    if (!open) return undefined;
    const onDoc = (e) => { if (!ref.current?.contains(e.target)) setOpen(false); };
    document.addEventListener('mousedown', onDoc);
    return () => document.removeEventListener('mousedown', onDoc);
  }, [open]);
  const toggleable = columns.filter((c) => c.label && c.key !== 'actions');
  return (
    <div className="wb-colmenu" ref={ref}>
      <button type="button" className="pc-btn pc-btn--sm" aria-haspopup="menu" aria-expanded={open} onClick={() => setOpen((v) => !v)}>{label}</button>
      {open && (
        <div className="wb-colmenu__pop" role="menu">
          {toggleable.map((c) => (
            <label key={c.key} className="wb-colmenu__row">
              <input type="checkbox" checked={!wb.isColHidden(c.key)} onChange={() => wb.toggleCol(c.key)} />
              <span>{c.label}</span>
            </label>
          ))}
        </div>
      )}
    </div>
  );
}

/** Bulk-action bar; renders only while rows are selected. */
export function WbBulkBar({ wb, children }) {
  if (wb.selection.size === 0) return null;
  return (
    <div className="wb-bulk" role="region" aria-label="Bulk actions">
      <span className="wb-bulk__n">{wb.selection.size} selected</span>
      <span className="wb-bulk__spacer" />
      {children}
      <button type="button" className="pc-btn pc-btn--sm" onClick={wb.clearSelection}>Clear</button>
    </div>
  );
}

/* ================================================================
   table
   ================================================================ */

/**
 * @param columns [{ key, label, sortable, align:'r'|undefined, hideSm, width, render(row) }]
 * @param selectable adds the checkbox column bound to wb selection
 * @param onRowClick(row) row body click (inputs/buttons are excluded)
 * @param empty ReactNode shown when there are no rows
 */
export function WbTable({ wb, columns = [], selectable = false, onRowClick, empty = 'No records match these filters.', rowAriaLabel }) {
  columns = columns.filter((c) => !wb.isColHidden?.(c.key));
  const colSpan = columns.length + (selectable ? 1 : 0);
  const clickable = typeof onRowClick === 'function';
  return (
    <div className="wb-tablewrap">
      <table className="wb-table">
        <thead>
          <tr>
            {selectable && (
              <th style={{ width: 34 }}>
                <input type="checkbox" checked={wb.pageAllSelected} onChange={wb.toggleSelectPage} aria-label="Select all rows on this page" />
              </th>
            )}
            {columns.map((c) => (
              <th
                key={c.key}
                style={c.width ? { width: c.width } : undefined}
                className={cx(c.align === 'r' && 'wb-r', c.hideSm && 'wb-hide-sm', c.sortable && 'wb-sortable', wb.sort.key === c.key && 'wb-sorted')}
                onClick={c.sortable ? () => wb.toggleSort(c.key) : undefined}
                aria-sort={wb.sort.key === c.key ? (wb.sort.dir > 0 ? 'ascending' : 'descending') : undefined}
              >
                {c.label}
                {c.sortable && <span className="wb-arr">{wb.sort.key === c.key && wb.sort.dir < 0 ? '▼' : '▲'}</span>}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {wb.pageRows.length === 0 && (
            <tr><td colSpan={colSpan}><div className="wb-empty">{empty}</div></td></tr>
          )}
          {wb.pageRows.map((r) => (
            <tr
              key={wb.getId(r)}
              className={cx(clickable && 'wb-rowlink')}
              aria-selected={selectable && wb.isSelected(r) ? 'true' : undefined}
              tabIndex={clickable ? 0 : undefined}
              aria-label={rowAriaLabel?.(r)}
              onClick={clickable ? (e) => { if (!e.target.closest('input,button,a,select')) onRowClick(r); } : undefined}
              onKeyDown={clickable ? (e) => { if (e.key === 'Enter' && !e.target.closest('input,button,a,select')) onRowClick(r); } : undefined}
            >
              {selectable && (
                <td>
                  <input type="checkbox" checked={wb.isSelected(r)} onChange={() => wb.toggleSelect(r)} aria-label="Select row" />
                </td>
              )}
              {columns.map((c) => (
                <td key={c.key} className={cx(c.align === 'r' && 'wb-r', c.hideSm && 'wb-hide-sm')}>
                  {c.render ? c.render(r) : r[c.key]}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

/** Pagination footer: showing-range, page-size select, page buttons. */
export function WbFooter({ wb, perOptions = [10, 25, 50] }) {
  const pageNums = useMemo(() => {
    if (wb.pages <= 7) return Array.from({ length: wb.pages }, (_, i) => i + 1);
    const around = [1, wb.page - 1, wb.page, wb.page + 1, wb.pages].filter((p) => p >= 1 && p <= wb.pages);
    return [...new Set(around)].sort((a, b) => a - b);
  }, [wb.pages, wb.page]);

  return (
    <div className="wb-foot">
      <span>{wb.total === 0 ? '0 results' : `Showing ${wb.from}–${wb.to} of ${wb.total}`}</span>
      <span className="wb-foot__spacer" />
      <label>
        Rows
        <select className="pc-input wb-per" value={wb.per} onChange={(e) => wb.setPer(Number(e.target.value))} aria-label="Rows per page">
          {perOptions.map((n) => <option key={n} value={n}>{n}</option>)}
        </select>
      </label>
      <nav className="wb-pgn" aria-label="Pagination">
        <button type="button" disabled={wb.page === 1} onClick={() => wb.setPage(wb.page - 1)} aria-label="Previous page">‹</button>
        {pageNums.map((p, i) => (
          <span key={p} style={{ display: 'contents' }}>
            {i > 0 && p - pageNums[i - 1] > 1 && <button type="button" disabled>…</button>}
            <button type="button" aria-current={p === wb.page ? 'page' : undefined} onClick={() => wb.setPage(p)}>{p}</button>
          </span>
        ))}
        <button type="button" disabled={wb.page === wb.pages} onClick={() => wb.setPage(wb.page + 1)} aria-label="Next page">›</button>
      </nav>
    </div>
  );
}

/* ================================================================
   detail drawer
   ================================================================ */

/**
 * Right-hand detail drawer with optional tab strip.
 * @param tabs  [{ id, label }] — controlled via activeTab/onTab
 * @param head  ReactNode rendered above the tabs (identity + quick actions)
 */
export function WbDrawer({ open, onClose, head, tabs, activeTab, onTab, children, width, ariaLabel = 'Detail' }) {
  useEffect(() => {
    if (!open) return undefined;
    const onKey = (e) => { if (e.key === 'Escape') onClose?.(); };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [open, onClose]);

  if (!open) return null;
  return createPortal(
    <>
      <div className="wb-drawer-ov" onClick={onClose} />
      <aside className="wb-drawer" role="dialog" aria-modal="true" aria-label={ariaLabel} style={width ? { width } : undefined}>
        {head && <div className="wb-drawer__head">{head}</div>}
        {tabs?.length > 0 && (
          <div className="wb-tabs" role="tablist">
            {tabs.map((t) => (
              <button key={t.id} type="button" role="tab" aria-selected={activeTab === t.id} onClick={() => onTab?.(t.id)}>
                {t.label}
              </button>
            ))}
          </div>
        )}
        <div className="wb-drawer__body">{children}</div>
      </aside>
    </>,
    document.body
  );
}

/* ================================================================
   context menu
   ================================================================ */

/**
 * usage:
 *   const ctx = useCtxMenu();
 *   <button className="wb-kebab" onClick={(e) => ctx.open(e.currentTarget, items)}>⋯</button>
 *   {ctx.element}
 * items: [{ label, danger, onClick }, 'sep', …]
 */
export function useCtxMenu() {
  const [state, setState] = useState(null); // { x, y, items }

  const open = useCallback((anchor, items) => {
    const r = anchor.getBoundingClientRect();
    setState({ x: r.right, y: r.bottom + 4, items });
  }, []);
  const close = useCallback(() => setState(null), []);

  useEffect(() => {
    if (!state) return undefined;
    const onDoc = (e) => { if (!e.target.closest('.wb-ctx')) close(); };
    const onKey = (e) => { if (e.key === 'Escape') close(); };
    document.addEventListener('mousedown', onDoc);
    document.addEventListener('keydown', onKey);
    return () => { document.removeEventListener('mousedown', onDoc); document.removeEventListener('keydown', onKey); };
  }, [state, close]);

  const element = state
    ? createPortal(
      <div
        className="wb-ctx"
        role="menu"
        ref={(el) => {
          if (!el) return;
          const w = el.offsetWidth;
          const h = el.offsetHeight;
          el.style.left = `${Math.max(8, Math.min(state.x - w, window.innerWidth - w - 8))}px`;
          el.style.top = `${Math.min(state.y, window.innerHeight - h - 8)}px`;
        }}
      >
        {state.items.map((it, i) =>
          it === 'sep'
            ? <hr key={`s${i}`} />
            : (
              <button key={it.label} type="button" role="menuitem" className={cx(it.danger && 'wb-ctx--danger')}
                onClick={() => { close(); it.onClick?.(); }}>
                {it.label}
              </button>
            )
        )}
      </div>,
      document.body
    )
    : null;

  return { open, close, element };
}
