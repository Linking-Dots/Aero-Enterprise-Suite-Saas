<?php

namespace Aero\Core\Services;

use Aero\Core\Models\Comment;
use Aero\Core\Models\CommentMention;
use Aero\Core\Models\CommentReaction;
use Illuminate\Support\Facades\Log;

class CommentService
{
    /**
     * Create a new comment.
     */
    public function create(array $data): Comment
    {
        $comment = Comment::create([
            'user_id' => auth()->id(),
            'commentable_id' => $data['commentable_id'],
            'commentable_type' => $data['commentable_type'],
            'parent_id' => $data['parent_id'] ?? null,
            'content' => $data['content'],
            'metadata' => $data['metadata'] ?? [],
        ]);

        // Parse and create mentions
        if (isset($data['mentions']) && is_array($data['mentions'])) {
            $this->createMentions($comment, $data['mentions']);
        }

        Log::info('Comment created', ['comment_id' => $comment->id]);

        return $comment;
    }

    /**
     * Update an existing comment.
     */
    public function update(Comment $comment, array $data): Comment
    {
        $comment->update([
            'content' => $data['content'],
            'metadata' => array_merge($comment->metadata ?? [], $data['metadata'] ?? []),
        ]);

        $comment->markAsEdited();

        // Update mentions if provided
        if (isset($data['mentions']) && is_array($data['mentions'])) {
            $this->updateMentions($comment, $data['mentions']);
        }

        Log::info('Comment updated', ['comment_id' => $comment->id]);

        return $comment->fresh();
    }

    /**
     * Delete a comment.
     */
    public function delete(Comment $comment): bool
    {
        $result = $comment->delete();

        Log::info('Comment deleted', ['comment_id' => $comment->id]);

        return $result;
    }

    /**
     * Get comments for a commentable entity.
     */
    public function getComments(string $commentableType, int $commentableId, bool $includeReplies = true)
    {
        $query = Comment::where('commentable_type', $commentableType)
            ->where('commentable_id', $commentableId)
            ->root()
            ->with(['user', 'replies.user', 'reactions'])
            ->orderBy('created_at', 'desc');

        if (!$includeReplies) {
            $query->withCount('replies');
        }

        return $query->get();
    }

    /**
     * Add a reaction to a comment.
     */
    public function addReaction(Comment $comment, string $reactionType): CommentReaction
    {
        $userId = auth()->id();

        // Check if user already reacted with this type
        $existing = $comment->reactions()
            ->where('user_id', $userId)
            ->where('reaction_type', $reactionType)
            ->first();

        if ($existing) {
            return $existing;
        }

        $reaction = $comment->reactions()->create([
            'user_id' => $userId,
            'reaction_type' => $reactionType,
        ]);

        Log::info('Reaction added', [
            'comment_id' => $comment->id,
            'reaction_type' => $reactionType,
        ]);

        return $reaction;
    }

    /**
     * Remove a reaction from a comment.
     */
    public function removeReaction(Comment $comment, string $reactionType): bool
    {
        $userId = auth()->id();

        $result = $comment->reactions()
            ->where('user_id', $userId)
            ->where('reaction_type', $reactionType)
            ->delete();

        if ($result) {
            Log::info('Reaction removed', [
                'comment_id' => $comment->id,
                'reaction_type' => $reactionType,
            ]);
        }

        return $result;
    }

    /**
     * Get unread mentions for a user.
     */
    public function getUnreadMentions(int $userId)
    {
        return CommentMention::where('mentioned_user_id', $userId)
            ->unread()
            ->with(['comment.user', 'comment.commentable'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Mark a mention as read.
     */
    public function markMentionAsRead(CommentMention $mention): void
    {
        $mention->markAsRead();
    }

    /**
     * Mark all mentions as read for a user.
     */
    public function markAllMentionsAsRead(int $userId): int
    {
        return CommentMention::where('mentioned_user_id', $userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Create mentions for a comment.
     */
    protected function createMentions(Comment $comment, array $mentionedUserIds): void
    {
        foreach ($mentionedUserIds as $userId) {
            // Don't mention the comment author
            if ($userId == $comment->user_id) {
                continue;
            }

            CommentMention::firstOrCreate([
                'comment_id' => $comment->id,
                'mentioned_user_id' => $userId,
            ]);
        }
    }

    /**
     * Update mentions for a comment.
     */
    protected function updateMentions(Comment $comment, array $mentionedUserIds): void
    {
        // Remove existing mentions not in the new list
        $comment->mentions()
            ->whereNotIn('mentioned_user_id', $mentionedUserIds)
            ->delete();

        // Add new mentions
        foreach ($mentionedUserIds as $userId) {
            if ($userId == $comment->user_id) {
                continue;
            }

            CommentMention::firstOrCreate([
                'comment_id' => $comment->id,
                'mentioned_user_id' => $userId,
            ]);
        }
    }

    /**
     * Parse @mentions from comment content.
     */
    public function parseMentions(string $content): array
    {
        preg_match_all('/@(\w+)/', $content, $matches);

        // This would typically query the users table to get actual user IDs
        // For now, return the usernames
        return $matches[1] ?? [];
    }
}
