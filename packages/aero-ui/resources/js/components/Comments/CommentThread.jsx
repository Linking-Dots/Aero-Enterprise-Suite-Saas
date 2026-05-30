/**
 * CommentThread — reusable inline comment component.
 *
 * Props:
 *   commentableType  string   — Laravel model type, e.g. "App\\Models\\Employee"
 *   commentableId    number   — record ID
 *   title            string?  — optional heading (default: "Comments")
 *
 * Features:
 *   - Fetches comments via GET /api/comments on mount
 *   - Avatar from initials
 *   - Reply support: sets replyTo state, shows "Replying to X" indicator
 *   - Compose area gated by HRMAC permission: core.comments_mentions.comments.create
 *   - POSTs to /api/comments via fetch with CSRF from meta tag
 *
 * No inline style={}, no @heroui/react
 */
import { useState, useEffect, useCallback } from 'react';
import {
  VStack,
  HStack,
  Text,
  Mono,
  Button,
  Badge,
  useHRMAC,
} from '@aero/ui';

/* ── Helper: initials avatar ──────────────────────────────────────────────── */
function InitialsAvatar({ name }) {
  const initials = (name || '?')
    .split(' ')
    .slice(0, 2)
    .map(w => w[0]?.toUpperCase() ?? '')
    .join('');

  return (
    <span className="ct-avatar">{initials}</span>
  );
}

/* ── Single comment row ───────────────────────────────────────────────────── */
function CommentItem({ comment, onReply, depth = 0 }) {
  const isReply = depth > 0;

  return (
    <div className={`ct-comment${isReply ? ' ct-comment--reply' : ''}`}>
      <HStack gap={3} align="flex-start">
        <InitialsAvatar name={comment.user?.name} />
        <VStack gap={1} className="ct-comment__body">
          <HStack gap={2} align="center">
            <Text size="sm">{comment.user?.name || 'Unknown'}</Text>
            <Mono size="sm" tone="secondary">
              {new Date(comment.created_at).toLocaleString()}
            </Mono>
          </HStack>
          <Text size="sm">{comment.content}</Text>
          {onReply && !isReply && (
            <Button
              intent="ghost"
              size="sm"
              onClick={() => onReply(comment)}
            >
              Reply
            </Button>
          )}
        </VStack>
      </HStack>

      {Array.isArray(comment.replies) && comment.replies.length > 0 && (
        <VStack gap={2} className="ct-replies">
          {comment.replies.map(reply => (
            <CommentItem key={reply.id} comment={reply} depth={depth + 1} />
          ))}
        </VStack>
      )}
    </div>
  );
}

