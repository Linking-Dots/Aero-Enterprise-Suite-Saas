---
description: "Migrate legacy HeroUI/framer-motion code to @aero/ui."
---

# /migrate-legacy-ui Workflow

1. Replace `import ... from '@heroui/react'` with `import { ... } from '@aero/ui'`.
2. Replace `import ... from '@heroicons/react'` with `import { Icon } from '@aero/ui'` and use `name` prop.
3. Replace `showToast.promise(...)` with `const { toast } = useToast()` hook.
4. Remove `framer-motion` imports. Replace with CSS transitions or component `motion` prop.
5. Replace custom layout with `App.jsx` `.layout` property.
6. Replace `<button>`, `<a>`, `<table>`, `<input>` with `@aero/ui` equivalents.
7. Run visual regression test (Playwright snapshot).
