# Frontend Engine Overhaul Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make every component in `packages/aero-ui` respond correctly to all theme preferences (mode, variant, density, radius, borders) with zero hardcoded colors, zero overflow, and full dark/light mode coverage.

**Architecture:** Layer-by-layer following the CSS cascade — tokens first, then bridge, then themes, then component CSS, then component JSX, then shells, then templates. Each layer is independently verifiable via grep before moving on.

**Tech Stack:** CSS custom properties, React 18 JSX, Vite, HeroUI v2, Heroicons v2. No test runner — verification is grep + visual toggle.

**Spec:** `docs/superpowers/specs/2026-05-06-frontend-engine-overhaul-design.md`

---

## File Map

| File | Change |
|------|--------|
| `packages/aero-ui/resources/css/tokens/base.css` | Add 5 new tokens, rewrite 4 radius tokens with calc |
| `packages/aero-ui/resources/css/heroui/bridge.css` | Fix background-default alias, audit all mappings |
| `packages/aero-ui/resources/css/themes/light.css` | Add 27 missing token overrides |
| `packages/aero-ui/resources/css/themes/dark-warm.css` | Completeness audit |
| `packages/aero-ui/resources/css/themes/dark-cool.css` | Completeness audit |
| `packages/aero-ui/resources/css/themes/dark-oled.css` | Completeness audit |
| `packages/aero-ui/resources/css/themes/dark-forest.css` | Completeness audit |
| `packages/aero-ui/resources/css/themes/dark-rose.css` | Completeness audit |
| `packages/aero-ui/resources/css/themes/dark-midnight.css` | Completeness audit |
| `packages/aero-ui/resources/css/themes/light-warm.css` | Completeness audit |
| `packages/aero-ui/resources/css/themes/light-cool.css` | Completeness audit |
| `packages/aero-ui/resources/css/themes/light-paper.css` | Completeness audit |
| `packages/aero-ui/resources/css/themes/high-contrast.css` | Completeness audit |
| `packages/aero-ui/resources/css/components/cards.css` | Replace hardcoded dark surfaces |
| `packages/aero-ui/resources/css/components/display.css` | Replace avatar rgba values, skeleton gradient, add .aeos-truncate |
| `packages/aero-ui/resources/css/components/forms.css` | Replace focus/error rgba values |
| `packages/aero-ui/resources/css/components/buttons.css` | Replace hover rgba value |
| `packages/aero-ui/resources/css/components/navigation.css` | Replace magic px values |
| `packages/aero-ui/resources/css/components/overlays.css` | Replace backdrop rgba, font-size px |
| `packages/aero-ui/resources/css/components/data.css` | Replace stat-icon fixed size |
| `packages/aero-ui/resources/css/components/feedback.css` | Audit sweep |
| `packages/aero-ui/resources/css/components/actions.css` | Audit sweep |
| `packages/aero-ui/resources/css/components/badges.css` | Audit sweep |
| `packages/aero-ui/resources/css/components/typography.css` | Audit sweep |
| `packages/aero-ui/resources/js/components/Data.jsx` | Remove inline styles, fix icon sizes |
| `packages/aero-ui/resources/js/components/Display.jsx` | Remove inline styles, add min-width:0 |
| `packages/aero-ui/resources/js/components/Forms.jsx` | Structural fixes |
| `packages/aero-ui/resources/js/components/Actions.jsx` | Structural fixes |
| `packages/aero-ui/resources/js/components/Navigation.jsx` | Structural fixes |
| `packages/aero-ui/resources/js/components/Overlays.jsx` | Structural fixes |
| `packages/aero-ui/resources/js/components/Feedback.jsx` | Structural fixes |
| `packages/aero-ui/resources/js/components/Primitives.jsx` | Structural fixes |
| `packages/aero-ui/resources/js/components/Badges.jsx` | Structural fixes (if exists, else skip) |
| `packages/aero-ui/resources/css/shells/sidebar.css` | Replace shell-bg, add overflow containment |
| `packages/aero-ui/resources/css/shells/topnav.css` | Replace shell-bg, add overflow containment |
| `packages/aero-ui/resources/css/shells/floating.css` | Replace shell-bg, add overflow containment |
| `packages/aero-ui/resources/css/shells/command.css` | Replace shell-bg, add overflow containment |
| `packages/aero-ui/resources/css/shells/app-chrome.css` | border-width token |
| `packages/aero-ui/resources/js/shells/Shells.jsx` | Icon size tokens |
| `packages/aero-ui/resources/css/templates/page-layouts.css` | min-width:0, dashboard grid columns, tablet breakpoint |
| `packages/aero-ui/resources/js/templates/Templates.jsx` | No inline px values |

---

## Task 1: Token Foundations

**Files:**
- Modify: `packages/aero-ui/resources/css/tokens/base.css`

- [ ] **Step 1: Verify the missing tokens don't exist yet**

```bash
grep -n "radius-factor\|border-width\|space-7\|space-11\|space-32\|shell-bg\|overlay-backdrop\|stat-icon" \
  packages/aero-ui/resources/css/tokens/base.css
```
Expected: no output (none exist yet).

- [ ] **Step 2: Add radius-factor, border-width axes and rewrite radius tokens**

In `packages/aero-ui/resources/css/tokens/base.css`, inside `:root { }`, replace the existing radius block:

```css
/* BEFORE */
--aeos-r-sm:   6px;
--aeos-r-md:   8px;
--aeos-r-lg:   12px;
--aeos-r-xl:   16px;
--aeos-r-2xl:  24px;
--aeos-r-full: 9999px;
--aeos-card-radius: var(--aeos-r-xl);
```

```css
/* AFTER */
--aeos-radius-factor: 1;
--aeos-r-sm:   calc(6px  * var(--aeos-radius-factor));
--aeos-r-md:   calc(8px  * var(--aeos-radius-factor));
--aeos-r-lg:   calc(12px * var(--aeos-radius-factor));
--aeos-r-xl:   calc(16px * var(--aeos-radius-factor));
--aeos-r-2xl:  24px;
--aeos-r-full: 9999px;
--aeos-card-radius: var(--aeos-r-xl);
```

After the closing `}` of `:root`, add (not inside it — body rules cannot be nested in :root):

```css
body[data-radius="sharp"]    { --aeos-radius-factor: 0.5; }
body[data-radius="balanced"] { --aeos-radius-factor: 1;   }
body[data-radius="soft"]     { --aeos-radius-factor: 1.5; }
```

- [ ] **Step 3: Add border-width axis**

Inside `:root { }`, after the existing density block, add:

```css
  /* Border weight */
  --aeos-border-width: 1px;
```

After the closing brace of `:root`, add:

