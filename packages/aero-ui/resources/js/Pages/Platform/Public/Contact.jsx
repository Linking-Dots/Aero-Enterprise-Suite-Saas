import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  Section, Container, PublicSectionHeader, Accordion,
  Card, VStack, HStack, Box, Text, Field, Input, Button, Alert, Icon,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';

const SUBJECT_OPTIONS = [
  { value: '',            label: 'Select a subject…' },
  { value: 'sales',       label: 'Sales Inquiry' },
  { value: 'support',     label: 'Technical Support' },
  { value: 'partnership', label: 'Partnership' },
  { value: 'billing',     label: 'Billing' },
  { value: 'other',       label: 'Other' },
];

const CONTACT_FAQ = [
  { question: 'How quickly will I get a response?', answer: 'We aim to respond within 1 business day for all inquiries. Sales and enterprise requests are typically responded to within 4 hours during business hours.' },
  { question: 'Do you offer phone support?', answer: 'Phone support is available on Business and Enterprise plans. All other plans receive email and chat support.' },
  { question: 'How do I report a security vulnerability?', answer: 'Please email security@aeos365.com with details. We follow responsible disclosure practices and commit to acknowledging reports within 5 business days.' },
  { question: 'Can I book a live demo?', answer: 'Yes — use the "Talk to sales" option and mention you would like a product walkthrough. Our team will schedule a personalized session within 24 hours.' },
];

// ── Contact Hero ─────────────────────────────────────────────────
function ContactHero() {
  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <VStack gap={5}>
          <p className="aeos-pub-label">Get in touch</p>
          <h1 className="aeos-pub-h1">
            We would love to hear{' '}
            <span className="aeos-pub-gradient-text">from you.</span>
          </h1>
          <p className="aeos-pub-lead aeos-content-base">
            Whether you are evaluating aeos365, need technical assistance, or want
            to explore a partnership — our team is ready to help. Choose the channel
            that fits your need best.
          </p>
          <HStack gap={3}>
            <a href="#contact-form" className="aeos-pub-btn-primary">Contact sales →</a>
            <a href="/docs" className="aeos-pub-btn-ghost">Browse docs</a>
          </HStack>
          <p className="aeos-pub-label">
            Typical response within 1 business day · No spam, ever · ISO 27001 certified
          </p>
        </VStack>
      </Container>
    </Section>
  );
}

// ── Contact Options ───────────────────────────────────────────────
function ContactOptions() {
  const options = [
    { icon: 'mail', title: 'Email us', body: 'For general inquiries, partnerships, and enterprise discussions.', cta: 'hello@aeos365.com', href: 'mailto:hello@aeos365.com', accent: 'cyan' },
    { icon: 'users', title: 'Talk to sales', body: 'Get a personalized walkthrough and pricing discussion with our team.', cta: 'Book a call', href: '#contact-form', accent: 'indigo' },
    { icon: 'document', title: 'Read docs', body: 'Guides, API references, and integration recipes for every module.', cta: 'Browse docs', href: '/docs', accent: 'amber' },
  ];

  return (
    <Section size="md">
      <Container>
        <HStack gap={4} wrap>
          {options.map((opt) => (
            <Box key={opt.title} grow>
              <Card>
                <VStack gap={3}>
                  <Icon name={opt.icon} size={24} className={`aeos-pub-accent-text--${opt.accent}`} />
                  <h3 className="aeos-pub-h3">{opt.title}</h3>
                  <Text tone="secondary">{opt.body}</Text>
                  <a href={opt.href} className="aeos-pub-btn-ghost aeos-pub-btn-sm">
                    {opt.cta}
                  </a>
                </VStack>
              </Card>
            </Box>
          ))}
        </HStack>
      </Container>
    </Section>
  );
}

