# AEOS Frontend Engine Overhaul — Design Spec
**Date:** 2026-05-06
**Scope:** `packages/aero-ui/resources/` — CSS system + reusable component JSX. Pages untouched. Class names and component prop APIs stable.

---

## Goal

Make the AEOS frontend engine bitwise consistent: every spacing value on the 4px grid, every color through a token, every theme preference (mode, variant, density, radius, borders) visibly applied to every component, zero overflow anywhere, HeroUI components visually indistinguishable from native AEOS components.

---

## Approach: Layer-by-Layer (CSS cascade order)

Fix the foundation first. A token fixed once propagates to every component that references it. Work in this exact order so each layer builds cleanly on the one above:

1. Token layer → 2. HeroUI bridge → 3. Theme files → 4. Component CSS → 5. Component JSX → 6. Shells → 7. Templates

---

## Layer 1 — `tokens/base.css`: New CSS axes

### Problem
ThemeProvider already writes `data-radius` and `data-borders` attributes to `body`, but no CSS rules respond to them. Radius and border-weight preferences are stored in localStorage but have zero visual effect.

### Fix: Radius axis
Add `--aeos-radius-factor` driven by `data-radius` attribute:

```css
/* in :root (default = balanced) */
--aeos-radius-factor: 1;

body[data-radius="sharp"]    { --aeos-radius-factor: 0.5; }
body[data-radius="balanced"] { --aeos-radius-factor: 1;   }
body[data-radius="soft"]     { --aeos-radius-factor: 1.5; }
```

Rewrite all four radius tokens to use the factor:
```css
--aeos-r-sm:   calc(6px  * var(--aeos-radius-factor));
--aeos-r-md:   calc(8px  * var(--aeos-radius-factor));
--aeos-r-lg:   calc(12px * var(--aeos-radius-factor));
--aeos-r-xl:   calc(16px * var(--aeos-radius-factor));
```
`--aeos-r-2xl` and `--aeos-r-full` stay fixed (pill/full-round shapes should not scale).

### Fix: Border-weight axis
Add `--aeos-border-width` driven by `data-borders` attribute:

```css
/* in :root (default = standard) */
--aeos-border-width: 1px;

body[data-borders="hairline"] { --aeos-border-width: 0.5px; }
body[data-borders="standard"] { --aeos-border-width: 1px;   }
body[data-borders="bold"]     { --aeos-border-width: 2px;   }
```

All component `border: 1px solid …` declarations become `border: var(--aeos-border-width) solid …`.

### Fix: Missing spacing tokens
Add two missing values to the spacing scale:
- `--aeos-space-7: 28px` — fills gap between space-6 and space-8, used by navigation indentation
- `--aeos-space-32: 128px` — needed by `.aeos-app-user-info { max-width }` in app-chrome.css (currently references an undefined token, falling back to 0)

### Fix: Shell background token
Add `--aeos-shell-bg` to replace the hardcoded `var(--aeos-grad-mesh), var(--aeos-bg-page)` on shell roots. Dark default keeps the mesh; light themes override to plain `var(--aeos-bg-page)`.

```css
--aeos-shell-bg: var(--aeos-grad-mesh), var(--aeos-bg-page);
```

---

## Layer 2 — `heroui/bridge.css`: Fix dark-hardcoded alias

### Problem
`--heroui-background-default: var(--aeos-obsidian)` resolves to `#03040A` (dark page color) in all modes because `--aeos-obsidian` is a fixed alias, not a semantic token.

### Fix
```css
/* Before */
--heroui-background-default: var(--aeos-obsidian);

/* After */
--heroui-background-default: var(--aeos-bg-page);
```

### Full bridge audit
Every `--heroui-*` mapping must resolve to a semantic `--aeos-*` token (one that changes with theme). Audit all ~55 mappings. Any that point to a hardcoded alias (`--aeos-obsidian`, `--aeos-onyx`, `--aeos-slate`, etc.) must be redirected to the semantic equivalent (`--aeos-bg-page`, `--aeos-bg-app`, `--aeos-bg-card`).