```css
body[data-borders="hairline"] { --aeos-border-width: 0.5px; }
body[data-borders="standard"] { --aeos-border-width: 1px;   }
body[data-borders="bold"]     { --aeos-border-width: 2px;   }
```

- [ ] **Step 4: Add missing spacing tokens**

Inside `:root { }`, after `--aeos-space-10: 40px;` add `--aeos-space-11: 44px;`. After `--aeos-space-12: 48px;` add nothing yet (space-7 goes between space-6 and space-8). After `--aeos-space-6: 24px;` add `--aeos-space-7: 28px;`. After `--aeos-space-24: 96px;` add `--aeos-space-32: 128px;`.

Final spacing block order:
```css
  --aeos-space-0:    0px;
  --aeos-space-px:   1px;
  --aeos-space-0-5:  2px;
  --aeos-space-1:    4px;
  --aeos-space-1-5:  6px;
  --aeos-space-2:    8px;
  --aeos-space-3:    12px;
  --aeos-space-4:    16px;
  --aeos-space-5:    20px;
  --aeos-space-6:    24px;
  --aeos-space-7:    28px;
  --aeos-space-8:    32px;
  --aeos-space-10:   40px;
  --aeos-space-11:   44px;
  --aeos-space-12:   48px;
  --aeos-space-16:   64px;
  --aeos-space-20:   80px;
  --aeos-space-24:   96px;
  --aeos-space-32:   128px;
```

- [ ] **Step 5: Add shell-bg and overlay-backdrop tokens**

Inside `:root { }`, after `--aeos-shell-floating-bg` line, add:

```css
  --aeos-shell-bg:          var(--aeos-grad-mesh), var(--aeos-bg-page);
  --aeos-overlay-backdrop:  rgba(0, 0, 0, 0.72);
```

In the `body.aeos--light, body[data-mode="light"]` block at the bottom of the file, add:

```css
  --aeos-shell-bg: var(--aeos-bg-page);
```

- [ ] **Step 6: Verify all new tokens are present**

```bash
grep -n "radius-factor\|border-width\|space-7\|space-11\|space-32\|shell-bg\|overlay-backdrop" \
  packages/aero-ui/resources/css/tokens/base.css
```
Expected: 8+ matches including the `:root` definitions and the `body[data-*]` rules.

- [ ] **Step 7: Commit**

```bash
git add packages/aero-ui/resources/css/tokens/base.css
git commit -m "feat(tokens): add radius-factor, border-width axes, spacing gaps, shell-bg, overlay-backdrop"
```

---

## Task 2: HeroUI Bridge Audit

**Files:**
- Modify: `packages/aero-ui/resources/css/heroui/bridge.css`

- [ ] **Step 1: Find all bridge mappings that reference hardcoded aliases**

```bash
grep -n "aeos-obsidian\|aeos-onyx\|aeos-slate\|aeos-graphite\|aeos-gunmetal\|aeos-ink\b" \
  packages/aero-ui/resources/css/heroui/bridge.css
```
Expected: at least one match for `--aeos-obsidian` on the `--heroui-background-default` line.

- [ ] **Step 2: Fix background-default alias**

In `bridge.css`, replace:
```css
--heroui-background-default: var(--aeos-obsidian);
```
with:
```css
--heroui-background-default: var(--aeos-bg-page);
```

- [ ] **Step 3: Replace all legacy alias references**

For each line found in Step 1, apply these substitutions:
- `var(--aeos-obsidian)`  → `var(--aeos-bg-page)`
- `var(--aeos-onyx)`     → `var(--aeos-bg-app)`
- `var(--aeos-slate)`    → `var(--aeos-bg-card)`
- `var(--aeos-graphite)` → `var(--aeos-bg-elevated)`
- `var(--aeos-gunmetal)` → `var(--aeos-bg-modal)`
- `var(--aeos-ink)\b`    → `var(--aeos-text-primary)` (use word-boundary to avoid matching ink-muted etc.)
- `var(--aeos-ink-muted)` → `var(--aeos-text-secondary)`
- `var(--aeos-ink-faint)` → `var(--aeos-text-tertiary)`

- [ ] **Step 4: Verify no legacy aliases remain in bridge**

```bash
grep -n "aeos-obsidian\|aeos-onyx\b\|aeos-slate\b\|aeos-graphite\b\|aeos-gunmetal\b\|aeos-ink\b\|aeos-ink-muted\|aeos-ink-faint" \
  packages/aero-ui/resources/css/heroui/bridge.css
```
Expected: zero matches.

- [ ] **Step 5: Commit**

```bash
git add packages/aero-ui/resources/css/heroui/bridge.css
git commit -m "fix(bridge): replace all legacy dark-alias HeroUI mappings with semantic tokens"
```

---

## Task 3: light.css — Complete Token Coverage

**Files:**
- Modify: `packages/aero-ui/resources/css/themes/light.css`

- [ ] **Step 1: Check current light.css token count**

```bash
grep -c "^  --aeos-" packages/aero-ui/resources/css/themes/light.css
```
Expected: ~13 (the current incomplete count).

- [ ] **Step 2: Add all 27 missing overrides**

Open `packages/aero-ui/resources/css/themes/light.css`. Append the following inside the `.aeos--light` rule block (before the closing `}`):

```css
  /* Semantic surfaces */
  --aeos-bg-subtle:  rgba(0, 0, 0, 0.03);
  --aeos-bg-tint:    rgba(0, 0, 0, 0.04);
  --aeos-bg-hover:   rgba(0, 0, 0, 0.06);
  --aeos-bg-active:  rgba(0, 0, 0, 0.10);
  --aeos-bg-wash:    rgba(0, 0, 0, 0.14);
  --aeos-bg-input:   rgba(0, 0, 0, 0.04);

  /* Semantic borders */
  --aeos-border-subtle: rgba(15, 23, 42, 0.06);
  --aeos-border-base:   rgba(15, 23, 42, 0.10);
  --aeos-border-strong: rgba(15, 23, 42, 0.18);

  /* Primary tints (on white) */
  --aeos-primary-tint:   rgba(0, 163, 184, 0.08);
  --aeos-primary-bg:     rgba(0, 163, 184, 0.12);
  --aeos-primary-border: rgba(0, 163, 184, 0.22);
  --aeos-primary-hover:  rgba(0, 163, 184, 0.14);

  /* Danger tints */
  --aeos-danger-tint:    rgba(220, 38, 38, 0.07);
  --aeos-danger-bg:      rgba(220, 38, 38, 0.10);
  --aeos-danger-border:  rgba(220, 38, 38, 0.22);
  --aeos-danger-hover:   rgba(220, 38, 38, 0.14);

  /* Success tints */
  --aeos-success-tint:   rgba(21, 128, 61, 0.07);
  --aeos-success-bg:     rgba(21, 128, 61, 0.10);
  --aeos-success-border: rgba(21, 128, 61, 0.22);

  /* Warning tints */
  --aeos-warning-tint:   rgba(180, 83, 9, 0.07);
  --aeos-warning-bg:     rgba(180, 83, 9, 0.10);
  --aeos-warning-border: rgba(180, 83, 9, 0.22);

  /* Shadows (lighter for light mode) */
  --aeos-shadow-card:     0 2px 8px rgba(15, 23, 42, 0.08);
  --aeos-shadow-lift:     0 4px 16px rgba(15, 23, 42, 0.12);
  --aeos-shadow-elevated: 0 2px 8px rgba(15, 23, 42, 0.08);
  --aeos-shadow-sm:  0 1px 3px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.06);
  --aeos-shadow-md:  0 4px 6px -1px rgba(15, 23, 42, 0.07), 0 2px 4px -2px rgba(15, 23, 42, 0.06);

  /* Shell background (no dark mesh in light mode) */
  --aeos-shell-bg: var(--aeos-bg-page);

  /* Overlay backdrop (slightly lighter in light mode) */
  --aeos-overlay-backdrop: rgba(15, 23, 42, 0.60);
```

