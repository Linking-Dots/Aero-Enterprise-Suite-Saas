/**
 * Tenant/Dashboard — root page component.
 *
 * Receives all props from DashboardController::index() via Inertia.
 * Each widget is independently refreshable via useWidgetRefresh.
 *
 * Layout: 4-column bento grid using aeos-col-span-* classes.
 *
 * Row 1  (span 4):  W1  Welcome
 * Row 2  (4 × 1):   W2  KPI tiles
 * Row 3  (3 + 1):   W3  Activity chart  +  W4 Sessions
 * Row 4  (2 + 2):   W5  Security        +  W6 Storage & Plan
 * Row 5  (3 + 1):   W7  Audit log       +  W10 Quick actions
 * Row 6  (2 + 2):   W8  Onboarding      +  W9  Announcements
 * Row 7  (2 + 2):   W11 Peak hours      +  W12 System health (span 2)
 * NOTE: W12 is full-width (span 4) and sits in its own row at the bottom.
 */

import App            from '@/Pages/App.jsx';
import DashboardRail   from './Dashboard/DashboardRail.jsx';
import { PageHeader }  from '@aero/ui';

import { WelcomeWidget }       from './Dashboard/widgets/WelcomeWidget.jsx';
import { KpiRow }              from './Dashboard/widgets/KpiRow.jsx';
import { ActivityChartWidget } from './Dashboard/widgets/ActivityChartWidget.jsx';
import { SessionsWidget }      from './Dashboard/widgets/SessionsWidget.jsx';
import { SecurityWidget }      from './Dashboard/widgets/SecurityWidget.jsx';
import { StoragePlanWidget }   from './Dashboard/widgets/StoragePlanWidget.jsx';
import { AuditLogWidget }      from './Dashboard/widgets/AuditLogWidget.jsx';
import { QuickActionsWidget }  from './Dashboard/widgets/QuickActionsWidget.jsx';
import { OnboardingWidget }    from './Dashboard/widgets/OnboardingWidget.jsx';
import { AnnouncementsWidget } from './Dashboard/widgets/AnnouncementsWidget.jsx';
import { PeakHoursWidget }     from './Dashboard/widgets/PeakHoursWidget.jsx';
import { SystemHealthWidget }  from './Dashboard/widgets/SystemHealthWidget.jsx';

export default function Dashboard({
    welcomeData        = {},
    coreStats          = null,
    onboardingProgress = null,
    announcements      = null,
    userActivity       = null,
    sessionsData       = null,
    securityOverview   = null,
    storageAnalytics   = null,
    subscriptionInfo   = null,
    recentAuditLog     = null,
    quickActions       = [],
    systemHealth       = null,
}) {
    return (
        <div className="dash-grid">

            {/* ── Standardized page header (breadcrumb/title standard) ── */}
            <PageHeader
                className="aeos-col-span-4 dash-page-header"
                title="Dashboard"
                description="Activity, security and usage at a glance."
            />

            {/* ── Row 1 · Welcome ─────────────────────────────────── */}
            <WelcomeWidget
                welcomeData={welcomeData}
                subscriptionInfo={subscriptionInfo}
                systemHealth={systemHealth}
            />

            {/* ── Row 2 · KPI tiles (4 cards, each span-1) ─────────── */}
            <KpiRow coreStats={coreStats} userActivity={userActivity} />

            {/* ── Row 3 · Activity chart (span 3) + Sessions (span 1) */}
            <ActivityChartWidget userActivity={userActivity} />
            <SessionsWidget      sessionsData={sessionsData} />

            {/* ── Row 4 · Security (span 2) + Storage/Plan (span 2) ── */}
            <SecurityWidget    securityOverview={securityOverview} />
            <StoragePlanWidget storageAnalytics={storageAnalytics} subscriptionInfo={subscriptionInfo} />

            {/* ── Row 5 · Audit log (span 3) + Quick actions (span 1) */}
            <AuditLogWidget    recentAuditLog={recentAuditLog} />
            <QuickActionsWidget quickActions={quickActions} />

            {/* ── Row 6 · Onboarding (span 2) + Announcements (span 2) */}
            <OnboardingWidget    onboardingProgress={onboardingProgress} />
            <AnnouncementsWidget announcements={announcements} />

            {/* ── Row 7 · Peak hours (span 2) + System health (span 2) */}
            <PeakHoursWidget   userActivity={userActivity} />
            <SystemHealthWidget systemHealth={systemHealth} />

        </div>
    );
}

Dashboard.layout = page => (
    <App title="Dashboard" railTitle="At a glance" rail={<DashboardRail />}>
        {page}
    </App>
);
