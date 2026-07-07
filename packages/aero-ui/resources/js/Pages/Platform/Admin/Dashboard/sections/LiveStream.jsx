import { Card, CardBody, Badge } from '@aero/ui';

/**
 * Live activity stream — newest platform audit events, refreshed by the poll.
 */
export default function LiveStream({ stream = [] }) {
  return (
    <Card>
      <CardBody>
        <div className="lcc-card-h">
          <span className="lcc-card-h__title">Live activity</span>
          <Badge intent="primary" size="sm" dot>live</Badge>
        </div>

        <div className="lcc-stream">
          {stream.length === 0 && <div className="lcc-stream__meta">No recent activity</div>}
          {stream.map((e) => (
            <div key={e.id} className="lcc-stream__item">
              <span className="lcc-stream__dot" />
              <div className="lcc-stream__body">
                <div className="lcc-stream__text">{e.text}{e.subject ? ` · ${e.subject}` : ''}</div>
                <div className="lcc-stream__meta">{e.actor} · {e.at}</div>
              </div>
            </div>
          ))}
        </div>
      </CardBody>
    </Card>
  );
}