- [ ] **Step 3: Apply the same block to the `@media (prefers-color-scheme: light)` `.aeos--system` rule**

Copy and paste the same additions into the `.aeos--system` media query block.

- [ ] **Step 4: Verify token count increased**

```bash
grep -c "^  --aeos-" packages/aero-ui/resources/css/themes/light.css
```
Expected: ~40 (13 original + 27 new).

- [ ] **Step 5: Commit**

```bash
git add packages/aero-ui/resources/css/themes/light.css
git commit -m "fix(theme/light): add 27 missing semantic token overrides for full light-mode coverage"
```

---

## Task 4: Dark Variant Themes — Completeness Audit

**Files:**
- Modify: `packages/aero-ui/resources/css/themes/dark-warm.css`
- Modify: `packages/aero-ui/resources/css/themes/dark-cool.css`
- Modify: `packages/aero-ui/resources/css/themes/dark-oled.css`
- Modify: `packages/aero-ui/resources/css/themes/dark-forest.css`
- Modify: `packages/aero-ui/resources/css/themes/dark-rose.css`
- Modify: `packages/aero-ui/resources/css/themes/dark-midnight.css`

Dark variants only need to override tokens that differ from the default dark theme. The default dark values in `base.css` are the fallback. Each dark variant file currently overrides surface and text colors for its color palette.

- [ ] **Step 1: Check what each dark variant currently overrides**

```bash
for f in packages/aero-ui/resources/css/themes/dark-*.css; do
  echo "=== $f ===" && grep -c "^  --aeos-" "$f"
done
```
Note the count for each file.

- [ ] **Step 2: Read each dark variant file and identify any hardcoded rgba/hex**

```bash
grep -n "rgba\|#[0-9a-fA-F]" packages/aero-ui/resources/css/themes/dark-warm.css \
  packages/aero-ui/resources/css/themes/dark-cool.css \
  packages/aero-ui/resources/css/themes/dark-oled.css \
  packages/aero-ui/resources/css/themes/dark-forest.css \
  packages/aero-ui/resources/css/themes/dark-rose.css \
  packages/aero-ui/resources/css/themes/dark-midnight.css
```
For each raw value found: if it is a surface or text token override, convert it to a semantic form. If it is a variant-specific color, it stays (theme files are the ONE place raw colors are allowed as token definitions).

- [ ] **Step 3: Ensure each dark variant overrides --aeos-shell-bg**

Each dark variant that changes `--aeos-bg-page` must also update `--aeos-shell-bg` to keep the mesh gradient on the right background:

```css
/* Add to any dark variant that overrides --aeos-bg-page */
--aeos-shell-bg: var(--aeos-grad-mesh), var(--aeos-bg-page);
```

- [ ] **Step 4: Verify no raw rgba in component-style properties**

```bash
grep -n "rgba\|#[0-9a-fA-F]" packages/aero-ui/resources/css/themes/dark-*.css | \
  grep -v "^\s*/\|bg-page\|bg-app\|bg-card\|glass\|border\|divider\|primary\|glow\|shadow\|grad\|text-"
```
Expected: zero matches (raw values are only valid as token value definitions, not as usage of tokens).

- [ ] **Step 5: Commit**

```bash
git add packages/aero-ui/resources/css/themes/dark-warm.css \
        packages/aero-ui/resources/css/themes/dark-cool.css \
        packages/aero-ui/resources/css/themes/dark-oled.css \
        packages/aero-ui/resources/css/themes/dark-forest.css \
        packages/aero-ui/resources/css/themes/dark-rose.css \
        packages/aero-ui/resources/css/themes/dark-midnight.css
git commit -m "fix(themes): audit dark variants, add shell-bg overrides"
```

---

## Task 5: Light Variant + High-Contrast Themes

**Files:**
- Modify: `packages/aero-ui/resources/css/themes/light-warm.css`
- Modify: `packages/aero-ui/resources/css/themes/light-cool.css`
- Modify: `packages/aero-ui/resources/css/themes/light-paper.css`
- Modify: `packages/aero-ui/resources/css/themes/high-contrast.css`

Light variants extend `.aeos--light` — they only override tokens that differ from the base light theme. They must NOT re-declare tokens already set in `light.css`.

- [ ] **Step 1: Check each file for tokens already in light.css**

```bash
for f in packages/aero-ui/resources/css/themes/light-warm.css \
         packages/aero-ui/resources/css/themes/light-cool.css \
         packages/aero-ui/resources/css/themes/light-paper.css; do
  echo "=== $f ===" && cat "$f"
done
```

- [ ] **Step 2: Ensure each light variant always sets --aeos-shell-bg**

```css
/* Add to each light variant that overrides --aeos-bg-page */
--aeos-shell-bg: var(--aeos-bg-page);
```

- [ ] **Step 3: Audit high-contrast.css for token completeness**

```bash
cat packages/aero-ui/resources/css/themes/high-contrast.css
```
High-contrast must override all border, text, background, and intent tokens with high-contrast values. Verify:
- Text tokens use fully opaque values (no opacity < 1 on text)
- Border tokens use `1px` minimum (not hairline)
- Focus rings are highly visible

For any missing overrides in high-contrast, add them explicitly.

- [ ] **Step 4: Verify light variants have shell-bg**

```bash
grep -n "shell-bg" packages/aero-ui/resources/css/themes/light-*.css \
  packages/aero-ui/resources/css/themes/high-contrast.css
```
Expected: at least one match per file (if the file overrides bg-page).

- [ ] **Step 5: Commit**

