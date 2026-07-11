/**
 * DashboardRail — per-page context panel for the command shell's right rail.
 *
 * Only the command shell renders a rail; other shells ignore it. Surfaces live
 * operational counters (fresh from the widget endpoint) + permission-gated quick
 * actions + the command-palette shortcut, so command mode has real context.
 */
import { Link } from '@inertiajs/react';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';
import { safeRoute } from './panels.jsx';

const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform || '');

export default function DashboardRail({ quickActions = [] }) {
  const { data: stats } = useWidgetRefresh('coreStats', null);
  const { data: sessions } = useWidgetRefresh('sessionsData', null);
  const { data: security } = useWidgetRefresh('securityOverview', null);

  const failed = security?.failedLoginsLast24h ?? 0;
  const groups = Array.isArray(quickActions) ? quickActions.filter((g) => g.items?.length) : [];

  return (
    <div className="dash-rail">
      <div>
        <div className="dash-rail__h">Live now</div>
        <div className="dash-rail__rows">
          <div className="dash-rail__row"><span>Online users</span><b>{stats?.onlineUsers ?? 0}</b></div>
          <div className="dash-rail__row"><span>Sessions today</span><b>{sessions?.activeToday ?? 0}</b></div>
          <div className="dash-rail__row"><span>Failed logins</span><b className={failed > 0 ? 'is-warn' : ''}>{failed}</b></div>
          <div className="dash-rail__row"><span>Active sessions</span><b>{security?.activeSessions ?? 0}</b></div>
        </div>
      </div>

      {groups.length > 0 && (
        <div>
          <div className="dash-rail__h">Quick actions</div>
          <div className="dash-rail__links">
            {groups.flatMap((g) => g.items).map((it) => (
              <Link key={it.label} href={safeRoute(it.route, '#')} className="dash-rail__link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14M12 5v14" /></svg>
                <span>{it.label}</span>
              </Link>
            ))}
          </div>
        </div>
      )}

      <div>
        <div className="dash-rail__h">Shortcuts</div>
        <div className="dash-rail__kbd"><span>Command palette</span><kbd>{isMac ? '⌘K' : 'Ctrl K'}</kbd></div>
      </div>
    </div>
  );
}
