import React, { useMemo, useState } from 'react';
import { submitAeonForm } from './aeonClient.js';

// A generative-UI operation form: Aeon builds it by introspecting the app's own
// route + validation, and it posts to the REAL endpoint on submit (server-side
// validation, permissions and audit all run). Fully theme-reactive.
export default function AeonForm({ block, onAction }) {
  const initial = useMemo(() => {
    const v = {};
    (block.fields || []).forEach((f) => {
      v[f.name] = f.value ?? (f.type === 'toggle' ? false : '');
    });
    return v;
  }, [block]);

  const [values, setValues] = useState(initial);
  const [errors, setErrors] = useState({});
  const [state, setState] = useState('idle'); // idle | sending | done
  const [banner, setBanner] = useState('');

  const set = (name, val) => setValues((p) => ({ ...p, [name]: val }));

  const submit = async (e) => {
    e.preventDefault();
    if (state === 'sending') return;
    setState('sending');
    setErrors({});
    setBanner('');

    const res = await submitAeonForm({ action: block.action, method: block.method, values });
    if (res.ok) {
      setState('done');
      return;
    }
    setState('idle');
    setErrors(res.errors || {});
    if (res.errors && res.errors._) setBanner(res.errors._);
    else if (Object.keys(res.errors || {}).length) setBanner('Please fix the highlighted fields and try again.');
    else setBanner("Couldn't submit — please try again.");
  };

  if (state === 'done') {
    return (
      <div className="aeon-form is-done">
        <div className="aeon-form-done">
          <span className="aeon-form-tick">✓</span>
          <div>
            <div className="aeon-form-done-t">{doneTitle(block)}</div>
            <div className="aeon-form-done-d">Saved through {block.entity ? `the ${block.entity} form` : 'the app'} — validation & audit applied.</div>
          </div>
        </div>
        <div className="aeon-form-row">
          <button type="button" className="aeon-abtn is-go" onClick={() => onAction?.({ kind: 'navigate', block: { route: collectionOf(block) } })}>
            View {plural(block.entity)} →
          </button>
        </div>
      </div>
    );
  }

  return (
    <form className="aeon-form" onSubmit={submit}>
      <div className="aeon-form-head">
        <span className="aeon-form-badge">{verbOf(block)}</span>
        <span className="aeon-form-title">{block.title}</span>
      </div>

      {banner ? <div className="aeon-form-banner">{banner}</div> : null}

      <div className="aeon-form-grid">
        {(block.fields || []).map((f) => (
          <Field key={f.name} field={f} value={values[f.name]} error={firstError(errors[f.name])} onChange={(v) => set(f.name, v)} />
        ))}
      </div>

      <div className="aeon-form-foot">
        <span className="aeon-form-note">{block.note}</span>
        <button type="submit" className="aeon-abtn is-go" disabled={state === 'sending'}>
          {state === 'sending' ? 'Submitting…' : (block.submit_label || 'Submit')}
        </button>
      </div>
    </form>
  );
}

function Field({ field, value, error, onChange }) {
  const wide = field.type === 'textarea';
  return (
    <label className={`aeon-fld ${wide ? 'is-wide' : ''} ${error ? 'has-err' : ''}`}>
      <span className="aeon-fld-l">{field.label}{field.required ? <span className="aeon-fld-req">*</span> : null}</span>
      <Control field={field} value={value} onChange={onChange} />
      {error ? <span className="aeon-fld-err">{error}</span> : null}
    </label>
  );
}

function Control({ field, value, onChange }) {
  const common = { className: 'aeon-fld-c', required: field.required };
  switch (field.type) {
    case 'select':
      return (
        <select {...common} value={value ?? ''} onChange={(e) => onChange(e.target.value)}>
          <option value="">Select…</option>
          {(field.options || []).map((o) => (
            <option key={String(o.value)} value={o.value}>{o.label}</option>
          ))}
        </select>
      );
    case 'textarea':
      return <textarea {...common} rows={3} maxLength={field.maxlength || undefined} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />;
    case 'toggle':
      return (
        <span className="aeon-fld-toggle">
          <input type="checkbox" checked={!!value} onChange={(e) => onChange(e.target.checked)} />
          <span className="aeon-fld-track" aria-hidden="true"><span className="aeon-fld-knob" /></span>
          <span className="aeon-fld-toggle-t">{value ? 'Yes' : 'No'}</span>
        </span>
      );
    case 'date':
      return <input type="date" {...common} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />;
    case 'number':
      return <input type="number" {...common} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />;
    case 'email':
      return <input type="email" {...common} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />;
    default:
      return <input type="text" {...common} maxLength={field.maxlength || undefined} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />;
  }
}

function firstError(err) {
  if (!err) return '';
  return Array.isArray(err) ? err[0] : String(err);
}
function verbOf(block) {
  return ({ create: 'Create', update: 'Update', delete: 'Delete', action: 'Action' })[block.kind] || 'Form';
}
function doneTitle(block) {
  return ({ create: 'Created', update: 'Saved', delete: 'Deleted', action: 'Done' })[block.kind] || 'Done';
}
function plural(entity) {
  if (!entity) return 'records';
  return /s$/i.test(entity) ? entity.toLowerCase() : `${entity.toLowerCase()}s`;
}
// The list URL for a create form is its own action path (GET); for others fall back to it too.
function collectionOf(block) {
  return block.action || '/';
}