```bash
git add packages/aero-ui/resources/css/themes/light-warm.css \
        packages/aero-ui/resources/css/themes/light-cool.css \
        packages/aero-ui/resources/css/themes/light-paper.css \
        packages/aero-ui/resources/css/themes/high-contrast.css
git commit -m "fix(themes): audit light variants and high-contrast for token completeness"
```

---

## Task 6: cards.css + display.css — Hardcoded Value Sweep

**Files:**
- Modify: `packages/aero-ui/resources/css/components/cards.css`
- Modify: `packages/aero-ui/resources/css/components/display.css`

- [ ] **Step 1: Find all raw values in both files**

```bash
grep -n "rgba\|#[0-9a-fA-F]" \
  packages/aero-ui/resources/css/components/cards.css \
  packages/aero-ui/resources/css/components/display.css
```
Note every match and the line number.

- [ ] **Step 2: Fix cards.css — .aeos-card background**

```css
/* BEFORE */
.aeos-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
```
```css
/* AFTER */
.aeos-card {
  background: var(--aeos-bg-subtle);
  border: var(--aeos-border-width) solid var(--aeos-border-subtle);
```

- [ ] **Step 3: Fix cards.css — .aeos-card-elevated border**

```css
/* BEFORE */
.aeos-card-elevated {
  background: var(--aeos-bg-elevated);
  border: 1px solid rgba(0, 229, 255, 0.08);
```
```css
/* AFTER */
.aeos-card-elevated {
  background: var(--aeos-bg-elevated);
  border: var(--aeos-border-width) solid var(--aeos-primary-tint);
```

- [ ] **Step 4: Fix cards.css — .aeos-glass borders**

```css
/* BEFORE */
.aeos-glass {
  border: 1px solid var(--aeos-glass-border);
```
```css
/* AFTER */
.aeos-glass {
  border: var(--aeos-border-width) solid var(--aeos-glass-border);
```

```css
/* BEFORE */
.aeos-glass-strong {
  border: 1px solid rgba(0, 229, 255, 0.18);
```
```css
/* AFTER */
.aeos-glass-strong {
  border: var(--aeos-border-width) solid var(--aeos-primary-border);
```

- [ ] **Step 5: Fix cards.css — .aeos-bento background**

```css
/* BEFORE */
.aeos-bento {
  background: rgba(13, 17, 32, 0.80);
  border: 1px solid rgba(0, 229, 255, 0.08);
```
```css
/* AFTER */
.aeos-bento {
  background: var(--aeos-bg-card);
  border: var(--aeos-border-width) solid var(--aeos-primary-tint);
```

- [ ] **Step 6: Fix all remaining `1px solid` borders in cards.css**

```bash
grep -n "1px solid" packages/aero-ui/resources/css/components/cards.css
```
Replace every `1px solid` with `var(--aeos-border-width) solid`.

- [ ] **Step 7: Fix display.css — .aeos-avatar colors**

```css
/* BEFORE */
.aeos-avatar {
  background: rgba(0,229,255,.14); border: 1px solid rgba(0,229,255,.28); color: var(--aeos-primary);
}
.aeos-avatar-amber  { background: rgba(255,179,71,.14); border-color: rgba(255,179,71,.28); color: var(--aeos-secondary); }
.aeos-avatar-indigo { background: rgba(99,102,241,.14); border-color: rgba(99,102,241,.28); color: var(--aeos-tertiary); }
.aeos-avatar-rose   { background: rgba(255,107,107,.14); border-color: rgba(255,107,107,.28); color: var(--aeos-destructive); }
.aeos-avatar-success{ background: rgba(34,197,94,.14); border-color: rgba(34,197,94,.28); color: var(--aeos-success); }
.aeos-avatar-extra  { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.10); color: var(--aeos-text-secondary); }
```
```css
/* AFTER */
.aeos-avatar {
  background: var(--aeos-primary-bg);
  border: var(--aeos-border-width) solid var(--aeos-primary-border);
  color: var(--aeos-primary);
}
.aeos-avatar-amber  { background: var(--aeos-warning-bg);  border-color: var(--aeos-warning-border); color: var(--aeos-secondary); }
.aeos-avatar-indigo { background: rgba(99,102,241,.14); border-color: rgba(99,102,241,.28); color: var(--aeos-tertiary); }
.aeos-avatar-rose   { background: var(--aeos-danger-bg);   border-color: var(--aeos-danger-border);  color: var(--aeos-destructive); }
.aeos-avatar-success{ background: var(--aeos-success-bg);  border-color: var(--aeos-success-border); color: var(--aeos-success); }
.aeos-avatar-extra  { background: var(--aeos-bg-tint);     border-color: var(--aeos-border-base);    color: var(--aeos-text-secondary); }
```
Note: `avatar-indigo` keeps raw values because there are no `--aeos-indigo-*` semantic tint tokens. Add a note in a comment: `/* TODO: add --aeos-tertiary-bg/border tokens in a follow-up */`.

- [ ] **Step 8: Fix display.css — .aeos-skeleton gradient**

```css
/* BEFORE */
.aeos-skeleton {
  background: linear-gradient(90deg,rgba(255,255,255,.04) 25%,rgba(255,255,255,.08) 50%,rgba(255,255,255,.04) 75%);
```
```css
/* AFTER */
.aeos-skeleton {
  background: linear-gradient(90deg, var(--aeos-bg-subtle) 25%, var(--aeos-bg-hover) 50%, var(--aeos-bg-subtle) 75%);
```

- [ ] **Step 9: Fix display.css — remaining raw values**

```css
/* .aeos-empty-icon */
/* BEFORE */
.aeos-empty-icon { background: rgba(255,255,255,.04); border: 1px solid var(--aeos-divider); }
/* AFTER */
.aeos-empty-icon { background: var(--aeos-bg-subtle); border: var(--aeos-border-width) solid var(--aeos-divider); }

/* .aeos-empty-desc */
/* BEFORE */
.aeos-empty-desc { font-size: 0.875rem; max-width: 320px; }
/* AFTER */
.aeos-empty-desc { font-size: var(--aeos-text-sm); max-width: var(--aeos-space-32); }

/* .aeos-card-title */
/* BEFORE */
.aeos-card-title { font-size: 1.05rem; }
/* AFTER */
.aeos-card-title { font-size: var(--aeos-text-lg); }

/* .aeos-empty-title */
/* BEFORE */
.aeos-empty-title { font-size: 1rem; }
/* AFTER */
.aeos-empty-title { font-size: var(--aeos-text-base); }
```

- [ ] **Step 10: Add .aeos-truncate utility class to display.css**

At the end of `display.css`, add:

```css
/* ── Truncate utility ─────────────────────────────────────────── */
.aeos-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  min-width: 0;
}
```