---

## Layer 3 — `themes/*.css`: Complete token coverage

### Problem
`light.css` currently overrides 13 tokens (bg surfaces + text + glass + divider). It is missing 27 tokens that remain dark in light mode, causing hardcoded dark alpha values to show through.

### Required additions to `light.css`

**Semantic surfaces (6 tokens):**
```css
--aeos-bg-subtle:  rgba(0, 0, 0, 0.03);
--aeos-bg-tint:    rgba(0, 0, 0, 0.04);
--aeos-bg-hover:   rgba(0, 0, 0, 0.06);
--aeos-bg-active:  rgba(0, 0, 0, 0.10);
--aeos-bg-wash:    rgba(0, 0, 0, 0.14);
--aeos-bg-input:   rgba(0, 0, 0, 0.04);
```

**Semantic borders (3 tokens):**
```css
--aeos-border-subtle: rgba(15, 23, 42, 0.06);
--aeos-border-base:   rgba(15, 23, 42, 0.10);
--aeos-border-strong: rgba(15, 23, 42, 0.16);
```

**Intent tints — primary (4 tokens):**
```css
--aeos-primary-tint:   rgba(0, 163, 184, 0.08);
--aeos-primary-bg:     rgba(0, 163, 184, 0.12);
--aeos-primary-border: rgba(0, 163, 184, 0.22);
--aeos-primary-hover:  rgba(0, 163, 184, 0.14);
```

**Intent tints — danger, success, warning (9 tokens):**
```css
--aeos-danger-tint:    rgba(220, 38, 38, 0.07);
--aeos-danger-bg:      rgba(220, 38, 38, 0.10);
--aeos-danger-border:  rgba(220, 38, 38, 0.22);
--aeos-danger-hover:   rgba(220, 38, 38, 0.14);

--aeos-success-tint:   rgba(21, 128, 61, 0.07);
--aeos-success-bg:     rgba(21, 128, 61, 0.10);
--aeos-success-border: rgba(21, 128, 61, 0.22);

--aeos-warning-tint:   rgba(180, 83, 9, 0.07);
--aeos-warning-bg:     rgba(180, 83, 9, 0.10);
--aeos-warning-border: rgba(180, 83, 9, 0.22);
```

**Shadow tokens (5 tokens):**
Light mode shadows are cooler and lighter. Override:
```css
--aeos-shadow-card:     0 2px 8px rgba(15, 23, 42, 0.08);
--aeos-shadow-lift:     0 4px 16px rgba(15, 23, 42, 0.12);
--aeos-shadow-elevated: 0 2px 8px rgba(15, 23, 42, 0.08);
--aeos-shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.06);
--aeos-shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.07), 0 2px 4px -2px rgba(15, 23, 42, 0.06);
```

**Shell background (1 token):**
```css
--aeos-shell-bg: var(--aeos-bg-page);
```

### All 11 theme variants
Apply the same completeness audit to: `dark-warm`, `dark-cool`, `dark-oled`, `dark-forest`, `dark-rose`, `dark-midnight`, `light-warm`, `light-cool`, `light-paper`, `high-contrast`. Each variant file must override every semantic token that differs from its base (dark or light).

---

## Layer 4 — `components/*.css` (11 files): Zero raw values

### Rule set
Every component CSS file must pass all 7 rules:

| # | Rule | Token to use |
|---|------|-------------|
| 1 | No raw `rgba(…)` or `#RRGGBB` colors | `--aeos-*` semantic token |
| 2 | No raw `px` spacing outside 1px layout hacks | `--aeos-space-*` or density calc |
| 3 | No raw `px` or `rem` font-size values | `--aeos-text-*` |
| 4 | All `border:` declarations use `--aeos-border-width` | `var(--aeos-border-width) solid var(--aeos-border-*)` |
| 5 | All `border-radius` uses `--aeos-r-*` tokens | (already wired to radius-factor after Layer 1) |
| 6 | All transitions include an easing function | `var(--aeos-dur-fast) var(--aeos-ease-out)` |
| 7 | All `z-index` values use `--aeos-z-*` tokens | (already correct in most files) |

