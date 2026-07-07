import { Card, CardBody, ProgressRow } from '@aero/ui';

/**
 * Engagement — module adoption (% of active tenants) + feature-usage intensity.
 */
export default function EngagementPanel({ engagement }) {
  const modules = engagement?.modules ?? [];
  const features = engagement?.features ?? [];
  const maxFeature = Math.max(1, engagement?.maxFeature ?? 1);

  return (
    <Card>
      <CardBody>
        <div className="lcc-card-h">
          <span className="lcc-card-h__title">Engagement · adoption</span>
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--aeos-space-3)' }}>
          {modules.length === 0 && <span className="lcc-alert__d">No module subscriptions yet</span>}
          {modules.map((m) => (
            <ProgressRow key={m.module} label={`${m.module.toUpperCase()} · ${m.tenants} tenants`} value={m.pct} max={100} />
          ))}
        </div>

        <div className="lcc-kv" style={{ marginTop: 'var(--aeos-space-4)', paddingTop: 'var(--aeos-space-4)', borderTop: 'var(--aeos-border-width) solid var(--aeos-divider)' }}>
          {features.map((f) => (
            <div key={f.feature} className="lcc-kv__row">
              <span className="lcc-kv__label is-feature" title={f.feature}>{f.feature}</span>
              <span className="lcc-kv__bar-wrap">
                <span className="lcc-kv__bar" style={{ width: `${(f.count / maxFeature) * 100}%` }} />
              </span>
              <span className="lcc-kv__val">{f.count}</span>
            </div>
          ))}
        </div>
      </CardBody>
    </Card>
  );
}