- [ ] **Step 11: Verify zero raw values remain in both files**

```bash
grep -n "rgba\|#[0-9a-fA-F]" \
  packages/aero-ui/resources/css/components/cards.css \
  packages/aero-ui/resources/css/components/display.css
```
Expected: zero matches (ignoring comments).

- [ ] **Step 12: Commit**

```bash
git add packages/aero-ui/resources/css/components/cards.css \
        packages/aero-ui/resources/css/components/display.css
git commit -m "fix(components): replace all hardcoded values in cards and display with tokens"
```

---

## Task 7: forms.css + buttons.css Sweep

**Files:**
- Modify: `packages/aero-ui/resources/css/components/forms.css`
- Modify: `packages/aero-ui/resources/css/components/buttons.css`

- [ ] **Step 1: Find all raw values**

```bash
grep -n "rgba\|#[0-9a-fA-F]\|1px solid" \
  packages/aero-ui/resources/css/components/forms.css \
  packages/aero-ui/resources/css/components/buttons.css
```

- [ ] **Step 2: Fix forms.css — focus state**

```css
/* BEFORE */
.aeos-input:focus {
  border-color: rgba(0, 229, 255, 0.50);
```
```css
/* AFTER */
.aeos-input:focus {
  border-color: var(--aeos-primary-border);
```

- [ ] **Step 3: Fix forms.css — error state**

```css
/* BEFORE */
.aeos-input.error {
  border-color: rgba(255, 107, 107, 0.50);
```
```css
/* AFTER */
.aeos-input.error {
  border-color: var(--aeos-danger-border);
```

- [ ] **Step 4: Fix forms.css — all `1px solid` borders**

```bash
grep -n "1px solid" packages/aero-ui/resources/css/components/forms.css
```
Replace each `1px solid` with `var(--aeos-border-width) solid`.

- [ ] **Step 5: Fix buttons.css — soft hover border**

```css
/* BEFORE */
.aeos-btn-soft:hover {
  border-color: rgba(0, 229, 255, 0.32);
```
```css
/* AFTER */
.aeos-btn-soft:hover {
  border-color: var(--aeos-primary-border);
```

- [ ] **Step 6: Fix buttons.css — all `1px solid` borders**

```bash
grep -n "1px solid" packages/aero-ui/resources/css/components/buttons.css
```
Replace each `1px solid` with `var(--aeos-border-width) solid`. The `calc(var(--aeos-pad-btn-y) - 1px)` padding compensations must also change to `calc(var(--aeos-pad-btn-y) - var(--aeos-border-width))`.

- [ ] **Step 7: Verify zero raw values**

```bash
grep -n "rgba\|#[0-9a-fA-F]" \
  packages/aero-ui/resources/css/components/forms.css \
  packages/aero-ui/resources/css/components/buttons.css
```
Expected: zero matches.

- [ ] **Step 8: Commit**

```bash
git add packages/aero-ui/resources/css/components/forms.css \
        packages/aero-ui/resources/css/components/buttons.css
git commit -m "fix(components): tokenise all hardcoded values in forms and buttons"
```

---

## Task 8: navigation.css Sweep

**Files:**
- Modify: `packages/aero-ui/resources/css/components/navigation.css`

- [ ] **Step 1: Find all raw values and magic px**

```bash
grep -n "rgba\|#[0-9a-fA-F]\|: [0-9]*px\b\|: [0-9]*rem\b\|: [0-9]*\.[0-9]*rem\b" \
  packages/aero-ui/resources/css/components/navigation.css
```

- [ ] **Step 2: Apply all replacements**

```css
/* .aeos-nav-item.is-indented */
/* BEFORE */ padding-left: 28px;
/* AFTER  */ padding-left: var(--aeos-space-7);

/* .aeos-section-title */
/* BEFORE */ font-size: 1.1rem;
/* AFTER  */ font-size: var(--aeos-text-lg);

/* .aeos-section-desc */
/* BEFORE */ font-size: 0.875rem; margin: 4px 0 0;
/* AFTER  */ font-size: var(--aeos-text-sm); margin: var(--aeos-space-1) 0 0;

/* .aeos-page-title */
/* BEFORE */ font-size: clamp(1.4rem, 2.5vw, 1.875rem);
/* AFTER  */ font-size: clamp(var(--aeos-text-xl), 2.5vw, var(--aeos-text-3xl));

/* .aeos-page-desc */
/* BEFORE */ font-size: 0.9rem; margin: 6px 0 0;
/* AFTER  */ font-size: var(--aeos-text-sm); margin: var(--aeos-space-1-5) 0 0;

/* .aeos-page-status */
/* BEFORE */ margin-top: 4px;
/* AFTER  */ margin-top: var(--aeos-space-1);

/* .aeos-breadcrumb-sep */
/* BEFORE */ margin: 0 2px;
/* AFTER  */ margin: 0 var(--aeos-space-0-5);

/* .aeos-nav-group-items */
/* BEFORE */ gap: 2px;
/* AFTER  */ gap: var(--aeos-space-0-5);

/* .aeos-tab margin-bottom: -1px — keep as layout hack, do NOT change */
```

- [ ] **Step 3: Fix all `1px solid` borders**

```bash
grep -n "1px solid\|border-bottom: 1px\|border-top: 1px" \
  packages/aero-ui/resources/css/components/navigation.css
```
Replace each `1px solid` with `var(--aeos-border-width) solid`. Exception: `.aeos-tab { border-bottom: 2px solid transparent; margin-bottom: -1px; }` — the `2px` here is the active indicator weight, keep it or set it to `calc(var(--aeos-border-width) * 2)`.

- [ ] **Step 4: Fix incomplete transitions (add easing)**

```bash
grep -n "transition:" packages/aero-ui/resources/css/components/navigation.css | grep -v "ease"
```
For each found, append `var(--aeos-ease-out)` after the duration token if missing.
Example:
```css
/* BEFORE */ transition: color var(--aeos-dur-fast);
/* AFTER  */ transition: color var(--aeos-dur-fast) var(--aeos-ease-out);
```

- [ ] **Step 5: Verify zero raw values remain**

```bash
grep -n "rgba\|#[0-9a-fA-F]" packages/aero-ui/resources/css/components/navigation.css
```
Expected: zero matches.

- [ ] **Step 6: Commit**

```bash
git add packages/aero-ui/resources/css/components/navigation.css
git commit -m "fix(components): replace magic px and raw values in navigation with tokens"
```

---

## Task 9: overlays.css + data.css Sweep

**Files:**
- Modify: `packages/aero-ui/resources/css/components/overlays.css`
- Modify: `packages/aero-ui/resources/css/components/data.css`