### Known violations by file

**cards.css**
- `.aeos-card { background: rgba(255,255,255,0.03) }` → `var(--aeos-bg-subtle)`
- `.aeos-card-elevated { border: 1px solid rgba(0,229,255,0.08) }` → `var(--aeos-border-width) solid var(--aeos-primary-tint)`
- `.aeos-bento { background: rgba(13,17,32,0.80) }` → `var(--aeos-bg-card)` (opaque surface, tokenised)
- All `1px solid` borders → `var(--aeos-border-width) solid`

**forms.css**
- `.aeos-input:focus { border-color: rgba(0,229,255,0.50) }` → `var(--aeos-primary-border)`
- `.aeos-input:focus { box-shadow: 0 0 0 3px var(--aeos-primary-bg) }` → already uses token ✓
- `.aeos-input.error { border-color: rgba(255,107,107,0.50) }` → `var(--aeos-danger-border)`

**buttons.css**
- `.aeos-btn-soft:hover { border-color: rgba(0,229,255,0.32) }` → `var(--aeos-primary-border)`

**navigation.css**
- `.aeos-nav-item.is-indented { padding-left: 28px }` → `var(--aeos-space-7)`
- `.aeos-section-title { font-size: 1.1rem }` → `var(--aeos-text-lg)`
- `.aeos-section-desc { margin: 4px 0 0 }` → `var(--aeos-space-1) 0 0`
- `.aeos-page-desc { font-size: 0.9rem; margin: 6px 0 0 }` → `var(--aeos-text-sm); var(--aeos-space-1-5) 0 0`
- `.aeos-page-status { margin-top: 4px }` → `var(--aeos-space-1)`
- `.aeos-breadcrumb-sep { margin: 0 2px }` → `0 var(--aeos-space-0-5)`
- `.aeos-nav-group-items { gap: 2px }` → `var(--aeos-space-0-5)`
- Tab `margin-bottom: -1px` — layout hack, keep as-is

**display.css, data.css, overlays.css, feedback.css, actions.css, badges.css, typography.css**
Full audit pass: grep for raw rgba/hex and replace. Most are already token-driven; expect 2–5 violations per file.

---

## Layer 5 — `components/*.jsx` (12 files): Structural fixes

No prop API changes. No logic changes. Structural CSS fixes only.

### Rule set

| # | Rule |
|---|------|
| 1 | Every flex/grid child that contains text or variable-width content has `min-width: 0` via CSS class |
| 2 | Truncating text spans use the `.aeos-truncate` utility class (overflow:hidden; text-overflow:ellipsis; white-space:nowrap) |
| 3 | No inline `style={{ padding: 'Npx' }}` or `style={{ margin: N }}` — move to CSS class |
| 4 | Heroicon `className="w-3 h-3"` replaced with `style={{ width: 'var(--aeos-icon-sm)', height: 'var(--aeos-icon-sm)' }}` |
| 5 | Skeleton inline `style={{ marginBottom: 12 }}` etc. → dedicated CSS class `.aeos-skeleton-gap` |

### Add utility class to `display.css`
```css
.aeos-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  min-width: 0;
}
```

### Known JSX violations
- `Data.jsx`: Skeleton inline `style={{ marginBottom: 12 }}` and `style={{ marginBottom: 8 }}`
- `Data.jsx`: Heroicon `className="w-3 h-3"` on KPI delta icons
- `components/AppChrome.jsx` (via `app-chrome.css`): `.aeos-app-user-info { max-width: var(--aeos-space-32) }` — `--aeos-space-32` does not exist in token scale. Replace with explicit value or add token.

---

## Layer 6 — `shells/` (CSS + JSX)

### CSS fixes

