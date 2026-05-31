import { useForm } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
  VStack,
  HStack,
  Text,
  Switch,
  Select,
  TextField,
  Label,
  Button,
  useToast,
} from '@aero/ui';

export default function NotificationPreferences({ preferences }) {
  const { toast } = useToast();
  const form = useForm({
    category: 'notifications',
    preferences: {
      email_enabled: preferences.email_enabled ?? true,
      in_app_enabled: preferences.in_app_enabled ?? true,
      push_enabled: preferences.push_enabled ?? false,
      digest_frequency: preferences.digest_frequency ?? 'immediate',
      dnd_enabled: preferences.dnd_enabled ?? false,
      dnd_start_time: preferences.dnd_start_time ?? '',
      dnd_end_time: preferences.dnd_end_time ?? '',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('core.user-preferences.update'), {
      onSuccess: () => {
        toast({
          title: 'Success',
          description: 'Notification preferences updated successfully',
        });
      },
      onError: () => {
        toast({
          title: 'Error',
          description: 'Failed to update notification preferences',
          variant: 'destructive',
        });
      },
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <VStack gap={6}>
        <VStack gap={4}>
          <Text as="h3">Notification Channels</Text>
          
          <HStack gap={4} align="center" justify="space-between">
            <Label htmlFor="email_enabled">Email Notifications</Label>
            <Switch
              id="email_enabled"
              checked={form.data.preferences.email_enabled}
              onCheckedChange={(checked) => form.setData('preferences.email_enabled', checked)}
            />
          </HStack>

          <HStack gap={4} align="center" justify="space-between">
            <Label htmlFor="in_app_enabled">In-App Notifications</Label>
            <Switch
              id="in_app_enabled"
              checked={form.data.preferences.in_app_enabled}
              onCheckedChange={(checked) => form.setData('preferences.in_app_enabled', checked)}
            />
          </HStack>

          <HStack gap={4} align="center" justify="space-between">
            <Label htmlFor="push_enabled">Push Notifications</Label>
            <Switch
              id="push_enabled"
              checked={form.data.preferences.push_enabled}
              onCheckedChange={(checked) => form.setData('preferences.push_enabled', checked)}
            />
          </HStack>
        </VStack>

        <VStack gap={4}>
          <Text as="h3">Digest Settings</Text>
          
          <VStack gap={2}>
            <Label htmlFor="digest_frequency">Digest Frequency</Label>
            <Select
              id="digest_frequency"
              value={form.data.preferences.digest_frequency}
              onValueChange={(value) => form.setData('preferences.digest_frequency', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="immediate">Immediate</Select.Item>
                <Select.Item value="daily">Daily Digest</Select.Item>
                <Select.Item value="weekly">Weekly Digest</Select.Item>
              </Select.Content>
            </Select>
          </VStack>
        </VStack>

        <VStack gap={4}>
          <Text as="h3">Do-Not-Disturb</Text>
          
          <HStack gap={4} align="center" justify="space-between">
            <Label htmlFor="dnd_enabled">Enable DND</Label>
            <Switch
              id="dnd_enabled"
              checked={form.data.preferences.dnd_enabled}
              onCheckedChange={(checked) => form.setData('preferences.dnd_enabled', checked)}
            />
          </HStack>

          {form.data.preferences.dnd_enabled && (
            <HStack gap={4}>
              <VStack gap={2} className="aeos-flex-1">
                <Label htmlFor="dnd_start_time">Start Time</Label>
                <TextField
                  id="dnd_start_time"
                  type="time"
                  value={form.data.preferences.dnd_start_time}
                  onChange={(e) => form.setData('preferences.dnd_start_time', e.target.value)}
                />
              </VStack>
              <VStack gap={2} className="aeos-flex-1">
                <Label htmlFor="dnd_end_time">End Time</Label>
                <TextField
                  id="dnd_end_time"
                  type="time"
                  value={form.data.preferences.dnd_end_time}
                  onChange={(e) => form.setData('preferences.dnd_end_time', e.target.value)}
                />
              </VStack>
            </HStack>
          )}
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