/* ── Main Component ───────────────────────────────────────────────────────── */
export default function CommentThread({ commentableType, commentableId, title }) {
  const canPost = useHRMAC('core.comments_mentions.comments.create');

  const [comments,  setComments]  = useState([]);
  const [loading,   setLoading]   = useState(false);
  const [posting,   setPosting]   = useState(false);
  const [content,   setContent]   = useState('');
  const [replyTo,   setReplyTo]   = useState(null);
  const [error,     setError]     = useState(null);

  const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const fetchComments = useCallback(async () => {
    if (!commentableType || !commentableId) return;
    setLoading(true);
    try {
      const params = new URLSearchParams({
        commentable_type: commentableType,
        commentable_id:   String(commentableId),
      });
      const res = await fetch(`/api/comments?${params.toString()}`, {
        headers: { Accept: 'application/json' },
      });
      if (res.ok) {
        const json = await res.json();
        setComments(json.data ?? json ?? []);
      }
    } catch {
      // silently fail — non-critical widget
    } finally {
      setLoading(false);
    }
  }, [commentableType, commentableId]);

  useEffect(() => {
    fetchComments();
  }, [fetchComments]);

  const handlePost = async (e) => {
    e.preventDefault();
    if (!content.trim()) return;

    setPosting(true);
    setError(null);

    try {
      const res = await fetch('/api/comments', {
        method: 'POST',
        headers: {
          'Content-Type':  'application/json',
          Accept:          'application/json',
          'X-CSRF-TOKEN':  csrfToken(),
        },
        body: JSON.stringify({
          commentable_type: commentableType,
          commentable_id:   commentableId,
          content:          content.trim(),
          parent_id:        replyTo?.id ?? null,
        }),
      });

      if (res.ok) {
        setContent('');
        setReplyTo(null);
        await fetchComments();
      } else {
        const json = await res.json().catch(() => ({}));
        setError(json.message || 'Failed to post comment.');
      }
    } catch {
      setError('Network error. Please try again.');
    } finally {
      setPosting(false);
    }
  };

  return (
    <>
      <style>{`
        .ct-thread {
          border-top: 1px solid var(--aeos-divider);
          padding-top: var(--aeos-space-4);
        }
        .ct-title {
          font-family: var(--aeos-font-display);
          font-size: 0.875rem;
          font-weight: 600;
          color: var(--aeos-text-secondary);
          text-transform: uppercase;
          letter-spacing: 0.06em;
          margin-bottom: var(--aeos-space-3);
        }
        .ct-avatar {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 32px;
          height: 32px;
          border-radius: 50%;
          background: var(--aeos-primary);
          color: #fff;
          font-family: var(--aeos-font-display);
          font-size: 0.75rem;
          font-weight: 600;
          flex-shrink: 0;
        }
        .ct-comment {
          padding: var(--aeos-space-3) 0;
          border-bottom: 1px solid var(--aeos-divider);
        }
        .ct-comment:last-child { border-bottom: none; }
        .ct-comment--reply {
          padding-left: var(--aeos-space-6);
          border-bottom: none;
        }
        .ct-comment__body { flex: 1; min-width: 0; }
        .ct-replies {
          padding-left: var(--aeos-space-8);
          margin-top: var(--aeos-space-2);
          border-left: 2px solid var(--aeos-divider);
        }
        .ct-compose {
          border-top: 1px solid var(--aeos-divider);
          padding-top: var(--aeos-space-4);
          margin-top: var(--aeos-space-2);
        }
        .ct-reply-indicator {
          display: flex;
          align-items: center;
          gap: var(--aeos-space-2);
          background: var(--aeos-bg-surface);
          border-left: 3px solid var(--aeos-primary);
          padding: var(--aeos-space-2) var(--aeos-space-3);
          border-radius: 0 var(--aeos-r-sm) var(--aeos-r-sm) 0;
          margin-bottom: var(--aeos-space-3);
        }
        .ct-textarea {
          width: 100%;
          font-family: var(--aeos-font-body);
          font-size: 0.875rem;
          color: var(--aeos-text-primary);
          background: var(--aeos-bg-surface);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-md);
          padding: var(--aeos-space-2) var(--aeos-space-3);
          resize: vertical;
          min-height: 72px;
        }
        .ct-textarea:focus {
          outline: none;
          border-color: var(--aeos-primary);
        }
        .ct-error {
          color: var(--aeos-destructive);
          font-size: 0.8125rem;
        }
      `}</style>

      <div className="ct-thread">
        <div className="ct-title">{title || 'Comments'}</div>

        {loading && (
          <Text tone="secondary" size="sm">Loading comments…</Text>
        )}

        {!loading && comments.length === 0 && (
          <Text tone="secondary" size="sm">No comments yet. Be the first to comment.</Text>
        )}

        {!loading && comments.length > 0 && (
          <VStack gap={0}>
            {comments.map(comment => (
              <CommentItem
                key={comment.id}
                comment={comment}
                onReply={canPost ? setReplyTo : null}
              />
            ))}
          </VStack>
        )}

        {canPost && (
          <form className="ct-compose" onSubmit={handlePost}>
            {replyTo && (
              <div className="ct-reply-indicator">
                <Text size="sm" tone="secondary">Replying to</Text>
                <Text size="sm">{replyTo.user?.name || 'Unknown'}</Text>
                <Button
                  type="button"
                  intent="ghost"
                  size="sm"
                  onClick={() => setReplyTo(null)}
                >
                  Cancel
                </Button>
              </div>
            )}

            <VStack gap={3}>
              <textarea
                className="ct-textarea"
                rows={3}
                value={content}
                onChange={e => setContent(e.target.value)}
                placeholder={replyTo ? `Reply to ${replyTo.user?.name}…` : 'Write a comment…'}
              />

              {error && (
                <span className="ct-error">{error}</span>
              )}

              <HStack gap={2} justify="end">
                <Button
                  type="submit"
                  intent="primary"
                  size="sm"
                  loading={posting}
                  disabled={!content.trim()}
                >
                  Post
                </Button>
              </HStack>
            </VStack>
          </form>
        )}
      </div>
    </>
  );
}
