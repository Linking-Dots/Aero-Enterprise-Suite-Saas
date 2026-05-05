import { useForm } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
  VStack,
  HStack,
  Text,
  Switch,
  Select,
  Label,
  Button,
  useToast,
} from '@aero/ui';

export default function AccessibilityPreferences({ preferences }) {
  const { toast } = useToast();
  const form = useForm({
    category: 'accessibility',
    preferences: {
      font_size: preferences.font_size ?? 'medium',
      high_contrast: preferences.high_contrast ?? false,
      reduced_motion: preferences.reduced_motion ?? false,
      screen_reader: preferences.screen_reader ?? false,
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('core.user-preferences.update'), {
      onSuccess: () => {
        toast({
          title: 'Success',
          description: 'Accessibility preferences updated successfully',
        });
      },
      onError: () => {
        toast({
          title: 'Error',
          description: 'Failed to update accessibility preferences',
          variant: 'destructive',
        });
      },
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <VStack gap={6}>
        <VStack gap={4}>
          <Text as="h3">Visual</Text>
          
          <VStack gap={2}>
            <Label htmlFor="font_size">Font Size</Label>
            <Select
              id="font_size"
              value={form.data.preferences.font_size}
              onValueChange={(value) => form.setData('preferences.font_size', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="small">Small</Select.Item>
                <Select.Item value="medium">Medium</Select.Item>
                <Select.Item value="large">Large</Select.Item>
                <Select.Item value="extra-large">Extra Large</Select.Item>
              </Select.Content>
            </Select>
          </VStack>

          <HStack gap={4} align="center" justify="space-between">
            <Label htmlFor="high_contrast">High Contrast Mode</Label>
            <Switch
              id="high_contrast"
              checked={form.data.preferences.high_contrast}
              onCheckedChange={(checked) => form.setData('preferences.high_contrast', checked)}
            />
          </HStack>
        </VStack>

        <VStack gap={4}>
          <Text as="h3">Motion</Text>
          
          <HStack gap={4} align="center" justify="space-between">
            <Label htmlFor="reduced_motion">Reduced Motion</Label>
            <Switch
              id="reduced_motion"
              checked={form.data.preferences.reduced_motion}
              onCheckedChange={(checked) => form.setData('preferences.reduced_motion', checked)}
            />
          </HStack>
        </VStack>

        <VStack gap={4}>
          <Text as="h3">Screen Reader</Text>
          
          <HStack gap={4} align="center" justify="space-between">
            <Label htmlFor="screen_reader">Screen Reader Optimizations</Label>
            <Switch
              id="screen_reader"
              checked={form.data.preferences.screen_reader}
              onCheckedChange={(checked) => form.setData('preferences.screen_reader', checked)}
            />
          </HStack>
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
