import { useForm } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
  VStack,
  HStack,
  Text,
  Select,
  TextField,
  Label,
  Button,
  useToast,
} from '@aero/ui';

export default function LocalePreferences({ preferences }) {
  const { toast } = useToast();
  const form = useForm({
    category: 'locale',
    preferences: {
      language: preferences.language ?? 'en',
      timezone: preferences.timezone ?? 'UTC',
      date_format: preferences.date_format ?? 'Y-m-d',
      time_format: preferences.time_format ?? 'H:i',
      currency: preferences.currency ?? 'USD',
      number_format: preferences.number_format ?? '1,234.56',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('core.user-preferences.update'), {
      onSuccess: () => {
        toast({
          title: 'Success',
          description: 'Locale preferences updated successfully',
        });
      },
      onError: () => {
        toast({
          title: 'Error',
          description: 'Failed to update locale preferences',
          variant: 'destructive',
        });
      },
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <VStack gap={6}>
        <VStack gap={4}>
          <Text as="h3">Language & Region</Text>
          
          <VStack gap={2}>
            <Label htmlFor="language">Language</Label>
            <Select
              id="language"
              value={form.data.preferences.language}
              onValueChange={(value) => form.setData('preferences.language', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="en">English</Select.Item>
                <Select.Item value="es">Spanish</Select.Item>
                <Select.Item value="fr">French</Select.Item>
                <Select.Item value="de">German</Select.Item>
                <Select.Item value="it">Italian</Select.Item>
                <Select.Item value="pt">Portuguese</Select.Item>
                <Select.Item value="zh">Chinese</Select.Item>
                <Select.Item value="ja">Japanese</Select.Item>
                <Select.Item value="ko">Korean</Select.Item>
                <Select.Item value="ar">Arabic</Select.Item>
              </Select.Content>
            </Select>
          </VStack>

          <VStack gap={2}>
            <Label htmlFor="timezone">Timezone</Label>
            <Select
              id="timezone"
              value={form.data.preferences.timezone}
              onValueChange={(value) => form.setData('preferences.timezone', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="UTC">UTC</Select.Item>
                <Select.Item value="America/New_York">Eastern Time (ET)</Select.Item>
                <Select.Item value="America/Chicago">Central Time (CT)</Select.Item>
                <Select.Item value="America/Denver">Mountain Time (MT)</Select.Item>
                <Select.Item value="America/Los_Angeles">Pacific Time (PT)</Select.Item>
                <Select.Item value="Europe/London">London (GMT)</Select.Item>
                <Select.Item value="Europe/Paris">Paris (CET)</Select.Item>
                <Select.Item value="Europe/Berlin">Berlin (CET)</Select.Item>
                <Select.Item value="Asia/Tokyo">Tokyo (JST)</Select.Item>
                <Select.Item value="Asia/Shanghai">Shanghai (CST)</Select.Item>
                <Select.Item value="Australia/Sydney">Sydney (AEST)</Select.Item>
              </Select.Content>
            </Select>
          </VStack>
        </VStack>

        <VStack gap={4}>
          <Text as="h3">Date & Time Format</Text>
          
          <VStack gap={2}>
            <Label htmlFor="date_format">Date Format</Label>
            <Select
              id="date_format"
              value={form.data.preferences.date_format}
              onValueChange={(value) => form.setData('preferences.date_format', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="Y-m-d">YYYY-MM-DD (2024-05-05)</Select.Item>
                <Select.Item value="m/d/Y">MM/DD/YYYY (05/05/2024)</Select.Item>
                <Select.Item value="d/m/Y">DD/MM/YYYY (05/05/2024)</Select.Item>
                <Select.Item value="F j, Y">Month D, YYYY (May 5, 2024)</Select.Item>
                <Select.Item value="j F Y">D Month YYYY (5 May 2024)</Select.Item>
              </Select.Content>
            </Select>
          </VStack>

          <VStack gap={2}>
            <Label htmlFor="time_format">Time Format</Label>
            <Select
              id="time_format"
              value={form.data.preferences.time_format}
              onValueChange={(value) => form.setData('preferences.time_format', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="H:i">24-hour (14:30)</Select.Item>
                <Select.Item value="g:i A">12-hour (2:30 PM)</Select.Item>
              </Select.Content>
            </Select>
          </VStack>
        </VStack>

        <VStack gap={4}>
          <Text as="h3">Currency & Numbers</Text>
          
          <VStack gap={2}>
            <Label htmlFor="currency">Currency</Label>
            <Select
              id="currency"
              value={form.data.preferences.currency}
              onValueChange={(value) => form.setData('preferences.currency', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="USD">USD - US Dollar</Select.Item>
                <Select.Item value="EUR">EUR - Euro</Select.Item>
                <Select.Item value="GBP">GBP - British Pound</Select.Item>
                <Select.Item value="JPY">JPY - Japanese Yen</Select.Item>
                <Select.Item value="CNY">CNY - Chinese Yuan</Select.Item>
                <Select.Item value="CAD">CAD - Canadian Dollar</Select.Item>
                <Select.Item value="AUD">AUD - Australian Dollar</Select.Item>
                <Select.Item value="INR">INR - Indian Rupee</Select.Item>
              </Select.Content>
            </Select>
          </VStack>

          <VStack gap={2}>
            <Label htmlFor="number_format">Number Format</Label>
            <Select
              id="number_format"
              value={form.data.preferences.number_format}
              onValueChange={(value) => form.setData('preferences.number_format', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="1,234.56">1,234.56 (US/UK)</Select.Item>
                <Select.Item value="1.234,56">1.234,56 (European)</Select.Item>
                <Select.Item value="1 234,56">1 234,56 (Space)</Select.Item>
                <Select.Item value="1234.56">1234.56 (No separators)</Select.Item>
              </Select.Content>
            </Select>
          </VStack>
        </VStack>

        <HStack gap={4} justify="end">
          <Button type="submit" intent="primary" disabled={form.processing}>
            {form.processing ? 'Saving...' : 'Save Changes'}
          </Button>
        </HStack>
      </VStack>
    </form>
  );
}
