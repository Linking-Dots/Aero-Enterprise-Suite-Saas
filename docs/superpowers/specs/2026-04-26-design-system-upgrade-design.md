# aeos365 Design System Upgrade — Design Spec
**Date:** 2026-04-26  
**Scope:** 1st pass — CSS/HTML format only. aero-ui React adoption is a separate follow-on task.

---

## Goal

Upgrade the aeos365 design system (`aeos365-design-system/`) from a single monolithic CSS file into a modular, fully themeable system with:

1. 12 theme variants (7 dark including OLED, 4 light, 1 high-contrast)
2. 10 card surface classes total — 6 selectable presets in the customizer drawer (flat, glass, glow, gradient-border, outline, noise) plus 4 existing specialty classes (card-elevated, glass-strong, bento, cta-glass)
3. 4 app shell layout patterns
4. A floating bubble + slide-in drawer customizer UI with all controls exposed to every user
5. localStorage persistence + CSS export

---

## Architecture: Modular Split (Approach B)

Single entry point — users link one file:

```html
<link rel="stylesheet" href="aeos365.css" />
<script src="customizer/customizer.js" defer></script>
```

### File Structure

```
aeos365-design-system/project/
│
├── aeos365.css                        ← Entry point: @imports all modules in order
│
├── tokens/
│   ├── colors_and_type.css            ← EXISTING (lightly expanded): all --aeos-* custom props, typography classes
│   ├── motion.css                     ← NEW: keyframes + animation tokens (extracted from monolith)
│   └── spacing.css                    ← NEW: spacing scale, layout rhythm tokens
│
├── themes/
│   ├── dark/
│   │   ├── default.css                ← EXISTING refactored: Default Dark (.aeos)
│   │   ├── warm.css                   ← EXISTING refactored: Dark Warm (.aeos--dark-warm)
│   │   ├── cool.css                   ← EXISTING refactored: Dark Cool (.aeos--dark-cool)
│   │   ├── oled.css                   ← NEW: OLED/pure black (.aeos--oled)
│   │   ├── forest.css                 ← NEW: Deep green surfaces (.aeos--dark-forest)
│   │   ├── rose.css                   ← NEW: Deep plum/rose (.aeos--dark-rose)
│   │   └── midnight.css               ← NEW: Deep navy, blue-only (.aeos--midnight)
│   └── light/
│       ├── default.css                ← EXISTING refactored: Default Light (.aeos--light)
│       ├── warm.css                   ← EXISTING refactored: Light Warm (.aeos--light-warm)
│       ├── cool.css                   ← EXISTING refactored: Light Cool (.aeos--light-cool)
│       ├── paper.css                  ← NEW: Cream/parchment (.aeos--light-paper)
│       └── high-contrast.css          ← NEW: WCAG AAA (.aeos--high-contrast)
│
├── components/
│   ├── cards.css                      ← NEW: all 6 card style classes
│   ├── buttons.css                    ← EXTRACTED + expanded from monolith
│   ├── forms.css                      ← EXTRACTED + expanded (checkbox, radio, toggle, select)
│   ├── badges.css                     ← EXTRACTED + expanded
│   └── data.css                       ← NEW: KPI tiles, stat cards, sparkline containers
│
├── shells/
│   ├── sidebar.css                    ← EXISTING refactored: Classic sidebar (collapsible, icon-only mode)
│   ├── topnav.css                     ← NEW: Horizontal nav shell
│   ├── floating.css                   ← NEW: Inset floating sidebar shell
│   └── command.css                    ← NEW: Three-panel command layout
│
├── customizer/
│   ├── customizer.css                 ← Bubble + drawer styles
│   └── customizer.js                  ← Drawer logic, localStorage, live CSS var editing, CSS export
│
└── preview/
    ├── theme-designer.html            ← UPGRADED: full 12-theme picker + live preview
    ├── cards.html                     ← UPGRADED: all 6 card styles
    ├── shells.html                    ← NEW: all 4 shell layouts
    ├── customizer.html                ← NEW: live customizer demo
    └── ... (existing previews kept)
```

---

## Theme Variants (12 total)

### Dark (7)

