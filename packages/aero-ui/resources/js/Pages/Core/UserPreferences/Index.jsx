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
import App from '@/Pages/App.jsx';
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
          <Tabs
            tabs={[
              { value: 'notifications', label: 'Notifications' },
              { value: 'theme', label: 'Theme & Appearance' },
              { value: 'locale', label: 'Locale & Date' },
              { value: 'accessibility', label: 'Accessibility' },
            ]}
            value={currentTab}
            onChange={handleTabChange}
          >
            <VStack gap={4}>
              {renderTabContent()}
            </VStack>
          </Tabs>
        </CardContent>
      </Card>
    </DashboardLayout>
  );
}

UserPreferencesIndex.layout = page => <App title="User Preferences">{page}</App>;
