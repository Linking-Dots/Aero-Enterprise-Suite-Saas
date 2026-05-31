import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  Card,
  CardContent,
  VStack,
  HStack,
  Text,
  Avatar,
  Icon,
  Button,
  Badge,
  Menu,
  MenuItem,
} from '@aero/ui';

export default function Comment({ comment, onEdit, onDelete, onReply, currentUser }) {
  const [showReactions, setShowReactions] = useState(false);
  const [showReplies, setShowReplies] = useState(false);

  const handleReaction = async (reactionType) => {
    try {
      await router.post(
        route('core.comments.add-reaction', comment.id),
        { reaction_type: reactionType }
      );
      // Refresh the comment data
      window.location.reload();
    } catch (error) {
      console.error('Failed to add reaction:', error);
    }
  };

  const handleRemoveReaction = async (reactionType) => {
    try {
      await router.delete(
        route('core.comments.remove-reaction', comment.id),
        { data: { reaction_type: reactionType } }
      );
      window.location.reload();
    } catch (error) {
      console.error('Failed to remove reaction:', error);
    }
  };

  const reactionSummary = comment.reaction_summary || {};
  const hasUserReacted = (reactionType) => {
    return comment.reactions?.some(
      r => r.reaction_type === reactionType && r.user_id === currentUser?.id
    );
  };

  const canEdit = currentUser?.id === comment.user_id;
  const canDelete = currentUser?.id === comment.user_id || currentUser?.is_admin;

  return (
    <Card>
      <CardContent>
        <VStack gap={3}>
          <HStack gap={3} align="flex-start">
            <Avatar
              src={comment.user?.avatar_url}
              alt={comment.user?.name}
              size="md"
            />
            <VStack gap={1} className="aeos-flex-1">
              <HStack gap={2} align="center" justify="space-between">
                <HStack gap={2} align="center">
                  <Text weight="medium">{comment.user?.name}</Text>
                  {comment.is_edited && (
                    <Badge size="sm" intent="secondary">Edited</Badge>
                  )}
                </HStack>
                {(canEdit || canDelete) && (
                  <Menu>
                    <Menu.Button as={Button} variant="ghost" size="sm">
                      <Icon name="ellipsis-horizontal" />
                    </Menu.Button>
                    <Menu.Items>
                      {canEdit && (
                        <MenuItem onClick={() => onEdit?.(comment)}>
                          Edit
                        </MenuItem>
                      )}
                      {canDelete && (
                        <MenuItem onClick={() => onDelete?.(comment)}>
                          Delete
                        </MenuItem>
                      )}
                    </Menu.Items>
                  </Menu>
                )}
              </HStack>
              <Text>{comment.content}</Text>
              <HStack gap={3} align="center">
                <Text tone="secondary" size="sm">
                  {new Date(comment.created_at).toLocaleString()}
                </Text>
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={() => setShowReactions(!showReactions)}
                >
                  <Icon name="face-smile" className="aeos-icon-sm" />
                </Button>
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={() => onReply?.(comment)}
                >
                  Reply
                </Button>
                {comment.replies_count > 0 && (
                  <Button
                    size="sm"
                    variant="ghost"
                    onClick={() => setShowReplies(!showReplies)}
                  >
                    {comment.replies_count} {comment.replies_count === 1 ? 'reply' : 'replies'}
                  </Button>
                )}
              </HStack>
            </VStack>
          </HStack>

          {showReactions && (
            <HStack gap={2} align="center">
              {['👍', '❤️', '😂', '😮', '😢', '🎉'].map((emoji, index) => {
                const reactionType = ['like', 'love', 'laugh', 'surprised', 'sad', 'celebrate'][index];
                const count = reactionSummary[reactionType] || 0;
                const userReacted = hasUserReacted(reactionType);

                return (
                  <Button
                    key={emoji}
                    size="sm"
                    variant={userReacted ? 'solid' : 'ghost'}
                    onClick={() => userReacted ? handleRemoveReaction(reactionType) : handleReaction(reactionType)}
                  >
                    {emoji} {count > 0 && count}
                  </Button>
                );
              })}
            </HStack>
          )}

          {showReplies && comment.replies && comment.replies.length > 0 && (
            <VStack gap={2} style={{ marginLeft: 48 }}>
              {comment.replies.map((reply) => (
                <Comment
                  key={reply.id}
                  comment={reply}
                  onEdit={onEdit}
                  onDelete={onDelete}
                  onReply={onReply}
                  currentUser={currentUser}
                />
              ))}
            </VStack>
          )}
        </VStack>
      </CardContent>
    </Card>
  );
}