// ── Contact Form ─────────────────────────────────────────────────
function ContactFormSection() {
  const [form, setForm] = useState({
    fullName: '', workEmail: '', company: '', phone: '', subject: '', message: '',
  });
  const [submitted, setSubmitted] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [formErrors, setFormErrors] = useState({});

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

  function handleChange(field) {
    return (e) => {
      setForm((prev) => ({ ...prev, [field]: e.target.value }));
      if (formErrors[field]) setFormErrors((prev) => ({ ...prev, [field]: undefined }));
    };
  }

  function handleSubmit(e) {
    e.preventDefault();
    const errs = validate();
    if (Object.keys(errs).length > 0) { setFormErrors(errs); return; }
    setSubmitting(true);
    router.post('/contact', form, {
      preserveState: true,
      onSuccess: () => { setSubmitted(true); setSubmitting(false); },
      onError: (errors) => { setFormErrors(errors); setSubmitting(false); },
      onFinish: () => setSubmitting(false),
    });
  }

  return (
    <Section size="lg" bg="surface" id="contact-form">
      <Container>
        <HStack gap={8} align="start">
          {/* Left copy */}
          <VStack gap={4} style={{ flex: '0 0 300px' }}>
            <p className="aeos-pub-label">Send a message</p>
            <h2 className="aeos-pub-h2">Tell us how we can help.</h2>
            <Text tone="secondary">
              Fill in the form and select the topic that fits best — our team will route
              your request to the right people and respond promptly.
            </Text>
            <VStack gap={2}>
              {[
                ['checkCircle', 'Responds within 1 business day', 'cyan'],
                ['shield', 'No unsolicited marketing', 'indigo'],
                ['lock', 'Data protected under ISO 27001', 'amber'],
              ].map(([icon, text, accent]) => (
                <HStack key={text} gap={2} align="center">
                  <Icon name={icon} size={16} className={`aeos-pub-accent-text--${accent}`} />
                  <Text tone="secondary">{text}</Text>
                </HStack>
              ))}
            </VStack>
          </VStack>

          {/* Form card */}
          <Box grow>
            <Card>
              {submitted ? (
                <VStack gap={4} align="center">
                  <Icon name="checkCircle" size={40} className="aeos-pub-accent-text--cyan" />
                  <h3 className="aeos-pub-h3">Message received!</h3>
                  <Text tone="secondary" style={{ textAlign: 'center', maxWidth: 380 }}>
                    Thanks for reaching out. A member of our team will get back to you
                    within 1 business day.
                  </Text>
                  <Button intent="soft" onClick={() => { setSubmitted(false); setForm({ fullName: '', workEmail: '', company: '', phone: '', subject: '', message: '' }); }}>
                    Send another message
                  </Button>
                </VStack>
              ) : (
                <form onSubmit={handleSubmit} noValidate>
                  <VStack gap={4}>
                    <HStack gap={3} wrap>
                      <Box grow>
                        <Field label="Full Name" htmlFor="cn-name" error={formErrors.fullName} required>
                          <Input id="cn-name" type="text" placeholder="Jane Smith" leftIcon="user" value={form.fullName} onChange={handleChange('fullName')} error={!!formErrors.fullName} />
                        </Field>
                      </Box>
                      <Box grow>
                        <Field label="Work Email" htmlFor="cn-email" error={formErrors.workEmail} required>
                          <Input id="cn-email" type="email" placeholder="jane@company.com" leftIcon="mail" value={form.workEmail} onChange={handleChange('workEmail')} error={!!formErrors.workEmail} />
                        </Field>
                      </Box>
                    </HStack>
                    <HStack gap={3} wrap>
                      <Box grow>
                        <Field label="Company" htmlFor="cn-company" error={formErrors.company} required>
                          <Input id="cn-company" type="text" placeholder="Acme Corp" value={form.company} onChange={handleChange('company')} error={!!formErrors.company} />
                        </Field>
                      </Box>
                      <Box grow>
                        <Field label="Phone" htmlFor="cn-phone" hint="Optional">
                          <Input id="cn-phone" type="tel" placeholder="+1 555 000 0000" leftIcon="phone" value={form.phone} onChange={handleChange('phone')} />
                        </Field>
                      </Box>
                    </HStack>
                    <Field label="Subject" htmlFor="cn-subject" error={formErrors.subject} required>
                      <select
                        id="cn-subject"
                        value={form.subject}
                        onChange={handleChange('subject')}
                        className={formErrors.subject ? 'aeos-input aeos-input--error' : 'aeos-input'}
                        style={{ width: '100%' }}
                      >
                        {SUBJECT_OPTIONS.map((opt) => (
                          <option key={opt.value} value={opt.value} disabled={opt.value === ''}>
                            {opt.label}
                          </option>
                        ))}
                      </select>
                    </Field>
                    <Field label="Message" htmlFor="cn-message" error={formErrors.message} required>
                      <textarea
                        id="cn-message"
                        rows={5}
                        placeholder="Describe what you need help with, or tell us about your project…"
                        value={form.message}
                        onChange={handleChange('message')}
                        className={formErrors.message ? 'aeos-input aeos-input--error' : 'aeos-input'}
                        style={{ width: '100%', resize: 'vertical', minHeight: '120px' }}
                      />
                    </Field>
                    <HStack gap={3} align="center" className="aeos-justify-between">
                      <Text tone="tertiary" size="xs">
                        By submitting you agree to our{' '}
                        <a href="/legal/privacy" className="aeos-pub-accent-text--cyan">Privacy Policy</a>.
                      </Text>
                      <Button intent="primary" type="submit" loading={submitting} rightIcon="arrowRight">
                        Send Message
                      </Button>
                    </HStack>
                  </VStack>
                </form>
              )}
            </Card>
          </Box>
        </HStack>
      </Container>
    </Section>
  );
}

// ── Contact FAQ ───────────────────────────────────────────────────
function ContactFAQ() {
  return (
    <Section size="lg">
      <Container>
        <PublicSectionHeader
          eyebrow="FAQ"
          title="Common questions about contacting us."
          align="center"
        />
        <VStack gap={0} className="aeos-content-extra-wide">
          <Accordion items={CONTACT_FAQ} />
        </VStack>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function Contact() {
  return (
    <>
      <ContactHero />
      <ContactOptions />
      <ContactFormSection />
      <ContactFAQ />
    </>
  );
}

Contact.layout = (page) => (
  <PublicLayout title="Contact — Get in Touch">{page}</PublicLayout>
);