- [ ] **Step 1: Find raw values in both files**

```bash
grep -n "rgba\|#[0-9a-fA-F]\|: [0-9]*px\b\|: [0-9]*\.[0-9]*rem\b\|: [0-9]*rem\b" \
  packages/aero-ui/resources/css/components/overlays.css \
  packages/aero-ui/resources/css/components/data.css
```

- [ ] **Step 2: Fix overlays.css — backdrop**

```css
/* BEFORE */
.aeos-modal-backdrop { background: rgba(0,0,0,.72); }
/* AFTER */
.aeos-modal-backdrop { background: var(--aeos-overlay-backdrop); }
```

- [ ] **Step 3: Fix overlays.css — font sizes and margins**

```css
/* .aeos-modal-title */
/* BEFORE */ font-size: 1.1rem;
/* AFTER  */ font-size: var(--aeos-text-lg);

/* .aeos-modal-desc */
/* BEFORE */ font-size: 0.875rem; margin: 4px 0 0; line-height: 1.4;
/* AFTER  */ font-size: var(--aeos-text-sm); margin: var(--aeos-space-1) 0 0; line-height: var(--aeos-leading-snug);

/* .aeos-drawer-title */
/* BEFORE */ font-size: 1rem;
/* AFTER  */ font-size: var(--aeos-text-base);
```

- [ ] **Step 4: Fix overlays.css — all `1px solid` borders**

```bash
grep -n "1px solid" packages/aero-ui/resources/css/components/overlays.css
```
Replace each with `var(--aeos-border-width) solid`.

- [ ] **Step 5: Fix data.css — stat-icon fixed size**

```css
/* BEFORE */
.aeos-stat-icon {
  width: 44px;
  height: 44px;
```
```css
/* AFTER */
.aeos-stat-icon {
  width: var(--aeos-space-11);
  height: var(--aeos-space-11);
```

- [ ] **Step 6: Fix data.css — all `1px solid` borders**

```bash
grep -n "1px solid" packages/aero-ui/resources/css/components/data.css
```
Replace each with `var(--aeos-border-width) solid`.

- [ ] **Step 7: Fix data.css — sparkline bar border-radius**

```css
/* BEFORE */
.aeos-sparkline-bar { border-radius: 2px; }
/* AFTER  */
.aeos-sparkline-bar { border-radius: var(--aeos-space-0-5); }
```

- [ ] **Step 8: Verify zero raw values**

```bash
grep -n "rgba\|#[0-9a-fA-F]" \
  packages/aero-ui/resources/css/components/overlays.css \
  packages/aero-ui/resources/css/components/data.css
```
Expected: zero matches.

- [ ] **Step 9: Commit**

```bash
git add packages/aero-ui/resources/css/components/overlays.css \
        packages/aero-ui/resources/css/components/data.css
git commit -m "fix(components): tokenise hardcoded values in overlays and data"
```

---

## Task 10: Remaining Component CSS Files

**Files:**
- Modify: `packages/aero-ui/resources/css/components/feedback.css`
- Modify: `packages/aero-ui/resources/css/components/actions.css`
- Modify: `packages/aero-ui/resources/css/components/badges.css`
- Modify: `packages/aero-ui/resources/css/components/typography.css`

- [ ] **Step 1: Find all raw values across remaining files**

```bash
grep -n "rgba\|#[0-9a-fA-F]\|1px solid" \
  packages/aero-ui/resources/css/components/feedback.css \
  packages/aero-ui/resources/css/components/actions.css \
  packages/aero-ui/resources/css/components/badges.css \
  packages/aero-ui/resources/css/components/typography.css
```

- [ ] **Step 2: For each violation, apply these replacement rules**

| Pattern | Replace with |
|---------|-------------|
| `rgba(255, 255, 255, 0.0N)` where N ≤ 3 | `var(--aeos-bg-subtle)` or `var(--aeos-bg-tint)` |
| `rgba(255, 255, 255, 0.0N)` where N = 6 | `var(--aeos-bg-hover)` |
| `rgba(0, 229, 255, 0.NN)` | Intent-matched: `var(--aeos-primary-tint/bg/border)` |
| `rgba(255, 107, 107, 0.NN)` | `var(--aeos-danger-tint/bg/border)` |
| `rgba(34, 197, 94, 0.NN)` | `var(--aeos-success-tint/bg/border)` |
| `rgba(255, 179, 71, 0.NN)` | `var(--aeos-warning-tint/bg/border)` |
| `1px solid` | `var(--aeos-border-width) solid` |
| raw `font-size: 0.875rem` | `var(--aeos-text-sm)` |
| raw `font-size: 1rem` | `var(--aeos-text-base)` |
| raw `margin/padding: Npx` | `var(--aeos-space-N)` per the spacing scale |

- [ ] **Step 3: Fix incomplete transitions**

```bash
grep -n "transition:" \
  packages/aero-ui/resources/css/components/feedback.css \
  packages/aero-ui/resources/css/components/actions.css \
  packages/aero-ui/resources/css/components/badges.css | grep -v "ease"
```
Add `var(--aeos-ease-out)` to any transition missing an easing function.

- [ ] **Step 4: Verify zero raw values across all four files**

```bash
grep -n "rgba\|#[0-9a-fA-F]" \
  packages/aero-ui/resources/css/components/feedback.css \
  packages/aero-ui/resources/css/components/actions.css \
  packages/aero-ui/resources/css/components/badges.css \
  packages/aero-ui/resources/css/components/typography.css
```
Expected: zero matches.

- [ ] **Step 5: Commit**

```bash
git add packages/aero-ui/resources/css/components/feedback.css \
        packages/aero-ui/resources/css/components/actions.css \
        packages/aero-ui/resources/css/components/badges.css \
        packages/aero-ui/resources/css/components/typography.css
git commit -m "fix(components): tokenise all raw values in feedback, actions, badges, typography"
```

---

## Task 11: Component JSX Structural Fixes

**Files:**
- Modify: `packages/aero-ui/resources/js/components/Data.jsx`
- Modify: `packages/aero-ui/resources/js/components/Display.jsx`
- Modify: `packages/aero-ui/resources/js/components/Forms.jsx`
- Modify: `packages/aero-ui/resources/js/components/Actions.jsx`
- Modify: `packages/aero-ui/resources/js/components/Navigation.jsx`
- Modify: `packages/aero-ui/resources/js/components/Overlays.jsx`
- Modify: `packages/aero-ui/resources/js/components/Feedback.jsx`
- Modify: `packages/aero-ui/resources/js/components/Primitives.jsx`

- [ ] **Step 1: Find all inline styles in component files**

```bash
grep -rn "style={{" packages/aero-ui/resources/js/components/
```
Note every file and line with inline styles.

