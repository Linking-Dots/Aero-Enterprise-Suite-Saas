# Tech Versions — Pinned Stack & Canonical Docs

> **Purpose:** The single source of truth for which framework/library versions AEOS365
> targets, and where their *correct-version* documentation lives. AI agents and humans
> MUST resolve version-sensitive APIs against the docs below — never from memory.
>
> **Maintenance:** When a dependency in `composer.json` / `package.json` is bumped to a
> new MAJOR version, update the matching row here in the same PR. Last verified: 2026-06-13.

## How agents should use this file

1. Need a framework API? Query the **Context7 MCP** server (configured in `.mcp.json`)
   with the library + topic — it returns version-correct docs.
2. No MCP available? `WebFetch` the canonical URL in the table below.
3. Neither possible? State the uncertainty explicitly. Do **not** guess.

## Backend

| Tech | Pinned (this repo) | Latest stable (world) | Adopt latest? | Canonical docs |
|------|--------------------|-----------------------|---------------|----------------|
| PHP | `^8.2` | 8.4 | When CI image upgrades | https://www.php.net/docs.php |
| Laravel | `^11.0\|^12.0` → **12.x** | **13.x** (stable 2026-03) | ❌ NO — stay on 12.x | https://laravel.com/docs/12.x |
| Inertia (Laravel adapter) | v2 (`inertiajs/inertia-laravel`) | v2 | ✅ on v2 | https://inertiajs.com |
| PHPUnit | per `laravel/framework` | — | — | https://docs.phpunit.de |
| Pint | `^1.29` | — | ✅ | https://laravel.com/docs/12.x/pint |

> ⚠️ **Laravel 13 exists but is NOT adopted.** Do not use L13-only APIs (first-party AI
> primitives, JSON:API resources, vector search, etc.). Write against the **12.x** docs.

## Frontend (source of truth: `packages/aero-ui/package.json`)

| Tech | Pinned | Canonical docs | Notes |
|------|--------|----------------|-------|
| React | `^18.0.0` | https://react.dev | NOT React 19. No `use()` / RSC patterns that assume 19. |
| Inertia.js (React) | `@inertiajs/react ^2.0.0` | https://inertiajs.com | **v2 API** — `router.*` + `useForm()`. NEVER v1 `Inertia.visit()` / `Inertia.post()`. |
| HeroUI | `@heroui/react ^2.8.5` | https://www.heroui.com/docs | Formerly **NextUI** — reject NextUI imports/names. |
| Tailwind CSS | **v4** (`@tailwindcss/vite` + oxide) | https://tailwindcss.com/docs | v4 — CSS-first config (`@theme`), NOT v3 `tailwind.config.js` JS theme. |
| Zod | `^4.0.17` | https://zod.dev | **v4** — not v3 API. |
| framer-motion | `^11.18.2` | https://www.framer.com/motion | v11. |
| react-hook-form | `^7.62.0` | https://react-hook-form.com | v7. |
| Vite | per host build | https://vite.dev | — |
| @internationalized/date | `^3.5.6` | https://react-spectrum.adobe.com/internationalized/date | Used by HeroUI date inputs. |
| recharts | `^2.15.3` | https://recharts.org | — |
| chart.js / react-chartjs-2 | `^4.4.9` / `^5.3.0` | https://www.chartjs.org/docs | — |
| lucide-react | `^0.469.0` | https://lucide.dev | — |
| @dnd-kit | core `^6` / sortable `^10` | https://docs.dndkit.com | — |

## Common drift traps (read before writing framework code)

- **Inertia v1 → v2:** v1's global `Inertia` object is gone. Use `router` from
  `@inertiajs/react` and the `useForm()` hook. (See `docs/standards/inertia-standard.md`.)
- **NextUI → HeroUI:** the library was renamed. Import from `@heroui/react`, never `@nextui-org/*`.
- **Tailwind v3 → v4:** theme is configured in CSS via `@theme`, not a JS config object.
- **Zod v3 → v4:** error-map and some method signatures changed.
- **Laravel 12 vs 13:** we are pinned to 12.x — do not introduce 13-only features.
