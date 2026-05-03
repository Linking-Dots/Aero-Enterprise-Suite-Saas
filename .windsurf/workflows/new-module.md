---
description: "Scaffold a new AEOS365 package from scratch."
---

# /new-module Workflow

1. Create directory `packages/aero-{name}/` with standard structure:
   - `composer.json` (with `Aero{Name}ServiceProvider` in `extra.laravel.providers` and `extra.aero` metadata)
   - `config/{name}.php` (settings) and `config/module.php` (HRMAC hierarchy)
   - `database/migrations/`, `database/factories/`, `database/seeders/`
   - `resources/js/Pages/` (frontend), `resources/views/` (blade)
   - `routes/web.php`, `routes/admin.php`, `routes/api.php`
   - `src/Aero{Name}ServiceProvider.php`
   - `src/Http/Controllers/`, `src/Http/Middleware/`, `src/Http/Requests/`
   - `src/Models/`, `src/Services/`, `src/Policies/`
   - `tests/Feature/`, `tests/Unit/`
2. Provider extends `Illuminate\Support\ServiceProvider` (or `AbstractModuleProvider` for tenant-scoped modules).
3. Register in `aeos365/composer.json` `require` block.
4. Run `composer update` in host app.
5. Add routes with `hrmac:*` middleware.
6. Add frontend pages in `packages/aero-ui/resources/js/Pages/{Name}/`.
