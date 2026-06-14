import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  DashboardLayout,
  Card,
  CardContent,
  VStack,
  HStack,
  Text,
  Icon,
  Button,
  Badge,
  TextField,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function ActivityIndex({ title, activities, stats, filters }) {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedModule, setSelectedModule] = useState(filters.module || '');
  const [selectedAction, setSelectedAction] = useState(filters.action || '');

  const handleFilter = () => {
    router.get(route('core.activity.index'), {
      module: selectedModule || undefined,
      action: selectedAction || undefined,
    });
  };

  const handleExport = () => {
    window.open(route('core.activity.export'), '_blank');
  };

  const getActionColor = (action) => {
    switch (action) {
      case 'created': return 'success';
      case 'updated': return 'info';
      case 'deleted': return 'danger';
      case 'login': return 'success';
      case 'logout': return 'neutral';
      default: return 'secondary';
    }
  };

  const getActionIcon = (action) => {
    switch (action) {
      case 'created': return 'plus-circle';
      case 'updated': return 'pencil';
      case 'deleted': return 'trash';
      case 'login': return 'arrow-right-on-rectangle';
      case 'logout': return 'arrow-left-on-rectangle';
      case 'export': return 'download';
      case 'import': return 'upload';
      default: return 'document';
    }
  };

  return (
    <DashboardLayout
      title={title}
      actions={
        <Button onClick={handleExport}>
          <Icon name="download" className="w-4 h-4" />
          Export
        </Button>
      }
    >
      <VStack gap={4}>
        {/* Stats Cards */}
        <HStack gap={4}>
          <Card className="aeos-flex-1">
            <CardContent>
              <VStack gap={2}>
                <Text tone="secondary" size="sm">Total Activities</Text>
                <Text size="xl" weight="medium">{stats.total_activities}</Text>
              </VStack>
            </CardContent>
          </Card>
          <Card className="aeos-flex-1">
            <CardContent>
              <VStack gap={2}>
                <Text tone="secondary" size="sm">Today</Text>
                <Text size="xl" weight="medium">{stats.today_activities}</Text>
              </VStack>
            </CardContent>
          </Card>
          <Card className="aeos-flex-1">
            <CardContent>
              <VStack gap={2}>
                <Text tone="secondary" size="sm">This Week</Text>
                <Text size="xl" weight="medium">{stats.week_activities}</Text>
              </VStack>
            </CardContent>
          </Card>
        </HStack>

        {/* Filters */}
        <Card>
          <CardContent>
            <HStack gap={3} align="center">
              <TextField
                placeholder="Search activities..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="aeos-flex-1"
              />
              <TextField
                label="Module"
                select
                value={selectedModule}
                onChange={(e) => setSelectedModule(e.target.value)}
                className="aeos-w-200"
              >
                <option value="">All Modules</option>
                <option value="users">Users</option>
                <option value="roles">Roles</option>
                <option value="tags">Tags</option>
                <option value="settings">Settings</option>
              </TextField>
              <TextField
                label="Action"
                select
                value={selectedAction}
                onChange={(e) => setSelectedAction(e.target.value)}
                className="aeos-w-200"
              >
                <option value="">All Actions</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
                <option value="login">Login</option>
                <option value="logout">Logout</option>
              </TextField>
              <Button onClick={handleFilter}>Filter</Button>
            </HStack>
          </CardContent>
        </Card>

        {/* Activity List */}
        {activities.data.length === 0 ? (
          <Card>
            <CardContent>
              <VStack gap={4} align="center">
                <Icon name="document" className="w-12 h-12" tone="secondary" />
                <Text tone="secondary">No activities found</Text>
                <Text tone="secondary" size="sm">Activities will appear here as users interact with the system</Text>
              </VStack>
            </CardContent>
          </Card>
        ) : (
          <VStack gap={3}>
            {activities.data.map((activity) => (
              <Card key={activity.id}>
                <CardContent>
                  <HStack gap={3} align="center" justify="space-between">
                    <HStack gap={3} align="center">
                      <Icon name={getActionIcon(activity.action)} className="w-8 h-8" tone="secondary" />
                      <VStack gap={0}>
                        <HStack gap={2} align="center">
                          <Text weight="medium">{activity.description}</Text>
                          <Badge intent={getActionColor(activity.action)}>{activity.action}</Badge>
                          {activity.module && <Badge intent="secondary">{activity.module}</Badge>}
                        </HStack>
                        <Text tone="secondary" size="sm">
                          {activity.user?.name || 'System'} • {new Date(activity.created_at).toLocaleString()}
                        </Text>
                      </VStack>
                    </HStack>
                    <Button variant="ghost" size="sm" onClick={() => router.visit(route('core.activity.show', activity.id))}>
                      <Icon name="eye" className="w-4 h-4" />
                    </Button>
                  </HStack>
                </CardContent>
              </Card>
            ))}
          </VStack>
        )}
      </VStack>
    </DashboardLayout>
  );
}

ActivityIndex.layout = page => <App title={page.props.title}>{page}</App>;
