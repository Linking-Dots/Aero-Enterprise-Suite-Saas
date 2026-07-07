/**
 * Grand tour — the ~5-step "aha moment" walkthrough for the live demo.
 * Order matters. `route` = the tenant page the step lives on (Inertia path);
 * `element` = a stable [data-tour] anchor on that page (omit for a centered modal).
 * Copy: 1–2 sentences, second person, benefit-first (onboarding best practice).
 */
export const GRAND_TOUR = [
  {
    // Centered welcome modal — no target element.
    title: 'Welcome to the AEOS365 live demo 👋',
    description: "You're in a fully interactive demo — click around freely. Nothing you do here is permanent; it resets automatically.",
  },
  {
    route: '/dashboard',
    element: '[data-tour="dashboard-kpis"]',
    title: 'Your command center',
    description: 'Headcount, attendance and payroll at a glance — this is your daily starting point.',
  },
  {
    route: '/hrm/employees',
    element: '[data-tour="employees-table"]',
    title: 'Your people, organized',
    description: 'Search, filter and open any employee to see their full profile and records.',
  },
  {
    route: '/hrm/leave/applications',
    element: '[data-tour="leave-approve"]',
    title: 'Approve time-off in one click',
    description: 'Pending leave requests land here — go ahead and approve one.',
  },
  {
    route: '/hrm/payroll/runs',
    element: '[data-tour="payroll-run"]',
    title: 'Run payroll with confidence',
    description: 'Generate, preview and finalize payroll from here. That’s the tour — now explore freely!',
  },
];
