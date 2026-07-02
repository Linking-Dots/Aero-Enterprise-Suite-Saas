import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Container, Accordion, Field, Input, Button, Icon } from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import { Reveal } from './home/primitives.jsx';

const SUBJECTS = [
  { value: '', label: 'Select a subject…' },
  { value: 'sales', label: 'Sales inquiry' },
  { value: 'support', label: 'Technical support' },
  { value: 'partnership', label: 'Partnership' },
  { value: 'billing', label: 'Billing' },
  { value: 'other', label: 'Other' },
];
const OPTIONS = [
  { icon: 'mail', title: 'Email us', body: 'For general inquiries, partnerships, and enterprise discussions.', cta: 'hello@aeos365.com', href: 'mailto:hello@aeos365.com', accent: 'cyan' },
  { icon: 'users', title: 'Talk to sales', body: 'Get a personalized walkthrough and pricing discussion with our team.', cta: 'Book a call', href: '#contact-form', accent: 'indigo' },
  { icon: 'document', title: 'Read the docs', body: 'Guides, API references, and integration recipes for every module.', cta: 'Browse docs', href: '/docs', accent: 'amber' },
];
const FAQ = [
  { question: 'How quickly will I get a response?', answer: 'We aim to respond within 1 business day for all inquiries. Sales and enterprise requests are typically answered within 4 hours during business hours.' },
  { question: 'Do you offer phone support?', answer: 'Phone support is available on Business and Enterprise plans. All other plans receive email and chat support.' },
  { question: 'How do I report a security vulnerability?', answer: 'Email security@aeos365.com with details. We follow responsible disclosure and acknowledge reports within 5 business days.' },
  { question: 'Can I book a live demo?', answer: 'Yes — use the "Talk to sales" option and mention you would like a product walkthrough. We schedule a personalized session within 24 hours.' },
];

function Hero() {
  return (
    <section className="lv-hero lv-hero--page lv-hero--center">
      <div className="lv-hero-bg" aria-hidden="true">
        <div className="lv-hero-aura lv-hero-aura--1" /><div className="lv-hero-aura lv-hero-aura--2" /><div className="lv-hero-grid" />
      </div>
      <Container>
        <div className="lv-hero-centered">
          <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Get in touch</span></Reveal>
          <Reveal delay={0.06}><h1 className="lv-h1 lv-h1--page">We'd love to hear{' '}<span className="lv-h1-grad">from you.</span></h1></Reveal>
          <Reveal delay={0.12}><p className="lv-lead lv-hero-centered-lead">Whether you're evaluating aeos365, need technical assistance, or want to explore a partnership — our team is ready to help. Pick the channel that fits best.</p></Reveal>
          <Reveal delay={0.18}><p className="lv-eyebrow" style={{ opacity: .8 }}>Typical response within 1 business day · No spam, ever · ISO 27001 certified</p></Reveal>
        </div>
      </Container>
      <div className="lv-hero-fade" aria-hidden="true" />
    </section>
  );
}

