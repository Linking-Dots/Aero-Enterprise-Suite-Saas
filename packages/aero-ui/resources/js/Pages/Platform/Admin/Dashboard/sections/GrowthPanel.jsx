import { Card, CardBody } from '@aero/ui';
import { fmtNumber } from '../lib.jsx';

/**
 * Growth & acquisition — new signups, lead pipeline, top sources, trial conv.
 */
export default function GrowthPanel({ growth }) {
  const sources = growth?.topSources ?? [];
  const maxSource = Math.max(1, ...sources.map((s) => s.count));

  const stats = [
    { k: 'New sign-ups (30d)', v: fmtNumber(growth?.newSignups) },
    { k: 'Open leads', v: fmtNumber(growth?.totalLeads) },
    { k: 'Trial → paid', v: `${Number(growth?.trialConvRate ?? 0).toFixed(1)}%` },
    { k: 'Newsletter', v: fmtNumber(growth?.newsletterCount) },
  ];

  return (
    <Card>
      <CardBody>
        <div className="lcc-card-h">
          <span className="lcc-card-h__title">Growth &amp; acquisition</span>
        </div>

        <div className="lcc-pipeline" style={{ marginTop: 0, paddingTop: 0, borderTop: 'none' }}>
          {stats.map((s) => (
            <div key={s.k} className="lcc-chip">
              <span className="lcc-chip__n">{s.v}</span>
              <span className="lcc-chip__l">{s.k}</span>
            </div>
          ))}
        </div>

        <div className="lcc-kv" style={{ marginTop: 'var(--aeos-space-4)', paddingTop: 'var(--aeos-space-4)', borderTop: 'var(--aeos-border-width) solid var(--aeos-divider)' }}>
          {sources.map((s) => (
            <div key={s.source} className="lcc-kv__row">
              <span className="lcc-kv__label">{s.source.replace(/_/g, ' ')}</span>
              <span className="lcc-kv__bar-wrap">
                <span className="lcc-kv__bar" style={{ width: `${(s.count / maxSource) * 100}%` }} />
              </span>
              <span className="lcc-kv__val">{s.count}</span>
            </div>
          ))}
        </div>
      </CardBody>
    </Card>
  );
}
