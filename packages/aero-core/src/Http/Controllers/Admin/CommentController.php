<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\Comment;
use Aero\Core\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    public function __construct(
        private CommentService $service
    ) {}

    /**
     * Get comments for a commentable entity (JSON API).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'include_replies' => 'boolean',
        ]);

        $comments = $this->service->getComments(
            $validated['commentable_type'],
            $validated['commentable_id'],
            $validated['include_replies'] ?? true
        );

        return response()->json($comments);
    }

    /**
     * Store a new comment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'content' => 'required|string|max:5000',
            'mentions' => 'array',
            'mentions.*' => 'integer|exists:users,id',
            'metadata' => 'array',
        ]);

        $comment = $this->service->create($validated);

        return response()->json($comment->load(['user', 'mentions', 'reactions']), 201);
    }

    /**
     * Show a specific comment.
     */
    public function show(Comment $comment): JsonResponse
    {
        $comment->load(['user', 'parent.user', 'replies.user', 'mentions', 'reactions']);

        return response()->json($comment);
    }

    /**
     * Update a comment.
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'mentions' => 'array',
            'mentions.*' => 'integer|exists:users,id',
            'metadata' => 'array',
        ]);

        $comment = $this->service->update($comment, $validated);

        return response()->json($comment->load(['user', 'mentions', 'reactions']));
    }

    /**
     * Delete a comment.
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $this->service->delete($comment);

        return response()->json(null, 204);
    }

    /**
     * Add a reaction to a comment.
     */
    public function addReaction(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'reaction_type' => 'required|string|max:50',
        ]);

        $reaction = $this->service->addReaction($comment, $validated['reaction_type']);

        return response()->json($reaction, 201);
    }

    /**
     * Remove a reaction from a comment.
     */
    public function removeReaction(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'reaction_type' => 'required|string|max:50',
        ]);

        $this->service->removeReaction($comment, $validated['reaction_type']);

        return response()->json(null, 204);
    }
}