function Options() {
  return (
    <section className="lv-feat" style={{ paddingBottom: '2rem' }}>
      <Container>
        <div className="lv-arch-cards lv-arch-cards--3">
          {OPTIONS.map((o, i) => (
            <Reveal key={o.title} delay={i * 0.08}>
              <div className={`lv-arch-card lv-arch-card--${o.accent}`}>
                <span className={`lv-arch-icon lv-icon--${o.accent}`}><Icon name={o.icon} size={22} /></span>
                <h3 className="lv-arch-card-title">{o.title}</h3>
                <p className="lv-arch-card-body">{o.body}</p>
                <a href={o.href} className={`lv-accent--${o.accent}`} style={{ fontSize: '.85rem', fontWeight: 600, textDecoration: 'none', marginTop: 'auto' }}>{o.cta} →</a>
              </div>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

function FormSection() {
  const [form, setForm] = useState({ fullName: '', workEmail: '', company: '', phone: '', subject: '', message: '' });
  const [submitted, setSubmitted] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [errs, setErrs] = useState({});

  function validate() {
    const e = {};
    if (!form.fullName.trim()) e.fullName = 'Full name is required.';
    if (!form.workEmail.trim()) e.workEmail = 'Work email is required.';
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.workEmail)) e.workEmail = 'Enter a valid email address.';
    if (!form.company.trim()) e.company = 'Company name is required.';
    if (!form.subject) e.subject = 'Please select a subject.';
    if (!form.message.trim()) e.message = 'Please write a message.';
    else if (form.message.trim().length < 20) e.message = 'Message must be at least 20 characters.';
    return e;
  }
  const change = (f) => (e) => { setForm((p) => ({ ...p, [f]: e.target.value })); if (errs[f]) setErrs((p) => ({ ...p, [f]: undefined })); };
  function submit(e) {
    e.preventDefault();
    const v = validate(); if (Object.keys(v).length) { setErrs(v); return; }
    setSubmitting(true);
    router.post('/contact', form, { preserveState: true, onSuccess: () => { setSubmitted(true); setSubmitting(false); }, onError: (x) => { setErrs(x); setSubmitting(false); }, onFinish: () => setSubmitting(false) });
  }

  return (
    <section className="lv-split-sec" id="contact-form">
      <Container>
        <div className="lv-split">
          <Reveal className="lv-split-copy">
            <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Send a message</span>
            <h2 className="lv-h2">Tell us how we{' '}<span className="lv-h2-grad">can help.</span></h2>
            <p className="lv-lead">Fill in the form and select the topic that fits — our team routes your request to the right people and responds promptly.</p>
            <ul className="lv-check-list">
              <li><span className="lv-check lv-accent--cyan"><Icon name="checkCircle" size={14} /></span>Responds within 1 business day</li>
              <li><span className="lv-check lv-accent--indigo"><Icon name="shield" size={14} /></span>No unsolicited marketing</li>
              <li><span className="lv-check lv-accent--amber"><Icon name="lock" size={14} /></span>Data protected under ISO 27001</li>
            </ul>
          </Reveal>
          <Reveal delay={0.1} className="lv-form-card">
            {submitted ? (
              <div className="lv-form-done">
                <Icon name="checkCircle" size={40} className="lv-accent--cyan" />
                <h3 className="lv-arch-card-title">Message received!</h3>
                <p className="lv-arch-card-body">Thanks for reaching out — a member of our team will get back to you within 1 business day.</p>
                <Button intent="soft" onClick={() => { setSubmitted(false); setForm({ fullName: '', workEmail: '', company: '', phone: '', subject: '', message: '' }); }}>Send another message</Button>
              </div>
            ) : (
              <form onSubmit={submit} noValidate className="lv-form">
                <div className="lv-form-row">
                  <Field label="Full name" htmlFor="cn-name" error={errs.fullName} required><Input id="cn-name" placeholder="Jane Smith" leftIcon="user" value={form.fullName} onChange={change('fullName')} error={!!errs.fullName} /></Field>
                  <Field label="Work email" htmlFor="cn-email" error={errs.workEmail} required><Input id="cn-email" type="email" placeholder="jane@company.com" leftIcon="mail" value={form.workEmail} onChange={change('workEmail')} error={!!errs.workEmail} /></Field>
                </div>
                <div className="lv-form-row">
                  <Field label="Company" htmlFor="cn-company" error={errs.company} required><Input id="cn-company" placeholder="Acme Corp" value={form.company} onChange={change('company')} error={!!errs.company} /></Field>
                  <Field label="Phone" htmlFor="cn-phone" hint="Optional"><Input id="cn-phone" type="tel" placeholder="+1 555 000 0000" leftIcon="phone" value={form.phone} onChange={change('phone')} /></Field>
                </div>
                <Field label="Subject" htmlFor="cn-subject" error={errs.subject} required>
                  <select id="cn-subject" value={form.subject} onChange={change('subject')} className={`lv-cta-input ${errs.subject ? 'aeos-input--error' : ''}`} style={{ width: '100%' }}>
                    {SUBJECTS.map((o) => <option key={o.value} value={o.value} disabled={o.value === ''}>{o.label}</option>)}
                  </select>
                </Field>
                <Field label="Message" htmlFor="cn-message" error={errs.message} required>
                  <textarea id="cn-message" rows={5} placeholder="Describe what you need help with, or tell us about your project…" value={form.message} onChange={change('message')} className={`lv-cta-input ${errs.message ? 'aeos-input--error' : ''}`} style={{ width: '100%', resize: 'vertical', minHeight: 120 }} />
                </Field>
                <div className="lv-form-foot">
                  <span className="lv-form-note">By submitting you agree to our <a href="/legal/privacy" className="lv-accent--cyan">Privacy Policy</a>.</span>
                  <Button intent="primary" type="submit" loading={submitting} rightIcon="arrowRight">Send message</Button>
                </div>
              </form>
            )}
          </Reveal>
        </div>
      </Container>
    </section>
  );
}

function FaqSec() {
  return (
    <section className="lv-faq">
      <Container>
        <Reveal className="lv-feat-header">
          <span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> FAQ</span>
          <h2 className="lv-h2">Common questions about contacting us.</h2>
        </Reveal>
        <div className="lv-faq-inner"><Accordion items={FAQ} /></div>
      </Container>
    </section>
  );
}

export default function Contact() {
  return (<><Hero /><Options /><FormSection /><FaqSec /></>);
}

Contact.layout = (page) => (
  <PublicLayout title="Contact — Get in Touch">{page}</PublicLayout>
);
