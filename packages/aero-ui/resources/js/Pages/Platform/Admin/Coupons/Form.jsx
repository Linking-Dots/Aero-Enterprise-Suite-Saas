import { router, useForm, usePage } from '@inertiajs/react';
import {
  IndexPageLayout,
  Button,
  HStack,
  VStack,
  Field,
  Input,
  Select,
  Alert,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TYPE_OPTIONS = [
  { value: 'percent', label: 'Percent (%)' },
  { value: 'fixed',   label: 'Fixed Amount' },
];

const DURATION_OPTIONS = [
  { value: 'once',      label: 'Once' },
  { value: 'repeating', label: 'Repeating' },
  { value: 'forever',   label: 'Forever' },
];

export default function CouponForm({ coupon }) {
  const toast    = useToast();
  const isEdit   = !!coupon;
  const { errors: pageErrors } = usePage().props;

  const form = useForm({
    code:            coupon?.code            ?? '',
    name:            coupon?.name            ?? '',
    type:            coupon?.type            ?? 'percent',
    value:           coupon?.value           != null ? String(coupon.value) : '',
    currency:        coupon?.currency        ?? 'USD',
    duration:        coupon?.duration        ?? 'once',
    duration_months: coupon?.duration_months != null ? String(coupon.duration_months) : '',
    max_redemptions: coupon?.max_redemptions != null ? String(coupon.max_redemptions) : '',
    expires_at:      coupon?.expires_at      ?? '',
    applies_to:      coupon?.applies_to      ?? '',
  });

  const errors = { ...form.errors, ...pageErrors };

  function handleSubmit(e) {
    e.preventDefault();
    if (isEdit) {
      form.put(route('platform.admin.coupons.update', coupon.id), {
        onSuccess: () => {
          toast.success('Coupon updated.');
          router.get(route('platform.admin.coupons.index'));
        },
        onError: () => toast.error('Failed to update coupon.'),
      });
    } else {
      form.post(route('platform.admin.coupons.store'), {
        onSuccess: () => {
          toast.success('Coupon created.');
          router.get(route('platform.admin.coupons.index'));
        },
        onError: () => toast.error('Failed to create coupon.'),
      });
    }
  }

  return (
    <IndexPageLayout
      title={isEdit ? 'Edit Coupon' : 'New Coupon'}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Coupons', href: route('platform.admin.coupons.index') },
        { label: isEdit ? 'Edit' : 'New' },
      ]}
      description={isEdit ? `Editing coupon: ${coupon.code}` : 'Create a new discount coupon.'}
      actions={
        <Button
          intent="ghost"
          leftIcon="arrowLeft"
          onClick={() => router.get(route('platform.admin.coupons.index'))}
        >
          Back to Coupons
        </Button>
      }
    >
      <VStack gap={6}>
        {Object.keys(errors).length > 0 && (
          <Alert intent="danger" title="Please fix the errors below." />
        )}

        <VStack gap={4}>
          <Field label="Coupon Code" htmlFor="cp_code" error={errors.code} required>
            <Input
              id="cp_code"
              value={form.data.code}
              onChange={e => form.setData('code', e.target.value)}
              placeholder="SUMMER25"
              error={errors.code}
            />
          </Field>

          <Field label="Display Name" htmlFor="cp_name" error={errors.name} required>
            <Input
              id="cp_name"
              value={form.data.name}
              onChange={e => form.setData('name', e.target.value)}
              placeholder="Summer Sale 25%"
              error={errors.name}
            />
          </Field>

          <Field label="Discount Type" htmlFor="cp_type" error={errors.type} required>
            <Select
              id="cp_type"
              value={form.data.type}
              onChange={e => form.setData('type', e.target.value)}
              options={TYPE_OPTIONS}
            />
          </Field>

          <Field label="Value" htmlFor="cp_value" error={errors.value} required hint={form.data.type === 'percent' ? 'Enter a number between 1 and 100.' : 'Enter the fixed discount amount.'}>
            <Input
              id="cp_value"
              type="number"
              value={form.data.value}
              onChange={e => form.setData('value', e.target.value)}
              placeholder={form.data.type === 'percent' ? '25' : '10.00'}
              error={errors.value}
            />
          </Field>

          {form.data.type === 'fixed' && (
            <Field label="Currency" htmlFor="cp_currency" error={errors.currency}>
              <Input
                id="cp_currency"
                value={form.data.currency}
                onChange={e => form.setData('currency', e.target.value)}
                placeholder="USD"
                error={errors.currency}
              />
            </Field>
          )}

          <Field label="Duration" htmlFor="cp_duration" error={errors.duration} required>
            <Select
              id="cp_duration"
              value={form.data.duration}
              onChange={e => form.setData('duration', e.target.value)}
              options={DURATION_OPTIONS}
            />
          </Field>

          {form.data.duration === 'repeating' && (
            <Field label="Duration Months" htmlFor="cp_dur_months" error={errors.duration_months} required hint="How many months the discount applies.">
              <Input
                id="cp_dur_months"
                type="number"
                value={form.data.duration_months}
                onChange={e => form.setData('duration_months', e.target.value)}
                placeholder="3"
                error={errors.duration_months}
              />
            </Field>
          )}

          <Field label="Max Redemptions" htmlFor="cp_max" error={errors.max_redemptions} hint="Leave blank for unlimited.">
            <Input
              id="cp_max"
              type="number"
              value={form.data.max_redemptions}
              onChange={e => form.setData('max_redemptions', e.target.value)}
              placeholder="Unlimited"
              error={errors.max_redemptions}
            />
          </Field>

          <Field label="Expires At" htmlFor="cp_expires" error={errors.expires_at} hint="Leave blank for no expiry.">
            <Input
              id="cp_expires"
              type="datetime-local"
              value={form.data.expires_at}
              onChange={e => form.setData('expires_at', e.target.value)}
              error={errors.expires_at}
            />
          </Field>

          <Field
            label="Applies To"
            htmlFor="cp_applies"
            error={errors.applies_to}
            hint="Restrict coupon to a specific plan code or product code. Leave blank to allow any."
          >
            <Input
              id="cp_applies"
              value={form.data.applies_to}
              onChange={e => form.setData('applies_to', e.target.value)}
              placeholder="e.g. plan-pro or addon-storage"
              error={errors.applies_to}
            />
          </Field>
        </VStack>

        <HStack gap={2}>
          <Button
            intent="primary"
            type="submit"
            loading={form.processing}
            disabled={form.processing}
            onClick={handleSubmit}
          >
            {isEdit ? 'Update Coupon' : 'Create Coupon'}
          </Button>
          <Button
            intent="ghost"
            onClick={() => router.get(route('platform.admin.coupons.index'))}
          >
            Cancel
          </Button>
        </HStack>
      </VStack>
    </IndexPageLayout>
  );
}

CouponForm.layout = page => <App title={page.props.coupon ? 'Edit Coupon' : 'New Coupon'}>{page}</App>;