- [ ] **Step 2: Find all Tailwind icon size classes**

```bash
grep -rn "className=\"w-[0-9] h-[0-9]\"\|className=\"w-[0-9]\"" \
  packages/aero-ui/resources/js/components/
```

- [ ] **Step 3: Fix Data.jsx — remove inline Skeleton styles and icon sizes**

In `Data.jsx`, locate the `KPI` component's loading skeleton:
```jsx
/* BEFORE */
<Skeleton h={10} w="60%" style={{ marginBottom: 12 }} />
<Skeleton h={36} w="80%" style={{ marginBottom: 8 }} />
<Skeleton h={12} w="50%" />
```
```jsx
/* AFTER */
<Skeleton h={10} w="60%" className="aeos-skeleton-gap-sm" />
<Skeleton h={36} w="80%" className="aeos-skeleton-gap-xs" />
<Skeleton h={12} w="50%" />
```

Then add to `display.css` (after `.aeos-truncate`):
```css
.aeos-skeleton-gap-sm { margin-bottom: var(--aeos-space-3); }
.aeos-skeleton-gap-xs { margin-bottom: var(--aeos-space-2); }
```

- [ ] **Step 4: Fix Data.jsx — KPI delta icon sizes**

```jsx
/* BEFORE */
<ArrowUpIcon className="w-3 h-3" />
<ArrowDownIcon className="w-3 h-3" />
<TrendingUpIcon className="w-3 h-3" />
```
```jsx
/* AFTER */
<ArrowUpIcon style={{ width: 'var(--aeos-icon-xs)', height: 'var(--aeos-icon-xs)' }} />
<ArrowDownIcon style={{ width: 'var(--aeos-icon-xs)', height: 'var(--aeos-icon-xs)' }} />
<TrendingUpIcon style={{ width: 'var(--aeos-icon-xs)', height: 'var(--aeos-icon-xs)' }} />
```

- [ ] **Step 5: Fix all remaining Tailwind icon classes in all component files**

For each match from Step 2:
- `w-3 h-3` → `style={{ width: 'var(--aeos-icon-xs)', height: 'var(--aeos-icon-xs)' }}`
- `w-4 h-4` → `style={{ width: 'var(--aeos-icon-sm)', height: 'var(--aeos-icon-sm)' }}`
- `w-5 h-5` → `style={{ width: 'var(--aeos-icon-md)', height: 'var(--aeos-icon-md)' }}`
- `w-6 h-6` → `style={{ width: 'var(--aeos-icon-lg)', height: 'var(--aeos-icon-lg)' }}`

- [ ] **Step 6: Fix all remaining inline px/number styles**

For each `style={{ marginBottom: N }}` or `style={{ padding: 'Npx' }}` found:
- Move to a named CSS class in the appropriate component CSS file
- Replace the inline style with `className="aeos-[descriptive-name]"`

- [ ] **Step 7: Add min-width:0 to flex children that contain text**

In each component file, find elements that are flex/grid children AND contain text content (labels, titles, descriptions). Add `min-width: 0` via CSS class rather than inline style. The pattern to look for:

```jsx
/* Example — any flex container child with text that might truncate */
<div className="aeos-card-header-text">  {/* already has min-width:0 in CSS ✓ */}
<div className="aeos-nav-item-label">    {/* already has min-width:0 in CSS ✓ */}
```

Check for any flex children that do NOT have `min-width:0` in their CSS and add it. Focus on: stat card body text, KPI label/value containers, form field label+hint containers.

- [ ] **Step 8: Verify no Tailwind w-/h- icon classes remain**

```bash
grep -rn "className=\"w-[0-9].*h-[0-9]" packages/aero-ui/resources/js/components/
```
Expected: zero matches.

- [ ] **Step 9: Verify no inline px/number margin/padding styles remain**

```bash
grep -rn "style={{.*margin\|style={{.*padding" packages/aero-ui/resources/js/components/
```
Expected: zero matches (or only non-px values like percentage/token strings).

- [ ] **Step 10: Commit**

```bash
git add packages/aero-ui/resources/js/components/
git commit -m "fix(components/jsx): remove inline styles, fix icon sizes to token vars, add min-width:0"
```

---

## Task 12: Shell CSS Fixes

**Files:**
- Modify: `packages/aero-ui/resources/css/shells/sidebar.css`
- Modify: `packages/aero-ui/resources/css/shells/topnav.css`
- Modify: `packages/aero-ui/resources/css/shells/floating.css`
- Modify: `packages/aero-ui/resources/css/shells/command.css`
- Modify: `packages/aero-ui/resources/css/shells/app-chrome.css`

- [ ] **Step 1: Find all raw values and `var(--aeos-grad-mesh)` in shell files**

```bash
grep -n "rgba\|#[0-9a-fA-F]\|grad-mesh\|1px solid" \
  packages/aero-ui/resources/css/shells/sidebar.css \
  packages/aero-ui/resources/css/shells/topnav.css \
  packages/aero-ui/resources/css/shells/floating.css \
  packages/aero-ui/resources/css/shells/command.css \
  packages/aero-ui/resources/css/shells/app-chrome.css
```

- [ ] **Step 2: Replace shell background in all shell files**

For each file that contains `var(--aeos-grad-mesh), var(--aeos-bg-page)`:
```css
/* BEFORE */
background: var(--aeos-grad-mesh), var(--aeos-bg-page);
/* AFTER */
background: var(--aeos-shell-bg);
```

- [ ] **Step 3: Add overflow containment to main content area**

In each shell's CSS file, find the `.aeos-shell-main` selector (or equivalent — `[data-aeos-shell="sidebar"] > div:last-child` or `.aeos-shell-main`). Add:

```css
/* sidebar.css */
[data-aeos-shell="sidebar"] .aeos-shell-main {
  min-height: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

[data-aeos-shell="sidebar"] .aeos-shell-content {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
}
```
Apply the same pattern for `topnav`, `floating`, and `command` shell variants, adapting the selector to match the actual class names used in each file.

- [ ] **Step 4: Fix all `1px solid` borders in shell files**

```bash
grep -n "1px solid" \
  packages/aero-ui/resources/css/shells/sidebar.css \
  packages/aero-ui/resources/css/shells/topnav.css \
  packages/aero-ui/resources/css/shells/floating.css \
  packages/aero-ui/resources/css/shells/command.css \
  packages/aero-ui/resources/css/shells/app-chrome.css
```
Replace each with `var(--aeos-border-width) solid`.

- [ ] **Step 5: Verify zero raw values and no grad-mesh in shell files**

