import { router } from '@inertiajs/react';
import {
  DetailPageLayout,
  Card, CardContent, Button, Badge,
  HStack, VStack, Text, Mono, Alert,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function Sessions({ sessions, current_session_id, max_sessions }) {
  const toast = useToast();

  const terminate = (sessionId) => {
    if (!confirm('Terminate this session?')) return;
    router.delete(route('core.security.sessions.terminate', sessionId), {
      preserveScroll: true,
      onSuccess: () => toast.success('Session terminated.'),
      onError:   () => toast.error('Failed to terminate session.'),
    });
  };

  const terminateAll = () => {
    if (!confirm('Sign out of all other sessions?')) return;
    router.delete(route('core.security.sessions.terminate-all'), {
      preserveScroll: true,
      onSuccess: () => toast.success('All other sessions terminated.'),
    });
  };

  const otherSessions = sessions.filter(s => s.id !== current_session_id);

  return (
    <DetailPageLayout
      title="Active Sessions"
      actions={
        otherSessions.length > 0
          ? (
            <Button intent="danger" onClick={terminateAll}>
              Sign out all other sessions
            </Button>
          )
          : null
      }
    >
      <VStack gap={4}>
        {sessions.length >= max_sessions && (
          <Alert intent="warning">
            You've reached the maximum of {max_sessions} concurrent sessions.
            Sign out of other sessions to log in from a new device.
          </Alert>
        )}

        <VStack gap={3}>
          {sessions.map(session => (
            <Card key={session.id}>
              <CardContent>
                <HStack justify="between" align="center">
                  <VStack gap={0}>
                    <HStack gap={2} align="center">
                      <Text weight="semibold">
                        {session.browser ?? 'Unknown browser'} · {session.platform ?? 'Unknown OS'}
                      </Text>
                      {session.id === current_session_id && (
                        <Badge intent="success">Current session</Badge>
                      )}
                    </HStack>
                    <Mono size="xs" tone="tertiary">
                      {session.ip_address} · Last active {session.last_activity}
                    </Mono>
                  </VStack>
                  {session.id !== current_session_id && (
                    <Button size="sm" intent="danger" onClick={() => terminate(session.id)}>
                      Terminate
                    </Button>
                  )}
                </HStack>
              </CardContent>
            </Card>
          ))}

          {sessions.length === 0 && (
            <Card>
              <CardContent>
                <Text tone="secondary">No active sessions found.</Text>
              </CardContent>
            </Card>
          )}
        </VStack>
      </VStack>
    </DetailPageLayout>
  );
}

Sessions.layout = page => (
  <App title="Active Sessions">{page}</App>
);