| Class | Surfaces | Primary | Secondary | Tertiary |
|---|---|---|---|---|
| `.aeos` (default) | `#03040A → #1A1F33` | `#00E5FF` cyan | `#FFB347` amber | `#6366F1` indigo |
| `.aeos--dark-warm` | `#0A0803 → #33221A` | `#FFB347` amber | `#FF8C42` orange | `#FF6B6B` coral |
| `.aeos--dark-cool` | `#03040A → #1A1F33` | `#00E5FF` cyan | `#6366F1` indigo | `#8B5CF6` purple |
| `.aeos--oled` | `#000000 → #111111` | `#00E5FF` cyan | `#FFB347` amber | `#6366F1` indigo |
| `.aeos--dark-forest` | `#010A04 → #0D2418` | `#22C55E` emerald | `#4ADE80` light green | `#FFB347` amber |
| `.aeos--dark-rose` | `#0A0306 → #280E1B` | `#F43F5E` rose | `#FB7185` pink | `#EC4899` magenta |
| `.aeos--midnight` | `#010410 → #0A1340` | `#3B82F6` blue | `#60A5FA` sky | `#6366F1` indigo |

### Light (5)

| Class | Surfaces | Primary | Accent |
|---|---|---|---|
| `.aeos--light` | `#F8FAFC → #FFFFFF` | `#0891B2` cyan | `#D97706` amber |
| `.aeos--light-warm` | `#FEF7ED → #FFFFFF` | `#D97706` amber | `#EA580C` orange |
| `.aeos--light-cool` | `#F0F9FF → #FFFFFF` | `#0891B2` cyan | `#6366F1` indigo |
| `.aeos--light-paper` | `#FAF6F0 → #FFFFFF` | `#92400E` brown | `#78716C` stone |
| `.aeos--high-contrast` | `#FFFFFF / #000000` | `#0000EE` blue | `#CC0000` red |

### Theme Token Pattern

Every theme overrides only the semantic tokens. Components use semantic tokens — never raw hex. This means every component works in every theme without modification.

```css
/* Pattern: theme file only overrides these vars */
.aeos--dark-forest {
  --aeos-primary:      #22C55E;
  --aeos-primary-deep: #16A34A;
  --aeos-secondary:    #4ADE80;
  --aeos-tertiary:     #FFB347;
  --aeos-bg-page:      #010A04;
  --aeos-bg-app:       #04120A;
  --aeos-bg-card:      #071A0F;
  --aeos-bg-elevated:  #0D2418;
  --aeos-bg-modal:     #122E20;
  --aeos-glass-border: rgba(34, 197, 94, 0.15);
  --aeos-grad-cyan:    linear-gradient(135deg, #22C55E 0%, #4ADE80 100%);
  --aeos-grad-full:    linear-gradient(135deg, #22C55E 0%, #4ADE80 50%, #FFB347 100%);
}
```

---

## Card Styles (6 classes)

All cards share the same padding (`--aeos-pad-card: 1.5rem`) and border-radius (`--aeos-r-xl: 16px`) tokens. Style is purely cosmetic — swap classes, same content.

| Class | Description | Best for |
|---|---|---|
| `.aeos-card` | Flat solid — 3% white wash, hairline border | Data-dense tables, lists |
| `.aeos-card-elevated` | Elevated — graphite bg, cyan-tinted border, card shadow | KPI tiles, feature highlights |
| `.aeos-glass` | Glass blur — 70% translucent, 16px backdrop-blur, cyan border | Over gradient backgrounds |
| `.aeos-glass-strong` | Heavy glass — 85% opacity, 24px blur | Modals, hero panels |
| `.aeos-card-glow` | Glow border — cyan border + inner shadow + glow ring | High-signal metrics, alerts |
| `.aeos-card-gradient-border` | Gradient border via CSS mask — full spectrum | Premium/featured content |
| `.aeos-card-outline` | Outline / ghost — transparent fill, prominent border | Empty states, add slots |
| `.aeos-card-noise` | Noise texture — subtle grain overlay on elevated surface | Tactile, print-like sections |
| `.aeos-bento` | Bento — cursor-tracked highlight, mouse `--mx/--my` driven | Marketing feature grids |
| `.aeos-cta-glass` | CTA glass — full-spectrum gradient wash | Hero CTAs |

> Note: `.aeos-bento` and `.aeos-cta-glass` are existing classes kept as-is. The 6 "new" card styles expand the set.

---

## Shell Layouts (4 patterns)

