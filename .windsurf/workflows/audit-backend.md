---
description: "Audit backend PHP files for pattern violations."
---

# /audit-backend Workflow

1. Scan `*Controller.php` for `$request->validate(` → report count and files.
2. Scan `*.php` for `env(` outside `config/` → report count and files.
3. Scan `*ServiceProvider.php` for naming violations (not `Aero*ServiceProvider`).
4. Scan route files for missing `hrmac:` middleware.
5. Scan `DB::table(` usage where Eloquent could replace it.
6. Check controller action line counts (>30 lines = fat controller).
7. Generate prioritized fix list ordered by severity: CRITICAL → HIGH → MEDIUM → LOW.
