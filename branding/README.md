# AEOS365 Brand — "Meridian"

A core in continuous orbit; the satellite node rests in the ring's open gap — always on,
365. Spec: `docs/superpowers/specs/2026-07-12-aeos365-logo-design.md`.

## Files

| File | Use |
|---|---|
| `svg/mark.svg` | Primary mark, light grounds |
| `svg/mark-dark.svg` | Mark for dark grounds |
| `svg/mark-mono.svg` | Single-color mark, inherits `currentColor` (stamps, embossing, disabled states) |
| `svg/wordmark.svg` / `-dark` | Wordmark only, outlined paths (no font dependency) |
| `svg/lockup-horizontal.svg` / `-dark` | Mark + wordmark, side by side — default lockup |
| `svg/lockup-stacked.svg` / `-dark` | Centered stack — square placements, splash screens |
| `web/favicon.ico` | 16 / 32 / 48 multi-size |
| `web/icon-192.png`, `web/icon-512.png` | PWA icons (opaque navy tile) |
| `web/apple-touch-icon-180.png` | iOS home screen (opaque navy tile) |

## Palette (fixed — never theme-reactive)

| Token | Hex | Role |
|---|---|---|
| Navy | `#0C2742` | Ink on light grounds |
| Ice | `#E8EEF6` | Ink on dark grounds |
| Signal | `#FF7A1F` | Node + `365`, light grounds |
| Signal-bright | `#FF8A33` | Node + `365`, dark grounds |

## Type

Wordmark: `aeos` Barlow SemiBold + `365` Barlow Light (OFL). Already outlined in the
SVGs — never re-set the wordmark in another face or from installed fonts.

## Rules

- **Clear space:** ≥ 25% of the mark's width on all sides.
- **Minimum sizes:** mark 14 px; horizontal lockup 96 px wide.
- **Grounds:** light variant on light, dark variant on dark; mono only when one color
  is available. Never place the full-color mark on mid-tone or busy imagery.
- **Don'ts:** no recoloring, no rotation (the node lives at 45° NE), no container tile
  around the mark, no gradients, no drop shadows, don't detach the node from the ring.

## Regenerating

Generator (opentype.js + sharp) lives with the spec's implementation notes; wordmark
outlines came from Barlow TTFs (google/fonts OFL tree) at x-height 10.6 on the 24-grid.
