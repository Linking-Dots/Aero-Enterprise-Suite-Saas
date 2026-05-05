<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\CommentMention;
use Aero\Core\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MentionsController extends Controller
{
    public function __construct(
        private CommentService $service
    ) {}

    /**
     * Display mentions inbox page.
     */
    public function index(): Response
    {
        $userId = auth()->id();
        $unreadCount = CommentMention::where('mentioned_user_id', $userId)
            ->unread()
            ->count();

        return Inertia::render('Core/Mentions/Index', [
            'title' => 'Mentions',
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Get mentions for the current user (JSON API).
     */
    public function getMentions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unread_only' => 'boolean',
        ]);

        $userId = auth()->id();
        
        $query = CommentMention::where('mentioned_user_id', $userId)
            ->with(['comment.user', 'comment.commentable'])
            ->orderBy('created_at', 'desc');

        if ($validated['unread_only'] ?? false) {
            $query->unread();
        }

        $mentions = $query->paginate(20);

        return response()->json($mentions);
    }

    /**
     * Mark a mention as read.
     */
    public function markAsRead(CommentMention $mention): JsonResponse
    {
        $this->authorize('view', $mention);

        $this->service->markMentionAsRead($mention);

        return response()->json($mention->fresh());
    }

    /**
     * Mark all mentions as read for the current user.
     */
    public function markAllAsRead(): JsonResponse
    {
        $userId = auth()->id();
        $count = $this->service->markAllMentionsAsRead($userId);

        return response()->json(['marked_count' => $count]);
    }

    /**
     * Get unread mentions count.
     */
    public function unreadCount(): JsonResponse
    {
        $userId = auth()->id();
        $count = CommentMention::where('mentioned_user_id', $userId)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }
}
