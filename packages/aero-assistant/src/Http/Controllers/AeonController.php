<?php

declare(strict_types=1);

namespace Aero\Assistant\Http\Controllers;

use Aero\Assistant\Http\Requests\SendMessageRequest;
use Aero\Assistant\Models\Conversation;
use Aero\Assistant\Models\Message;
use Aero\Assistant\Services\AeonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AeonController extends Controller
{
    public function __construct(private AeonService $aeon) {}

    public function message(SendMessageRequest $request): JsonResponse
    {
        $out = $this->aeon->send(
            (int) auth()->id(),
            $request->integer('conversation_id') ?: null,
            (string) $request->string('message'),
            $this->context($request),
        );

        return response()->json($this->replyPayload($out));
    }

    /**
     * Same turn as message(), but as a Server-Sent-Events stream: `stage`
     * events narrate the agent loop (thinking / querying / opening) while it
     * runs, then a single `done` event carries the full reply payload.
     */
    public function stream(SendMessageRequest $request): StreamedResponse
    {
        $userId = (int) auth()->id();
        $conversationId = $request->integer('conversation_id') ?: null;
        $text = (string) $request->string('message');
        $context = $this->context($request);

        return response()->stream(function () use ($userId, $conversationId, $text, $context) {
            $send = static function (string $event, array $data): void {
                echo "event: {$event}\n";
                echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            };

            try {
                $out = $this->aeon->send($userId, $conversationId, $text, $context,
                    static fn (string $label) => $send('stage', ['label' => $label]));
                $send('done', $this->replyPayload($out));
            } catch (\Throwable $e) {
                report($e);
                $send('error', ['message' => 'Aeon is unavailable right now. Please try again.']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no', // nginx: don't buffer the stream
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

    // Route params are read from the route by NAME, never via a scalar/model
    // method arg: under {tenant}-subdomain tenancy a scalar arg receives the
    // subdomain, not the intended param (tenant route-param trap). Aeon runs in
    // every context (tenant, central, standalone), so this must be explicit.
    public function show(Request $request): JsonResponse
    {
        $conversation = Conversation::findOrFail($request->route('conversation'));
        abort_unless((int) $conversation->user_id === (int) auth()->id(), 403);

        return response()->json([
            'conversation' => $conversation->only(['id', 'title']),
            'messages' => $conversation->messages()->orderBy('id')
                ->get(['id', 'role', 'content', 'blocks', 'feedback']),
        ]);
    }

    /** Thumbs up/down on an assistant reply (1, -1, or 0 to clear). */
    public function feedback(Request $request): JsonResponse
    {
        $message = Message::findOrFail($request->route('message'));
        abort_unless((int) $message->conversation?->user_id === (int) auth()->id(), 403);
        $value = (int) $request->validate(['value' => 'required|integer|in:-1,0,1'])['value'];

        $message->update(['feedback' => $value === 0 ? null : $value]);

        return response()->json(['ok' => true, 'feedback' => $message->feedback]);
    }

    /** @return array<string,mixed> */
    private function context(Request $request): array
    {
        $user = $request->user();
        $context = [
            'page' => (string) $request->input('context.page', ''),
            'user_name' => $user?->name,
        ];
        if ($user && method_exists($user, 'getRoleNames')) {
            $context['roles'] = $user->getRoleNames()->all();
        }

        return $context;
    }

    /**
     * @param  array{conversation: Conversation, reply: Message}  $out
     * @return array<string,mixed>
     */
    private function replyPayload(array $out): array
    {
        $reply = $out['reply'];

        return [
            'conversation_id' => $out['conversation']->id,
            'reply' => [
                'id' => $reply->id,
                'role' => $reply->role,
                'content' => $reply->content,
                'blocks' => $reply->blocks ?? [['type' => 'text', 'text' => $reply->content]],
            ],
            // Fresh allowance after this turn, so the drawer reflects usage live.
            'usage' => $this->usage(),
        ];
    }

    /**
     * Current tenant AI allowance snapshot for the UI (null when unmetered).
     *
     * @return array<string,mixed>|null
     */
    private function usage(): ?array
    {
        try {
            if (! app()->bound(\Aero\Contracts\Ai\AeonQuotaContract::class)) {
                return null;
            }
            $s = app(\Aero\Contracts\Ai\AeonQuotaContract::class)->status();

            return [
                'used' => (int) ($s['used'] ?? 0),
                'limit' => (int) ($s['limit'] ?? -1),
                'remaining' => (int) ($s['remaining'] ?? -1),
                'unlimited' => ($s['limit'] ?? -1) === -1,
                'model' => (string) ($s['model'] ?? 'flash'),
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
