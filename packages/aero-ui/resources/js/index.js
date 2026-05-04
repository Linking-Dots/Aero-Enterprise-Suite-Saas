/**
 * @aero/ui — Flat barrel export
 *
 * Import anything from a single entry point:
 *   import { Card, Button, ThemeProvider, IndexPageLayout } from '@aero/ui';
 *
 * All visual styling is theme-controlled via ThemeProvider + app.css.
 * No per-instance style overrides — use intent props for semantics only.
 */

// ── Theme ─────────────────────────────────────────────────────────
export { ThemeProvider, useTheme }          from './theme/ThemeProvider.jsx';
export { default as ThemeDrawer }           from './theme/ThemeDrawer.jsx';
export { default as ThemeToggle }           from './theme/ThemeToggle.jsx';

// ── Shells ────────────────────────────────────────────────────────
export {
  AppShell, SidebarShell, TopNavShell,
  FloatingShell, CommandShell,
}                                           from './shells/Shells.jsx';

// ── Page Templates ────────────────────────────────────────────────
export {
  IndexPageLayout, DetailPageLayout,
  FormPageLayout, DashboardLayout,
}                                           from './templates/Templates.jsx';

// ── Primitives ────────────────────────────────────────────────────
export {
  cx,
  Box, Stack, HStack, VStack, Spacer, Flex1, Divider,
  Heading, Text, Label, Mono, Eyebrow,
  Card, CardHeader, CardBody, CardFooter,
  CardBody as CardContent,
}                                           from './components/Primitives.jsx';

// ── Display ───────────────────────────────────────────────────────
export {
  Badge, Status, Pill,
  Avatar, AvatarStack,
  Kbd, Tag,
  Progress, Skeleton, Alert,
}                                           from './components/Display.jsx';

// ── Data ──────────────────────────────────────────────────────────
export {
  KPI, Sparkline, Stat, MetricChip,
  ProgressRow, DataTable, EmptyState,
}                                           from './components/Data.jsx';

// ── Navigation ────────────────────────────────────────────────────
export {
  Tabs, Breadcrumb,
  NavItem, NavGroup,
  SectionHeader, PageHeader,
  Pagination,
  Steps,
}                                           from './components/Navigation.jsx';

// ── Forms ─────────────────────────────────────────────────────────
export {
  Field, Input, Textarea, Select,
  Checkbox, Radio, RadioGroup,
  Toggle, SearchInput, FileInput, DatePicker,
  OtpInput, PasswordStrength,
}                                           from './components/Forms.jsx';

// ── Actions ───────────────────────────────────────────────────────
export {
  Button, IconButton, ButtonGroup, Link,
}                                           from './components/Actions.jsx';

// ── Overlays ──────────────────────────────────────────────────────
export {
  Modal, Drawer,
  Tooltip, Popover, Menu,
  Banner, ConfirmDialog,
}                                           from './components/Overlays.jsx';

// ── Feedback ──────────────────────────────────────────────────────
export { Toast, useToast }                  from './components/Feedback.jsx';

// ── Hooks ─────────────────────────────────────────────────────────
export {
  useBreakpoint, useReducedMotion, useMediaQuery,
  useHRMAC, useHRMACMany,
  useSavedViews,
}                                           from './hooks/index.js';

// ── Icons ─────────────────────────────────────────────────────────
export { Icon, icons }                      from './icons/icons.jsx';

// ── App Chrome ───────────────────────────────────────────────
export {
  AeosLogo, AppBrand, AppTopbarTitle, AppUserMenu,
}                                           from './components/AppChrome.jsx';

// ── Global Search ───────────────────────────────────────────
export { default as SearchOverlay }         from './components/SearchOverlay.jsx';

// ── Tags & Labels ───────────────────────────────────────────
export { default as TagPicker }             from './components/TagPicker.jsx';

// ── Saved Views ────────────────────────────────────────────
export {
  SavedViewsDropdown,
  SaveCurrentViewDialog,
  SavedViewsList,
  ShareViewDialog,
}                                           from './components/SavedViews.jsx';

// ── Public (Marketing) Components ────────────────────────────────
export {
  Section, Container,
  PublicSectionHeader,
  Marquee,
  PublicFeatureCard,
  PublicStatCard,
  PublicTestimonialCard,
  PublicPricingCard,
  Accordion,
  PublicHeader,
  PublicFooter,
}                                           from './components/Public.jsx';
