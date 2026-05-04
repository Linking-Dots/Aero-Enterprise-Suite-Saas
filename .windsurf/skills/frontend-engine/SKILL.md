---
name: frontend-engine
description: "Build or modify React pages using the @aero/ui design system for aeos365. Covers App.jsx layout wrapper, page templates, ThemeProvider, primitives, data components, forms, overlays, hooks, and icons. Detects vanilla HTML leakage and legacy HeroUI/framer-motion/showToast.promise patterns."
---

# aeos365 Frontend Engineering Skill

## Active Design System: @aero/ui (NOT HeroUI)

The project has **two coexisting frontend systems**:
- **ACTIVE:** `@aero/ui` in `packages/aero-ui/resources/js/` — used by all pages in `packages/aero-ui/resources/js/Pages/`
- **LEGACY:** `@heroui/react`, `@heroicons/react`, `framer-motion`, `showToast.promise()` — only in `aero-assistant/` and `aero-rfi/`

Never use legacy imports in new code under `packages/aero-ui/`.

## Barrel Export

```jsx
import {
  ThemeProvider, useTheme,
  AppShell, SidebarShell, TopNavShell, FloatingShell, CommandShell,
  IndexPageLayout, DetailPageLayout, FormPageLayout, DashboardLayout,
  Card, CardHeader, CardBody, CardFooter,
  Button, IconButton, ButtonGroup, Link,
  Badge, Status, Pill, Avatar, AvatarStack, Kbd, Tag,
  Progress, Skeleton, Alert,
  KPI, Sparkline, Stat, MetricChip, ProgressRow, DataTable, EmptyState,
  Tabs, Breadcrumb, NavItem, NavGroup, SectionHeader, PageHeader, Pagination, Steps,
  Field, Input, Textarea, Select, Checkbox, Radio, RadioGroup, Toggle, SearchInput, FileInput, DatePicker, OtpInput, PasswordStrength,
  Modal, Drawer, Tooltip, Popover, Menu, Banner, ConfirmDialog,
  Toast, useToast,
  Icon,
  Box, Stack, HStack, VStack, Spacer, Flex1, Divider,
  Heading, Text, Label, Mono, Eyebrow,
  cx,
  useBreakpoint, useReducedMotion, useMediaQuery,
  AeosLogo, AppBrand, AppTopbarTitle, AppUserMenu,
  Section, Container, PublicSectionHeader, PublicFeatureCard, Marquee,
} from '@aero/ui';
```

## Page Layout Architecture

### App.jsx is the Shell Wrapper

**NEVER wrap pages directly in `AppShell`.** Every authenticated page exports a `.layout` property:

```jsx
import App from '../App.jsx';

export default function EmployeeList({ employees }) {
  // page content using page templates...
}

EmployeeList.layout = page => <App title="Employees">{page}</App>;
```

`App.jsx` (at `packages/aero-ui/resources/js/Pages/App.jsx`) provides:
- `AppShell` (auto-selected variant: sidebar/topnav/floating/command)
- Navigation from Inertia `navigation` / `navigationGroups` props
- `AppBrand`, `AppTopbarTitle`, `AppUserMenu`
- `ThemeDrawer` for theme customization

### Page Templates for Internal Layout

Pages use these templates for their **internal content structure** (not shell):

| Template | Use For | Key Props |
|----------|---------|-----------|
| `DashboardLayout` | Dashboard pages | `title`, `cols={{ base:1, md:2, lg:4 }}`, `actions` |
| `IndexPageLayout` | List/table pages | `title`, `filters`, `actions`, `bulkActions`, `columns`, `data` |
| `FormPageLayout` | Create/edit forms | `title`, `form`, `sidebar`, `submitLabel` |
| `DetailPageLayout` | Entity detail views | `title`, `tabs`, `metadata`, `actions` |

Example — Dashboard page:
```jsx
<DashboardLayout title="Dashboard" cols={{ base: 1, md: 2, lg: 4 }}>
  <Card>...</Card>
  <KPI label="Users" value={120} delta={+5} />
</DashboardLayout>
```

Example — Index/list page:
```jsx
<IndexPageLayout
  title="Employees"
  actions={<Button intent="primary" as={Link} href="/employees/create">Add Employee</Button>}
  filters={
    <HStack gap={2}>
      <SearchInput placeholder="Search employees..." value={search} onChange={setSearch} />
      <Select value={department} onChange={setDepartment}>
        <option value="">All Departments</option>
        {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
      </Select>
    </HStack>
  }
>
  <DataTable columns={columns} data={employees} empty={<EmptyState title="No employees" description="Add your first employee to get started." />} />
  <Pagination page={page} total={totalPages} onChange={setPage} />
</IndexPageLayout>
```

