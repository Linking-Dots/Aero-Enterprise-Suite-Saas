---
description: "Audit frontend JSX/JS files for pattern violations and legacy imports."
---

# /audit-frontend Workflow

1. Scan `aero-ui/resources/js/` for `@heroui/react` imports.
2. Scan for `@heroicons/react` imports.
3. Scan for `framer-motion` imports.
4. Scan for `showToast.promise` usage.
5. Scan for vanilla `<button>`, `<a>`, `<table>`, `<input>` tags.
6. Scan for `window.innerWidth` responsive checks.
7. Check pages export `.layout` to `App.jsx`.
8. Report drift score and migration priority per file.
