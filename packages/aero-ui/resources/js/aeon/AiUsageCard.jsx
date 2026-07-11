import React from 'react';
import { usePage } from '@inertiajs/react';

// Reflects the tenant's AI Assistant allowance anywhere on the tenant side —
// reads the shared `aeon` prop (set by the core Inertia middleware), so no
// per-page backend wiring. Renders nothing when AI isn't part of the plan.
//
// variant: 'card' (default, standalone surface) or 'row' (bare row for embedding
// inside an existing usage panel).
export default function AiUsageCard({ variant = 'card' }) {
  const aeon = usePage().props?.aeon;
  if (!aeon?.available || !aeon?.usage) return null;

  const u = aeon.usage;
  const model = u.model === 'pro' || u.model === 'all' ? 'Pro' : 'Flash';
  const unlimited = u.unlimited || u.limit <= 0;
  const pct = unlimited ? 0 : Math.min(100, Math.round((u.used / Math.max(1, u.limit)) * 100));
  const remaining = Math.max(0, u.remaining);
  const state = unlimited ? 'ok' : remaining <= 0 ? 'out' : pct >= 90 ? 'low' : 'ok';

  const body = (
    <>
      <div className="aiu-head">
        <span className="aiu-badge">✦ AI Assistant</span>
        <span className="aiu-model">{model} model</span>
      </div>
      {unlimited ? (
        <div className="aiu-line">Unlimited messages this month</div>
      ) : (
        <>
          <div className="aiu-line">
            <strong className={`aiu-rem aiu-${state}`}>{remaining}</strong> of {u.limit} messages left this month
          </div>
          <div className="aiu-track"><div className={`aiu-fill aiu-${state}`} style={{ width: `${pct}%` }} /></div>
        </>
      )}
    </>
  );

  if (variant === 'row') return <div className="aiu-row">{body}</div>;
  return <div className="aiu-card">{body}</div>;
}
