import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardBody, Button, Icon } from '@aero/ui';
import { PulseDot } from '../lib.jsx';

function LiveClock() {
  const [now, setNow] = useState(() => new Date());
  useEffect(() => {
    const id = setInterval(() => setNow(new Date()), 1000);
    return () => clearInterval(id);
  }, []);
  const t = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'Asia/Dhaka' });
  return <span className="lcc-clock" aria-label="Current time (Dhaka)">{t} BST</span>;
}

export default function CommandStrip({ welcome, pulse, onRefresh }) {
  const alertCount = pulse?.alertCount ?? 0;
  return (
    <Card>
      <CardBody>
        <div className="lcc-strip">
          <div className="lcc-strip__id">
            <div className="lcc-strip__greet">{welcome?.greeting ?? 'Welcome'}, {welcome?.userName ?? 'Admin'}</div>
            <div className="lcc-strip__sub">{welcome?.date ?? ''} · Platform Command Center</div>
          </div>
          <div className="lcc-strip__spacer" />
          <div className="lcc-strip__actions">
            <LiveClock />
            <PulseDot status={pulse?.status ?? 'operational'} />
            <Button intent="ghost" size="sm" onClick={() => router.visit('/dashboard#alerts')}>
              <Icon name="bell" size={16} /> Alerts{alertCount ? ` (${alertCount})` : ''}
            </Button>
            <Button intent="secondary" size="sm" onClick={onRefresh}>
              <Icon name="trending" size={16} /> Refresh
            </Button>
            <Button intent="ghost" size="sm" onClick={() => router.visit('/settings')} aria-label="Settings">
              <Icon name="settings" size={16} />
            </Button>
          </div>
        </div>
      </CardBody>
    </Card>
  );
}
