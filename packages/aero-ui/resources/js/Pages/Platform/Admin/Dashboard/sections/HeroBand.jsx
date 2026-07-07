import { Card, CardBody, Sparkline } from '@aero/ui';
import { HeroValue, DeltaChip, sampleSeries } from '../lib.jsx';

/**
 * North-star KPI band — 5 hero metrics, each count-up value + delta + sparkline.
 */
export default function HeroBand({ kpis = [] }) {
  return (
    <div className="lcc-band">
      {kpis.map((k) => (
        <Card key={k.key}>
          <CardBody>
            <div className="lcc-hero">
              <span className="lcc-hero__label">{k.label}</span>
              <HeroValue value={k.value} format={k.format} />
              <div className="lcc-hero__foot">
                <DeltaChip value={k.delta} invert={k.invert} />
                {k.spark?.length > 0 && (
                  <Sparkline data={sampleSeries(k.spark, 14)} height={26} intent={k.invert ? 'amber' : 'cyan'} />
                )}
              </div>
            </div>
          </CardBody>
        </Card>
      ))}
    </div>
  );
}
