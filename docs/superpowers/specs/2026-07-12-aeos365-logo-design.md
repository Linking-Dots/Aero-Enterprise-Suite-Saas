# AEOS365 Brand Logo — "Meridian" (Design Spec)

**Date:** 2026-07-12 · **Status:** Approved by Boss (concept B of 4, round-1 board)
**Scope:** Branding asset set only. No product wiring (AppChrome/public site/prod) — separate step.

## Concept

A core disc in continuous orbit with a satellite node resting in the ring's open gap —
the **365 always-on** story. Aviation-instrument palette (deep navy + signal orange)
carries the "Aero" heritage. Containerless mark; no tile.

## Mark geometry (master, 24×24 grid)

| Element | Spec |
|---|---|
| Core | disc, center (12,12), r = 4.6 |
| Orbit ring | r = 9.4, stroke 2.1, round caps, open arc `M20.65 8.33 A9.4 9.4 0 1 1 15.67 3.35` |
| Node | disc r = 2.35 at (18.65, 5.35) — centered in the ring gap, 45° NE |

Clear space: ≥ 0.25 × mark width on all sides. Minimum size: 14 px.

## Palette (fixed — NOT theme-reactive)

| Token | Hex | Use |
|---|---|---|
| Navy | `#0C2742` | ink on light grounds |
| Ice | `#E8EEF6` | ink on dark grounds |
| Signal | `#FF7A1F` | node accent, light grounds |
| Signal-bright | `#FF8A33` | node accent, dark grounds |

## Wordmark

`aeos` in **Barlow SemiBold** + `365` in **Barlow Light** (OFL license — safe to embed;
Bahnschrift from the concept board was preview-only, Microsoft license blocks
redistribution). Lowercase, tight tracking. Delivered as **outlined SVG paths** so it
renders identically everywhere. `365` may carry the Signal accent in lockups.

## Deliverables — `branding/` at monorepo root

- `svg/mark.svg`, `svg/mark-dark.svg`, `svg/mark-mono.svg` (currentColor)
- `svg/wordmark.svg`, `svg/wordmark-dark.svg` (outlined paths)
- `svg/lockup-horizontal.svg`, `svg/lockup-horizontal-dark.svg`
- `svg/lockup-stacked.svg`, `svg/lockup-stacked-dark.svg`
- `web/favicon.ico` (16/32/48), `web/icon-192.png`, `web/icon-512.png`,
  `web/apple-touch-icon-180.png` (app icons = ice mark on navy tile, opaque)
- `README.md` — usage sheet: clear space, min sizes, palette, don'ts

## Implementation plan

1. Fetch Barlow SemiBold + Light TTFs (google/fonts OFL tree).
2. Scratchpad node script (opentype.js, kerning on) → outline `aeos` / `365` to path data.
3. Compose all SVGs from the master geometry + outlined wordmark.
4. Rasterize with sharp → PNGs; assemble `favicon.ico` with png-to-ico.
5. Write README usage sheet.
6. Verify visually (render every asset, screenshot review) → show Boss live output.
7. Commit `branding/` pathspec-scoped.

## Acceptance

- Mark legible at 16 px; node visibly orange at 16 px.
- All SVGs open standalone (no external fonts/refs); mono variant inherits `currentColor`.
- ICO contains 16/32/48; app icons opaque navy.
- No product code touched.