Each shell is a set of CSS classes applied to the layout wrapper. Shells do not impose HTML structure beyond a wrapping element — they use CSS Grid/Flexbox with named areas.

| Shell class | Layout | Best for |
|---|---|---|
| `.aeos-shell-sidebar` | 56px icon sidebar + topbar + content | Default app — current system |
| `.aeos-shell-topnav` | Full-width top nav + content area | Wide-screen dashboards, module-heavy |
| `.aeos-shell-floating` | Floating inset sidebar card + floating content card over page bg | Airy, modern — settings pages |
| `.aeos-shell-command` | Three columns: narrow nav + main + context panel | Power-user, keyboard-first |

Each shell exports CSS custom properties for its dimensions so components can adapt:
- `--aeos-shell-sidebar-w`: sidebar width (default `56px`, expanded `240px`)
- `--aeos-shell-topnav-h`: topbar height (default `48px`)

---

## Customizer UI

### Bubble

- Fixed position, `bottom: 24px; right: 24px; z-index: 9999`
- 44×44px circle, gradient background (`--aeos-grad-cyan`)
- Animated glow pulse at rest (`aeos-pulse-glow` 3s)
- Click → toggles drawer open/closed
- Accessible: `role="button"`, `aria-label="Open theme studio"`, `aria-expanded`

### Drawer

- Slides in from the right, fixed position, full viewport height
- Width: `320px` (desktop), full-width on mobile (`< 480px`)
- `z-index: 9998` (below bubble)
- Backdrop: semi-transparent overlay on mobile only
- Animation: `transform: translateX(100%)` → `translateX(0)` at 240ms ease-out

**4 Tabs:**

#### Appearance Tab
| Control | Type | Token target |
|---|---|---|
| Mode | 4-way pill (Dark / Light / OLED / System) | Toggles theme class on `<body>`; System mode removes the class and relies on `@media (prefers-color-scheme: dark/light)` defined in `themes/dark/default.css` and `themes/light/default.css` |
| Theme Preset | Grid of swatches | Toggles theme class |
| Accent Color | 11 color swatches + custom `<input type="color">` | `--aeos-primary`, `--aeos-primary-deep` |
| Border Radius | Range slider (0–24px) | `--aeos-r-sm/md/lg/xl/2xl` (proportional) |
| Density | Range slider (comfortable / default / dense) | `--aeos-pad-card`, `--aeos-gap-section` |

#### Layout Tab
| Control | Type | Token target |
|---|---|---|
| Card Style | 6-option grid | Writes `data-card-style="glass"` (etc.) on `<body>`; CSS rule `body[data-card-style="glass"] .aeos-card-auto` applies the chosen style to any element with the opt-in class `.aeos-card-auto` |
| Shell Layout | 4-option grid | Toggles `.aeos-shell-*` class on the layout root element (identified by `data-aeos-shell` attribute) |

#### Typography Tab
| Control | Type | Token target |
|---|---|---|
| Display Font | 5 font chips (Syne, Inter, Outfit, Fraunces, Plus Jakarta Sans) | `--aeos-font-display` + injects `<link>` to Google Fonts |
| Body Font | 4 font chips (DM Sans, Inter, Nunito, IBM Plex Sans) | `--aeos-font-body` |
| Font Scale | Range slider (0.85× – 1.2×) | `--aeos-fs-*` (multiplied uniformly) |

#### Effects Tab
| Control | Type | Default |
|---|---|---|
| Glow Effects | Toggle | On |
| Backdrop Blur | Toggle | On |
| Gradient Text | Toggle | On |
| Animations | Toggle | On |
| Reduce Motion | Toggle | Off (accessibility) |
| Grid Texture | Toggle | On |
| Shadow Intensity | Range slider (0–100) | 65 |

### Persistence

- All settings serialised to `localStorage` key `aeos-theme-prefs` as JSON
- On page load, `customizer.js` reads and re-applies saved prefs before first paint (inline `<script>` snippet injected in `<head>` by user, or via the deferred script with `requestAnimationFrame`)
- **Export CSS:** generates a `<style>` block of overridden `--aeos-*` custom properties + body class, copies to clipboard and offers `.css` file download

### Drawer state

