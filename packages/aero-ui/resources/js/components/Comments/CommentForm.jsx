import { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import {
  Card,
  CardContent,
  VStack,
  HStack,
  Text,
  Avatar,
  Icon,
  Button,
  TextField,
  useToast,
} from '@aero/ui';

export default function CommentForm({
  commentableType,
  commentableId,
  parentId = null,
  initialComment = null,
  onCancel,
  onSuccess,
  currentUser,
}) {
  const { toast } = useToast();
  const [showMentions, setShowMentions] = useState(false);
  const [mentionQuery, setMentionQuery] = useState('');
  const [mentionSuggestions, setMentionSuggestions] = useState([]);
  const [selectedMentions, setSelectedMentions] = useState([]);

  const isEditing = !!initialComment;

  const { data, setData, post, put, processing, errors, reset } = useForm({
    commentable_type: commentableType,
    commentable_id: commentableId,
    parent_id: parentId,
    content: initialComment?.content || '',
    mentions: initialComment?.mentions?.map(m => m.mentioned_user_id) || [],
  });

  useEffect(() => {
    if (initialComment) {
      setData('content', initialComment.content);
      setSelectedMentions(initialComment.mentions?.map(m => m.mentioned_user_id) || []);
    }
  }, [initialComment]);

  const handleSubmit = (e) => {
    e.preventDefault();

    if (isEditing) {
      put(route('core.comments.update', initialComment.id), {
        onSuccess: () => {
          toast({
            title: 'Success',
            description: 'Comment updated successfully',
          });
          reset();
          onSuccess?.();
        },
        onError: () => {
          toast({
            title: 'Error',
            description: 'Failed to update comment',
            variant: 'destructive',
          });
        },
      });
    } else {
      post(route('core.comments.store'), {
        onSuccess: () => {
          toast({
            title: 'Success',
            description: 'Comment added successfully',
          });
          reset();
          onSuccess?.();
        },
        onError: () => {
          toast({
            title: 'Error',
            description: 'Failed to add comment',
            variant: 'destructive',
          });
        },
      });
    }
  };

  const handleMentionSearch = async (query) => {
    setMentionQuery(query);
    if (query.length < 2) {
      setMentionSuggestions([]);
      return;
    }

    try {
      const response = await fetch(`/api/users/search?q=${query}`);
      const data = await response.json();
      setMentionSuggestions(data || []);
    } catch (error) {
      console.error('Failed to search users:', error);
    }
  };

  const handleContentChange = (e) => {
    const content = e.target.value;
    setData('content', content);

    // Check for @mentions
    const match = content.match(/@(\w*)$/);
    if (match) {
      setShowMentions(true);
      handleMentionSearch(match[1]);
    } else {
      setShowMentions(false);
    }
  };

  const addMention = (user) => {
    if (!selectedMentions.includes(user.id)) {
      setSelectedMentions([...selectedMentions, user.id]);
      setData('mentions', [...selectedMentions, user.id]);
    }
    setShowMentions(false);
    setMentionQuery('');
  };

  const removeMention = (userId) => {
    const newMentions = selectedMentions.filter(id => id !== userId);
    setSelectedMentions(newMentions);
    setData('mentions', newMentions);
  };

  return (
    <Card>
      <CardContent>
        <form onSubmit={handleSubmit}>
          <VStack gap={3}>
            <HStack gap={3} align="flex-start">
              <Avatar
                src={currentUser?.avatar_url}
                alt={currentUser?.name}
                size="md"
              />
              <VStack gap={2} className="aeos-flex-1">
                <TextField
                  label={isEditing ? 'Edit Comment' : parentId ? 'Reply' : 'Add a Comment'}
                  placeholder="Write your comment... Use @ to mention users"
                  value={data.content}
                  onChange={(e) => setData('content', e.target.value)}
                  onInput={handleContentChange}
                  multiline
                  rows={4}
                  error={errors.content}
                  helperText={errors.content}
                />

                {showMentions && mentionSuggestions.length > 0 && (
                  <Card>
                    <CardContent>
                      <VStack gap={1}>
                        {mentionSuggestions.map((user) => (
                          <HStack
                            key={user.id}
                            gap={2}
                            align="center"
                            justify="space-between"
                            style={{ padding: '8px', cursor: 'pointer' }}
                            onClick={() => addMention(user)}
                          >
                            <HStack gap={2} align="center">
                              <Avatar
                                src={user.avatar_url}
                                alt={user.name}
                                size="sm"
                              />
                              <Text>{user.name}</Text>
                              <Text tone="secondary" size="sm">
                                @{user.username}
                              </Text>
                            </HStack>
                          </HStack>
                        ))}
                      </VStack>
                    </CardContent>
                  </Card>
                )}

                {selectedMentions.length > 0 && (
                  <HStack gap={2} align="center" flexWrap>
                    <Text size="sm" tone="secondary">Mentioned:</Text>
                    {selectedMentions.map((userId) => {
                      const user = mentionSuggestions.find(u => u.id === userId);
                      return user ? (
                        <Badge key={userId} onRemove={() => removeMention(userId)}>
                          {user.name}
                        </Badge>
                      ) : null;
                    })}
                  </HStack>
                )}

                <HStack gap={2} justify="flex-end">
                  {onCancel && (
                    <Button
                      type="button"
                      variant="ghost"
                      onClick={onCancel}
                      disabled={processing}
                    >
                      Cancel
                    </Button>
                  )}
                  <Button
                    type="submit"
                    disabled={processing || !data.content.trim()}
                  >
                    {processing ? 'Saving...' : isEditing ? 'Update' : 'Post'}
                  </Button>
                </HStack>
              </VStack>
            </HStack>
          </VStack>
        </form>
      </CardContent>
    </Card>
  );
}
