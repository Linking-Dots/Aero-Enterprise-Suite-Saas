import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
  DashboardLayout,
  Card,
  CardContent,
  Button,
  Badge,
  VStack,
  HStack,
  Text,
  Avatar,
  Icon,
  useToast,
} from '@aero/ui';

export default function MentionsIndex({ title, unread_count }) {
  const { toast } = useToast();
  const [mentions, setMentions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filterUnreadOnly, setFilterUnreadOnly] = useState(false);

  const fetchMentions = async () => {
    setLoading(true);
    try {
      const response = await fetch(route('core.mentions.list', { unread_only: filterUnreadOnly }));
      const data = await response.json();
      setMentions(data.data || []);
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Failed to load mentions',
        variant: 'destructive',
      });
    } finally {
      setLoading(false);
    }
  };

  const markAsRead = async (mentionId) => {
    try {
      await router.post(route('core.mentions.mark-read', mentionId));
      toast({
        title: 'Success',
        description: 'Mention marked as read',
      });
      fetchMentions();
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Failed to mark as read',
        variant: 'destructive',
      });
    }
  };

  const markAllAsRead = async () => {
    try {
      await router.post(route('core.mentions.mark-all-read'));
      toast({
        title: 'Success',
        description: 'All mentions marked as read',
      });
      fetchMentions();
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Failed to mark all as read',
        variant: 'destructive',
      });
    }
  };

  useEffect(() => {
    fetchMentions();
  }, [filterUnreadOnly]);

  return (
    <DashboardLayout
      title={title}
      actions={
        <HStack gap={2}>
          <Button
            intent="secondary"
            onClick={() => setFilterUnreadOnly(!filterUnreadOnly)}
          >
            {filterUnreadOnly ? 'Show All' : 'Unread Only'}
            {unread_count > 0 && <Badge>{unread_count}</Badge>}
          </Button>
          {unread_count > 0 && (
            <Button onClick={markAllAsRead}>Mark All Read</Button>
          )}
        </HStack>
      }
    >
      <VStack gap={4}>
        {loading ? (
          <Text tone="secondary">Loading mentions...</Text>
        ) : mentions.length === 0 ? (
          <Card>
            <CardContent>
              <VStack gap={4} align="center">
                <Icon name="chat-bubble-left-right" className="w-12 h-12" tone="secondary" />
                <Text tone="secondary">No mentions found</Text>
              </VStack>
            </CardContent>
          </Card>
        ) : (
          mentions.map((mention) => (
            <Card key={mention.id}>
              <CardContent>
                <VStack gap={3}>
                  <HStack gap={3} align="center" justify="space-between">
                    <HStack gap={3} align="center">
                      <Avatar
                        src={mention.comment.user.avatar_url}
                        alt={mention.comment.user.name}
                        size="sm"
                      />
                      <VStack gap={0}>
                        <Text weight="medium">{mention.comment.user.name}</Text>
                        <Text tone="secondary" size="sm">
                          mentioned you in a comment
                        </Text>
                      </VStack>
                    </HStack>
                    {!mention.is_read && (
                      <Button
                        size="sm"
                        onClick={() => markAsRead(mention.id)}
                      >
                        Mark as Read
                      </Button>
                    )}
                  </HStack>
                  <Text>{mention.comment.content}</Text>
                  <HStack gap={2} align="center">
                    <Text tone="secondary" size="sm">
                      {new Date(mention.created_at).toLocaleString()}
                    </Text>
                    {mention.is_read && (
                      <Badge intent="success">Read</Badge>
                    )}
                  </HStack>
                </VStack>
              </CardContent>
            </Card>
          ))
        )}
      </VStack>
    </DashboardLayout>
  );
}