```js
// Shape of aeos-theme-prefs in localStorage
{
  mode: 'dark' | 'light' | 'oled' | 'system',
  theme: 'default' | 'dark-warm' | 'dark-cool' | 'oled' | 'dark-forest' | 'dark-rose' | 'midnight' | 'light' | 'light-warm' | 'light-cool' | 'light-paper' | 'high-contrast',
  accent: '#00E5FF',           // hex string
  borderRadius: 8,             // px, base unit
  density: 0,                  // -1 = dense, 0 = default, 1 = comfortable
  displayFont: 'Syne',
  bodyFont: 'DM Sans',
  fontScale: 1,                // multiplier
  cardStyle: 'flat',           // 'flat' | 'glass' | 'glow' | 'gradient-border' | 'outline' | 'noise'
  shell: 'sidebar',            // 'sidebar' | 'topnav' | 'floating' | 'command'
  fx: {
    glow: true,
    blur: true,
    gradientText: true,
    animations: true,
    reduceMotion: false,
    gridTexture: true,
    shadowIntensity: 65
  }
}
```

---

## New/Updated Preview Pages

| File | Status | Purpose |
|---|---|---|
| `preview/theme-designer.html` | Upgraded | Full 12-theme picker, live component preview |
| `preview/cards.html` | Upgraded | All 6+2 card styles with real content |
| `preview/shells.html` | New | All 4 shell layouts side by side |
| `preview/customizer.html` | New | Full live customizer demo with bubble + drawer |
| `preview/app-shell.html` | Kept | Existing reference |
| `preview/buttons.html` | Kept | Existing |
| `preview/badges.html` | Kept | Existing |
| `preview/typography.html` | Kept | Existing |
| `preview/forms.html` | Kept | Existing |
| `preview/colors.html` | Kept | Existing |
| `preview/bento.html` | Kept | Existing |
| `preview/data-viz.html` | Kept | Existing |
| `preview/landing.html` | Kept | Existing |

---

## New Token Additions

### Spacing (`tokens/spacing.css`)

```css
--aeos-space-1:  4px;
--aeos-space-2:  8px;
--aeos-space-3:  12px;
--aeos-space-4:  16px;
--aeos-space-6:  24px;
--aeos-space-8:  32px;
--aeos-space-12: 48px;
--aeos-space-16: 64px;

--aeos-density-factor: 1;   /* customizer writes 0.85 (dense) or 1.15 (comfortable) */
--aeos-pad-card:    calc(1.5rem * var(--aeos-density-factor));
--aeos-pad-section: clamp(4rem, 8vw, 8rem);
```

### Motion (`tokens/motion.css`)

All existing keyframes extracted from `colors_and_type.css`. New additions:

```css
@keyframes aeos-slide-in-right  { from { transform: translateX(100%); } to { transform: translateX(0); } }
@keyframes aeos-fade-in         { from { opacity: 0; } to { opacity: 1; } }
@keyframes aeos-scale-in        { from { transform: scale(0.96); opacity: 0; } to { transform: scale(1); opacity: 1; } }
```

### `colors_and_type.css` additions

```css
/* OLED surface layer */
--aeos-bg-oled:        #000000;
--aeos-bg-oled-card:   #0A0A0A;
--aeos-bg-oled-modal:  #111111;

/* Density control */
--aeos-density-factor: 1;

/* Shell dimension tokens */
--aeos-shell-sidebar-w:     56px;
--aeos-shell-sidebar-w-exp: 240px;
--aeos-shell-topnav-h:      48px;

/* Font scale */
--aeos-font-scale: 1;
```

---

## Constraints & Rules (carried forward from existing system)

- ❌ No new accent colors introduced by components — themes supply all colors
- ❌ No pure-white or pure-black borders above 10% alpha (kills glow)
- ❌ No button border-radius above 8px
- ❌ No emoji — Heroicons only
- ❌ No animating background-color on hover — animate border, glow, transform
- ✅ All numbers use `JetBrains Mono` with `font-feature-settings: "tnum"`
- ✅ All theme overrides use semantic token vars — never raw hex in components
- ✅ `prefers-reduced-motion` media query respected — Reduce Motion toggle maps to this
- ✅ High Contrast theme passes WCAG AA minimum; aims for AAA

---

## Out of Scope (this pass)

- React/HeroUI component integration (aero-ui package) — separate task
- Server-side theme persistence (per-user DB storage) — separate task
- Figma token sync — separate task
- Dark/light mode system preference auto-detection beyond the `System` mode option