**All shell variants — replace hardcoded background:**
```css
/* Before (sidebar.css and others) */
background: var(--aeos-grad-mesh), var(--aeos-bg-page);

/* After */
background: var(--aeos-shell-bg);
```

**Main content overflow containment (add to all 4 shell variants):**
```css
.aeos-shell-main {
  min-height: 0;     /* allows grid cell to shrink */
  overflow: hidden;  /* clips content to grid cell */
  display: flex;
  flex-direction: column;
}

.aeos-shell-content {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
}
```

**All `border: 1px solid` → `border: var(--aeos-border-width) solid`**

### JSX fixes
- `Shells.jsx`: no inline px values
- Icon sizes: `style={{ width: 'var(--aeos-icon-md)', height: 'var(--aeos-icon-md)' }}`

---

## Layer 7 — `templates/`

### page-layouts.css fixes
```css
/* Add min-width:0 to all grid children */
.aeos-kpi-col,
.aeos-form-grid > *,
.aeos-dashboard-grid > * {
  min-width: 0;
}

/* Dashboard grid explicit column helpers */
.aeos-dashboard-grid.cols-1 { grid-template-columns: 1fr; }
.aeos-dashboard-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
.aeos-dashboard-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
.aeos-dashboard-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }

/* Tablet breakpoint for dashboard grid */
@media (max-width: 1024px) {
  .aeos-dashboard-grid.cols-3,
  .aeos-dashboard-grid.cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}
```

### Templates.jsx fixes
- Move all inline px margin/padding to CSS classes
- Ensure scroll containers have bounded height

---

## Verification — 8 Done Criteria

The overhaul is complete only when all 8 pass:

1. **Zero raw values** — `grep -E 'rgba?\(|#[0-9a-fA-F]{3,6}' packages/aero-ui/resources/css/components/` returns zero matches (excluding comments).
2. **Light mode full coverage** — Toggle `body` to `aeos--light`: no element shows a dark background; no text is invisible.
3. **All 11 theme variants** — Each theme switches cleanly with no wrong surface or border colour.
4. **Density switching** — compact / comfortable / spacious visibly changes all interactive heights, padding, and gaps.
5. **Radius axis** — sharp / balanced / soft visibly changes component corner radii throughout.
6. **Border-weight axis** — hairline / standard / bold visibly changes border thickness on cards, inputs, and buttons.
7. **HeroUI parity** — HeroUI `Input`, `Select`, `Modal`, `Table` match AEOS native components in both dark and light mode.
8. **No overflow** — All 4 shells at 320px / 768px / 1440px: no horizontal scrollbar on body, no clipping, no bleed.

---

## Files changed

| File | Change type |
|------|------------|
| `resources/css/tokens/base.css` | Add `--aeos-radius-factor`, `--aeos-border-width`, `--aeos-space-7`, `--aeos-space-32`, `--aeos-shell-bg`; rewrite `--aeos-r-sm/md/lg/xl` with calc |
| `resources/css/heroui/bridge.css` | Fix `--heroui-background-default`; audit all ~55 mappings |
| `resources/css/themes/light.css` | Add 27 missing token overrides |
| `resources/css/themes/*.css` (10 files) | Completeness audit per theme |
| `resources/css/components/*.css` (11 files) | Replace all raw values with tokens; `border-width` token; easing completeness |
| `resources/css/shells/*.css` (5 files) | Replace shell-bg; add overflow containment |
| `resources/css/templates/page-layouts.css` | min-width:0 on grid children; dashboard grid columns; tablet breakpoint |
| `resources/js/components/*.jsx` (12 files) | Structural: min-width:0, truncate class, no inline styles, icon token sizes |
| `resources/js/shells/Shells.jsx` | Icon size tokens, no inline px |
| `resources/js/templates/Templates.jsx` | No inline px values |
| `resources/css/components/display.css` | Add `.aeos-truncate` utility class |

**Total:** ~32 files. Zero page files touched. Zero public class names or prop APIs changed.