```bash
grep -n "rgba\|#[0-9a-fA-F]\|grad-mesh" \
  packages/aero-ui/resources/css/shells/sidebar.css \
  packages/aero-ui/resources/css/shells/topnav.css \
  packages/aero-ui/resources/css/shells/floating.css \
  packages/aero-ui/resources/css/shells/command.css \
  packages/aero-ui/resources/css/shells/app-chrome.css
```
Expected: zero matches.

- [ ] **Step 6: Commit**

```bash
git add packages/aero-ui/resources/css/shells/
git commit -m "fix(shells): replace shell-bg, add overflow containment, tokenise borders"
```

---

## Task 13: Shell JSX + Template CSS + JSX

**Files:**
- Modify: `packages/aero-ui/resources/js/shells/Shells.jsx`
- Modify: `packages/aero-ui/resources/css/templates/page-layouts.css`
- Modify: `packages/aero-ui/resources/js/templates/Templates.jsx`

- [ ] **Step 1: Find inline styles in Shells.jsx and Templates.jsx**

```bash
grep -n "style={{" \
  packages/aero-ui/resources/js/shells/Shells.jsx \
  packages/aero-ui/resources/js/templates/Templates.jsx
```

- [ ] **Step 2: Fix Shells.jsx — icon sizes**

For every heroicon in `Shells.jsx` using `className="w-N h-N"`:
Replace with `style={{ width: 'var(--aeos-icon-md)', height: 'var(--aeos-icon-md)' }}` (or xs/sm/lg based on context).

- [ ] **Step 3: Fix Templates.jsx — remove inline px styles**

For each inline `style={{ marginBottom: N }}` or `style={{ padding: 'Npx' }}` in Templates.jsx:
Move to a CSS class in `page-layouts.css`. Example:
```jsx
/* BEFORE */
<div style={{ marginBottom: 24 }}>
/* AFTER */
<div className="aeos-template-section-gap">
```
```css
/* In page-layouts.css */
.aeos-template-section-gap { margin-bottom: var(--aeos-space-6); }
```

- [ ] **Step 4: Fix page-layouts.css — add min-width:0 to grid children**

At the end of the existing grid rules, add:

```css
/* ── Grid child containment ───────────────────────────────────── */
.aeos-kpi-col,
.aeos-form-grid > *,
.aeos-dashboard-grid > * {
  min-width: 0;
}
```

- [ ] **Step 5: Fix page-layouts.css — dashboard grid column helpers**

After the existing `.aeos-dashboard-grid` rule, add:

```css
.aeos-dashboard-grid.cols-1 { grid-template-columns: 1fr; }
.aeos-dashboard-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
.aeos-dashboard-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
.aeos-dashboard-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 1024px) {
  .aeos-dashboard-grid.cols-3,
  .aeos-dashboard-grid.cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .aeos-dashboard-grid.cols-2,
  .aeos-dashboard-grid.cols-3,
  .aeos-dashboard-grid.cols-4 {
    grid-template-columns: 1fr;
  }
}
```

- [ ] **Step 6: Verify no inline px styles in shell/template JSX**

```bash
grep -n "style={{" \
  packages/aero-ui/resources/js/shells/Shells.jsx \
  packages/aero-ui/resources/js/templates/Templates.jsx
```
Expected: zero matches (or only token string values like `'var(--aeos-icon-md)'`).

- [ ] **Step 7: Commit**

```bash
git add packages/aero-ui/resources/js/shells/Shells.jsx \
        packages/aero-ui/resources/css/templates/page-layouts.css \
        packages/aero-ui/resources/js/templates/Templates.jsx
git commit -m "fix(shells/templates): overflow containment, grid helpers, remove inline styles"
```

---

## Task 14: End-to-End Verification

- [ ] **Criterion 1: Zero raw values in component CSS**

```bash
grep -rn "rgba\|#[0-9a-fA-F]{3,6}" \
  packages/aero-ui/resources/css/components/ \
  packages/aero-ui/resources/css/shells/ \
  packages/aero-ui/resources/css/templates/
```
Expected: zero matches (excluding comment lines starting with `/*`).

If any remain, fix them now and commit before continuing.

- [ ] **Criterion 2: Zero Tailwind icon classes in JSX**

```bash
grep -rn "className=\"w-[0-9]\|className=\"h-[0-9]" \
  packages/aero-ui/resources/js/components/ \
  packages/aero-ui/resources/js/shells/ \
  packages/aero-ui/resources/js/templates/
```
Expected: zero matches.

- [ ] **Criterion 3: All border-width tokens wired**

```bash
grep -rn "1px solid" \
  packages/aero-ui/resources/css/components/ \
  packages/aero-ui/resources/css/shells/ \
  packages/aero-ui/resources/css/templates/
```
Expected: zero matches. Only `var(--aeos-border-width) solid` permitted.

- [ ] **Criterion 4: All radius tokens use calc**

```bash
grep -n "^  --aeos-r-sm\|^  --aeos-r-md\|^  --aeos-r-lg\|^  --aeos-r-xl" \
  packages/aero-ui/resources/css/tokens/base.css
```
Expected: all four lines contain `calc(` and `var(--aeos-radius-factor)`.

- [ ] **Criterion 5: light.css has 40+ token overrides**

```bash
grep -c "^  --aeos-" packages/aero-ui/resources/css/themes/light.css
```
Expected: 40 or more.

- [ ] **Criterion 6: Shell files use --aeos-shell-bg**

```bash
grep -rn "shell-bg\|grad-mesh" packages/aero-ui/resources/css/shells/
```
Expected: each shell file references `var(--aeos-shell-bg)`, zero raw `grad-mesh` occurrences.

- [ ] **Criterion 7: Visual light-mode check**

Open the AEOS app in a browser. Open DevTools Console and run:
```js
document.body.classList.remove('aeos--dark');
document.body.classList.add('aeos--light');
```
Visually verify: no dark backgrounds showing through, no white-on-white text, all borders visible, shell has no dark mesh gradient.

- [ ] **Criterion 8: Visual density check**

In DevTools Console run:
```js
document.body.setAttribute('data-density', 'compact');
```
Verify: all buttons, inputs, nav items, and cards are visibly smaller.
Then:
```js
document.body.setAttribute('data-density', 'spacious');
```
Verify: all are visibly larger than default.

- [ ] **Criterion 9: Visual radius check**

```js
document.body.setAttribute('data-radius', 'sharp');
```
Verify: card, input, and button corners are visibly sharper.
```js
document.body.setAttribute('data-radius', 'soft');
```
Verify: corners are visibly more rounded.

- [ ] **Criterion 10: Visual border-weight check**

```js
document.body.setAttribute('data-borders', 'bold');
```
Verify: card, input, and button borders are visibly thicker (2px).

- [ ] **Final commit**

```bash
git add -A
git commit -m "chore: frontend engine overhaul complete — all 8 verification criteria pass"
```
