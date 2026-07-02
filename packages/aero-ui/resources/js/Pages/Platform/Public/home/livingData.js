// ─── Living data-OS — redesign data layer ─────────────────────────────────────
// Real product screenshots served from the host public dir (/images/landing/*).
import {
  UserGroupIcon, BanknotesIcon, CurrencyDollarIcon, BuildingOffice2Icon,
  ClipboardDocumentListIcon, CubeIcon, TruckIcon, ShoppingCartIcon,
  BeakerIcon, ShieldCheckIcon, FolderOpenIcon, DocumentMagnifyingGlassIcon,
  BoltIcon, ChartBarIcon, LockClosedIcon, ArrowPathIcon,
} from '@heroicons/react/24/outline';

export const IMG = '/images/landing';
const P = `${IMG}/product`;

// Themed product shots (dark/light captured live from the app)
export const SHOTS = {
  dashboard:    { dark: `${P}/dashboard-dark.png`,    light: `${P}/dashboard-light.png` },
  employees:    { dark: `${P}/employees-dark.png`,    light: `${P}/employees-light.png` },
  payroll:      { dark: `${P}/payroll-dark.png`,      light: `${P}/payroll-light.png` },
  leave:        { dark: `${P}/leave-dark.png`,        light: `${P}/leave-light.png` },
  subscription: { dark: `${P}/subscription-dark.png`, light: `${P}/subscription-light.png` },
};

// ── Hero floating extracted cards (rebuilt in DOM, legible at any size) ───────
export const HERO_CARDS = [
  { kind: 'stat', accent: 'cyan',   label: 'Total headcount', value: '250', delta: '5 this month',
    spark: [30, 42, 38, 55, 60, 72, 85] },
  { kind: 'stat', accent: 'indigo', label: 'Net payroll · June', value: '$36.06M', delta: '2 runs approved' },
  { kind: 'toast', accent: 'amber', title: 'Payroll run approved', body: 'June 2026 · 250 payslips generated' },
];

// ── Hero KPI chips (count-up) ────────────────────────────────────────────────
export const HERO_KPIS = [
  { value: 320,   suffix: '+',  decimals: 0, label: 'Enterprises deployed' },
  { value: 99.97, suffix: '%',  decimals: 2, label: 'Uptime SLA' },
  { value: 8.5,   suffix: 'M+', decimals: 1, label: 'Payroll records / day' },
  { value: 17,    suffix: '+',  decimals: 0, label: 'Business modules' },
];

// ── Constellation nodes (module icons orbiting the core) ─────────────────────
// ring: 0 = inner orbit, 1 = outer orbit · angle in degrees (starting position)
export const CONSTELLATION = [
  { id: 'hrm',        icon: UserGroupIcon,              accent: 'cyan',   ring: 0, angle: 0 },
  { id: 'payroll',    icon: BanknotesIcon,              accent: 'indigo', ring: 0, angle: 60 },
  { id: 'finance',    icon: CurrencyDollarIcon,         accent: 'indigo', ring: 0, angle: 120 },
  { id: 'crm',        icon: BuildingOffice2Icon,        accent: 'amber',  ring: 0, angle: 180 },
  { id: 'project',    icon: ClipboardDocumentListIcon,  accent: 'indigo', ring: 0, angle: 240 },
  { id: 'ims',        icon: CubeIcon,                   accent: 'cyan',   ring: 0, angle: 300 },
  { id: 'scm',        icon: TruckIcon,                  accent: 'amber',  ring: 1, angle: 30 },
  { id: 'pos',        icon: ShoppingCartIcon,           accent: 'indigo', ring: 1, angle: 75 },
  { id: 'quality',    icon: BeakerIcon,                 accent: 'cyan',   ring: 1, angle: 120 },
  { id: 'compliance', icon: ShieldCheckIcon,            accent: 'amber',  ring: 1, angle: 165 },
  { id: 'dms',        icon: FolderOpenIcon,             accent: 'indigo', ring: 1, angle: 210 },
  { id: 'rfi',        icon: DocumentMagnifyingGlassIcon,accent: 'cyan',   ring: 1, angle: 255 },
  { id: 'assistant',  icon: BoltIcon,                   accent: 'amber',  ring: 1, angle: 300 },
  { id: 'analytics',  icon: ChartBarIcon,               accent: 'cyan',   ring: 1, angle: 345 },
];

// ── Signature §1 — sticky-scroll module storytelling ─────────────────────────
export const STORY_SLIDES = [
  {
    id: 'hrm',
    tag: '01 / People',
    accent: 'cyan',
    icon: UserGroupIcon,
    title: 'Human capital,',
    highlight: 'fully orchestrated.',
    body: 'Digital onboarding, department hierarchy, leave policies, biometric attendance, recruitment, and 360° performance reviews — the entire employee lifecycle in one auditable system, gated by HRMAC down to the individual action.',
    shot: SHOTS.employees,
    stat: 'Full lifecycle coverage',
  },
  {
    id: 'payroll',
    tag: '02 / Payroll',
    accent: 'indigo',
    icon: BanknotesIcon,
    title: 'Multi-jurisdiction payroll,',
    highlight: 'run on autopilot.',
    body: 'Scheduled automated runs across currencies and tax regimes, configurable allowance and deduction tiers, bulk payslip generation, and an immutable audit trail. Two days of manual work collapses into a scheduled job.',
    shot: SHOTS.payroll,
    stat: '50+ tax rule templates',
  },
  {
    id: 'analytics',
    tag: '03 / Intelligence',
    accent: 'amber',
    icon: ChartBarIcon,
    title: 'Every module,',
    highlight: 'one live command deck.',
    body: 'Role-gated KPI dashboards refresh in near real-time and join data across every module. Your quarterly review stops being a slide deck and becomes a living, drill-down surface the board can interrogate.',
    shot: SHOTS.dashboard,
    stat: 'Real-time cross-module KPIs',
  },
];

// ── Signature §2 — parallax architecture pillars ─────────────────────────────
export const ARCH_PILLARS = [
  {
    id: 'tenant',
    icon: LockClosedIcon,
    accent: 'cyan',
    title: 'Isolated tenant databases',
    body: 'Every organisation runs on its own database. Zero shared schemas, zero row-level leakage — complete data sovereignty from day one.',
  },
  {
    id: 'hrmac',
    icon: ShieldCheckIcon,
    accent: 'indigo',
    title: 'HRMAC access control',
    body: 'A four-level permission hierarchy — module → submodule → component → action — applied uniformly across every feature in the platform.',
  },
  {
    id: 'async',
    icon: BoltIcon,
    accent: 'amber',
    title: 'Async-first processing',
    body: 'Payroll, imports, reports, and indexing all run as queued jobs. The SPA stays sub-100ms responsive while workers absorb the load.',
  },
  {
    id: 'modular',
    icon: ArrowPathIcon,
    accent: 'cyan',
    title: 'No-code configuration',
    body: 'Custom fields, approval chains, roles, and notification rules are configured per tenant — no deployment, no developer, no downtime.',
  },
];
