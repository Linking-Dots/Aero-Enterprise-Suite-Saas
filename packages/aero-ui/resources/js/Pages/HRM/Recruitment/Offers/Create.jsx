import { router, useForm, usePage } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Button, Textarea,
} from '@aero/ui';

export default function OffersCreate({ templates, application_id: propApplicationId }) {
  const { ziggy } = usePage().props;
  const queryApplicationId = ziggy?.query?.application_id ?? null;
  const resolvedApplicationId = propApplicationId ?? queryApplicationId ?? '';

  const { data, setData, post, processing, errors } = useForm({
    application_id:    resolvedApplicationId,
    offered_salary:    '',
    salary_currency:   'USD',
    joining_date:      '',
    offer_valid_until: '',
    notes:             '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.recruitment.offers.store'));
  }

  return (
    <FormPageLayout
      title="Send Offer"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Recruitment' },
        { label: 'Offers' },
        { label: 'New' },
      ]}
    >
      <form onSubmit={submit}>
        <VStack gap={5}>
          {resolvedApplicationId && (
            <input type="hidden" name="application_id" value={resolvedApplicationId} />
          )}

          <HStack gap={4} wrap>
            <Field label="Offered Salary" htmlFor="offered_salary" error={errors.offered_salary} required>
              <Input
                id="offered_salary"
                type="number"
                value={data.offered_salary}
                onChange={e => setData('offered_salary', e.target.value)}
                placeholder="50000"
                error={!!errors.offered_salary}
              />
            </Field>
            <Field label="Currency" htmlFor="salary_currency" error={errors.salary_currency} required>
              <Input
                id="salary_currency"
                value={data.salary_currency}
                onChange={e => setData('salary_currency', e.target.value)}
                placeholder="USD"
                error={!!errors.salary_currency}
              />
            </Field>
          </HStack>

          <HStack gap={4} wrap>
            <Field label="Joining Date" htmlFor="joining_date" error={errors.joining_date} required>
              <Input
                id="joining_date"
                type="date"
                value={data.joining_date}
                onChange={e => setData('joining_date', e.target.value)}
                error={!!errors.joining_date}
              />
            </Field>
            <Field label="Offer Valid Until" htmlFor="offer_valid_until" error={errors.offer_valid_until} required>
              <Input
                id="offer_valid_until"
                type="date"
                value={data.offer_valid_until}
                onChange={e => setData('offer_valid_until', e.target.value)}
                error={!!errors.offer_valid_until}
              />
            </Field>
          </HStack>

          <Field label="Notes" htmlFor="notes" error={errors.notes}>
            <Textarea
              id="notes"
              value={data.notes}
              onChange={e => setData('notes', e.target.value)}
              placeholder="Additional offer details or conditions (optional)..."
              error={!!errors.notes}
            />
          </Field>

          <HStack gap={3}>
            <Button type="submit" intent="primary" loading={processing}>
              Send Offer
            </Button>
            <Button
              type="button"
              intent="ghost"
              onClick={() => router.get(route('hrm.recruitment.jobs.index'))}
            >
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

OffersCreate.layout = page => <App title="Send Offer">{page}</App>;