## Theme System

```jsx
const { mode, variant, shell, density, radius, accent, fontDisplay, fontBody, fontMono, fontScale, motion } = useTheme();
```

| Property | Values | Usage |
|----------|--------|-------|
| `mode` | `dark` / `light` | Conditional logic, CSS class toggles |
| `variant` | `default`, `warm`, `cool`, `oled`, `forest`, `rose`, `midnight`, `paper`, `high-contrast` | Theme flavor |
| `shell` | `sidebar`, `topnav`, `floating`, `command` | Navigation layout |
| `density` | `compact`, `comfortable`, `spacious` | Spacing scale |
| `radius` | `sharp`, `balanced`, `soft` | Border radius |
| `accent` | hex color string | Brand accent color |
| `motion` | `full`, `reduced`, `off` | Animation preference |

**Rules:**
- NEVER hardcode colors. Derive from theme context or CSS custom properties (`var(--aeos-primary)`, `var(--aeos-success)`, `var(--aeos-destructive)`).
- NEVER manually set `document.body` classes — `ThemeProvider` handles this.

## Responsive Design

```jsx
const bp = useBreakpoint(); // 'sm' | 'md' | 'lg' | 'xl' | '2xl'
const isMobile = bp === 'sm';
const isTablet = bp === 'sm' || bp === 'md';
```

**Rules:**
- ALWAYS use `useBreakpoint()` for responsive logic.
- NEVER use `window.innerWidth` checks or manual `useState` for responsive state.

## Icons

```jsx
<Icon name="users" size={18} />
<Icon name="arrowRight" size={14} tone="tertiary" />
```

**Rules:**
- NEVER import `@heroicons/react`.
- Icon names are camelCase: `arrowRight`, `userGroup`, `chartBar`, `settings`, `bell`, `folder`, `search`, `calendar`, `clock`, `pin`, `globe`, `mail`, `tag`, `checkCircle`, `alertCircle`, `trending`, `layout`, `cube`, `truck`, `shoppingCart`, `beaker`, `bolt`, `lock`, `lockOpen`, `refresh`, `puzzle`, `database`, `phone`, `link`, `star`, `sparkles`, `x`, `menu`.

## Toasts

```jsx
const { toast } = useToast();

toast({
  title: 'Employee created',
  description: 'John Doe was added successfully.',
  intent: 'success', // success | error | warning | info | neutral
});
```

**Rules:**
- NEVER use `showToast.promise()` — that is legacy from `packages/aero-ui/resources/js/utils/toastUtils.jsx`.

## Styling Rules

1. **Use `cx()` for class names** — NEVER concatenate strings manually.
   ```jsx
   className={cx('aeos-card', isActive && 'active', ['class-a', 'class-b'])}
   ```
2. **Use `Box`, `HStack`, `VStack` for layout** — NEVER manually write `display:flex` divs.
3. **Use theme CSS variables** for colors: `var(--aeos-primary)`, `var(--aeos-success)`, `var(--aeos-destructive)`, `var(--aeos-warning)`, `var(--aeos-text-primary)`, `var(--aeos-text-secondary)`, `var(--aeos-text-tertiary)`, `var(--aeos-divider)`, `var(--aeos-bg-base)`, `var(--aeos-bg-elevated)`.
4. **Use component intent/size props** — NEVER add per-instance style overrides. Components expose semantic props only.

## Animation

- Use CSS transitions via component props (`motion` context from `useTheme()`).
- NEVER import `framer-motion` in new `aero-ui` code.
- For reduced motion: `const reduced = useReducedMotion();` then conditionally disable animations.

## Legacy Pattern Detection

When auditing existing code, flag these as **legacy**:
- `import ... from '@heroui/react'`
- `import ... from '@heroicons/react'`
- `import { motion } from 'framer-motion'`
- `showToast.promise(`
- Manual `window.innerWidth` checks
- Vanilla `<button>`, `<a>`, `<table>`, `<input>` tags inside `aero-ui` pages

## Reference Files

- App.jsx shell: `packages/aero-ui/resources/js/Pages/App.jsx`
- Dashboard page (gold standard): `packages/aero-ui/resources/js/Pages/Tenant/Dashboard.jsx`
- Primitives: `packages/aero-ui/resources/js/components/Primitives.jsx`
- ThemeProvider: `packages/aero-ui/resources/js/theme/ThemeProvider.jsx`
- Shells: `packages/aero-ui/resources/js/shells/Shells.jsx`
- Hooks: `packages/aero-ui/resources/js/hooks/index.js`
- Barrel export: `packages/aero-ui/resources/js/index.js`
