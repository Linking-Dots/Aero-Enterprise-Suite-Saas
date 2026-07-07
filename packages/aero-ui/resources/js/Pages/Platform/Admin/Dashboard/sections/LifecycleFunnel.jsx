import { Card, CardBody } from '@aero/ui';
import { fmtNumber } from '../lib.jsx';

/**
 * Tenant lifecycle — signup→trial→active funnel + status pipeline chips.
 */
export default function LifecycleFunnel({ lifecycle }) {
  const funnel = lifecycle?.funnel ?? [];
  const pipeline = lifecycle?.pipeline ?? [];
  const top = Math.max(1, ...funnel.map((s) => s.count));

  return (
    <Card>
      <CardBody>
        <div className="lcc-card-h">
          <span className="lcc-card-h__title">Tenant lifecycle</span>
        </div>

        <div className="lcc-funnel">
          {funnel.map((s) => (
            <div key={s.stage} className="lcc-funnel__row">
              <div className="lcc-funnel__meta">
                <span className="lcc-funnel__stage">{s.stage}</span>
                <span className="lcc-funnel__count">{fmtNumber(s.count)}</span>
              </div>
              <div className="lcc-funnel__track">
                <div className="lcc-funnel__fill" style={{ width: `${(s.count / top) * 100}%` }} />
              </div>
            </div>
          ))}
        </div>

        <div className="lcc-pipeline">
          {pipeline.map((p) => (
            <div key={p.status} className="lcc-chip">
              <span className="lcc-chip__n">{p.count}</span>
              <span className="lcc-chip__l">{p.status}</span>
            </div>
          ))}
        </div>
      </CardBody>
    </Card>
  );
}
