<?php

declare(strict_types=1);

namespace Aero\Assistant\Http\Controllers;

use Aero\Assistant\Http\Requests\SendMessageRequest;
use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Services\AeonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AeonController extends Controller
{
    public function __construct(private AeonService $aeon) {}

    public function message(SendMessageRequest $request): JsonResponse
    {
        $out = $this->aeon->send(
            (int) auth()->id(),
            $request->integer('conversation_id') ?: null,
            (string) $request->string('message'),
        );

        $reply = $out['reply'];

        return response()->json([
            'conversation_id' => $out['conversation']->id,
            'reply' => [
                'role' => $reply->role,
                'content' => $reply->content,
                'blocks' => $reply->blocks ?? [['type' => 'text', 'text' => $reply->content]],
            ],
        ]);
    }

    public function conversations(): JsonResponse
    {
        $items = Conversation::where('user_id', auth()->id())
            ->whereNull('archived_at')
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);

        return response()->json(['conversations' => $items]);
    }

    public function show(Conversation $conversation): JsonResponse
    {
        abort_unless((int) $conversation->user_id === (int) auth()->id(), 403);

        return response()->json([
            'conversation' => $conversation->only(['id', 'title']),
            'messages' => $conversation->messages()->orderBy('id')
                ->get(['id', 'role', 'content', 'blocks']),
        ]);
    }
}
