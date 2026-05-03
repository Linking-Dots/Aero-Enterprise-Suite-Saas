# AEOS365 Cascade Rules

## Monorepo Boundaries
- Host app `aeos365/` is OFF-LIMITS for business logic. Only `.env`, `composer.json`, `vite.config.js`, `bootstrap/`, `public/`, `storage/`.
- ALL code MUST go in `packages/aero-*/src/`.
- NEVER create files in `aeos365/app/`, `aeos365/resources/`, `aeos365/routes/`.

## Frontend System (Single Source of Truth)
- The ACTIVE design system is `@aero/ui` in `packages/aero-ui/resources/js/`.
- `@heroui/react`, `@heroicons/react`, `framer-motion`, `showToast.promise()` are LEGACY.
- New pages MUST use: `App.jsx` via `.layout` property, page templates (`IndexPageLayout`, `FormPageLayout`), primitives from `@aero/ui`.
- NEVER use vanilla `<button>`, `<a>`, `<table>`, `<input>` when `@aero/ui` has a replacement.
- Responsive: `const bp = useBreakpoint();` not manual `window.innerWidth`.
- NEVER call `env()` outside `config/` files. Use `config()` with default fallback.
- Controllers MUST be thin (≤30 lines/action). Delegate to Service classes.
- ALWAYS eager-load relationships (`with()`, `withCount()`).
- Prefer `Model::query()` over `DB::` facade.
- ALL tenant routes MUST have `hrmac:{module}.{submodule}.{component}.{action}` middleware.
- Middleware stack order: `web` → `auth` → `verified` → `hrmac:*`.
- Service providers MUST be named `Aero{Module}ServiceProvider`.
- NEVER import concrete classes from other packages in `aero-core` — use contracts/interfaces.
- Run `vendor/bin/pint --dirty` before committing.
- Use PHP 8.2 features: constructor property promotion, match expressions, named arguments.
- Every new feature MUST have a PHPUnit test. Every Inertia page MUST have a Playwright test.
