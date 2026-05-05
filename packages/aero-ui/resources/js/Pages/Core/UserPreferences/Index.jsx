import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
  DashboardLayout,
  Card,
  CardContent,
  Button,
  VStack,
  HStack,
  Text,
  Icon,
  Tabs,
  Tab,
  useToast,
} from '@aero/ui';
import App from '../../App.jsx';
import NotificationPreferences from './NotificationPreferences.jsx';
import ThemePreferences from './ThemePreferences.jsx';
import LocalePreferences from './LocalePreferences.jsx';
import AccessibilityPreferences from './AccessibilityPreferences.jsx';

export default function UserPreferencesIndex({ preferences, activeTab }) {
  const { toast } = useToast();
  const [currentTab, setCurrentTab] = useState(activeTab || 'notifications');

  const handleTabChange = (tab) => {
    setCurrentTab(tab);
    router.get(route('core.user-preferences.index'), { tab }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const renderTabContent = () => {
    switch (currentTab) {
      case 'notifications':
        return <NotificationPreferences preferences={preferences.notifications} />;
      case 'theme':
        return <ThemePreferences preferences={preferences.theme} />;
      case 'locale':
        return <LocalePreferences preferences={preferences.locale} />;
      case 'accessibility':
        return <AccessibilityPreferences preferences={preferences.accessibility} />;
      default:
        return <NotificationPreferences preferences={preferences.notifications} />;
    }
  };

  return (
    <DashboardLayout title="User Preferences">
      <Card>
        <CardContent>
          <Tabs value={currentTab} onValueChange={handleTabChange}>
            <Tabs.List>
              <Tabs.Trigger value="notifications">
                <HStack gap={2}>
                  <Icon name="bell" className="w-4 h-4" />
                  <Text>Notifications</Text>
                </HStack>
              </Tabs.Trigger>
              <Tabs.Trigger value="theme">
                <HStack gap={2}>
                  <Icon name="paint-brush" className="w-4 h-4" />
                  <Text>Theme & Appearance</Text>
                </HStack>
              </Tabs.Trigger>
              <Tabs.Trigger value="locale">
                <HStack gap={2}>
                  <Icon name="globe" className="w-4 h-4" />
                  <Text>Locale & Date</Text>
                </HStack>
              </Tabs.Trigger>
              <Tabs.Trigger value="accessibility">
                <HStack gap={2}>
                  <Icon name="accessibility" className="w-4 h-4" />
                  <Text>Accessibility</Text>
                </HStack>
              </Tabs.Trigger>
            </Tabs.List>
            <Tabs.Content value={currentTab}>
              {renderTabContent()}
            </Tabs.Content>
          </Tabs>
        </CardContent>
      </Card>
    </DashboardLayout>
  );
}

UserPreferencesIndex.layout = page => <App title="User Preferences">{page}</App>;
