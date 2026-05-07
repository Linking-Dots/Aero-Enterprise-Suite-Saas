# AEOS365 Standalone Host

Standalone host application for distributing AEOS product packages as installable software.

## Initial bundle
- `aero/hrm` (default — change in composer.json to bundle a different initial product)

## Add-ons
Additional products are installed at runtime via Settings > Add-ons (admin panel).
Each add-on requires a separate license key purchased from the marketplace.

## Packaging for distribution

Run from the monorepo root:
```bash
php artisan aero:package-product --output=./dist
```

This produces a distributable ZIP with:
- All Composer packages (production deps only)
- Empty `storage/app/` (fresh install state — no aeos.* runtime files)
- No `.env`, `.git`, or dev tooling

## Development
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```
