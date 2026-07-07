import { Card, CardBody, Badge } from '@aero/ui';

const SERVICE_LABELS = { database: 'Database', cache: 'Cache', queue: 'Queue', storage: 'Storage', mail: 'Mail', search: 'Search' };

/**
 * Operations pulse — service health + 24h error trend + ranked alerts.
 */
export default function OpsPulse({ ops, pulse }) {
  const services = ops?.services ?? {};
  const trend = ops?.errorTrend ?? [];
  const maxErr = Math.max(1, ...trend);
  const alerts = pulse?.alerts ?? [];

  return (
    <Card>
      <CardBody>
        <div className="lcc-card-h">
          <span className="lcc-card-h__title">Operations pulse</span>
          <Badge intent={ops?.unresolved ? 'warning' : 'success'} size="sm" dot>
            {ops?.errors24h ?? 0} errors / 24h
          </Badge>
        </div>

        <div className="lcc-services">
          {Object.entries(services).map(([key, status]) => (
            <div key={key} className={`lcc-service is-${status}`}>
              <span className="lcc-service__dot" />
              {SERVICE_LABELS[key] ?? key}
            </div>
          ))}
        </div>

        <div className="lcc-errtrend" aria-label="Error trend, last 24 hours">
          {trend.map((v, i) => (
            <div key={i} className="lcc-errtrend__bar" style={{ height: `${Math.max(6, (v / maxErr) * 100)}%` }} title={`${v} errors`} />
          ))}
        </div>

        <div className="lcc-alerts" style={{ marginTop: 'var(--aeos-space-4)' }} id="alerts">
          {alerts.length === 0 && (
            <div className="lcc-alert"><div className="lcc-alert__t">No active alerts</div></div>
          )}
          {alerts.map((a, i) => (
            <div key={i} className={`lcc-alert is-${a.severity}`}>
              <div>
                <div className="lcc-alert__t">{a.title}</div>
                <div className="lcc-alert__d">{a.detail}</div>
              </div>
            </div>
          ))}
        </div>
      </CardBody>
    </Card>
  );
}
