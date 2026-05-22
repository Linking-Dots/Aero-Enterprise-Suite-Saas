<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\StoreArticleRequest;
use Aero\Platform\Http\Requests\Enterprise\StoreTicketReplyRequest;
use Aero\Platform\Models\Enterprise\KbArticle;
use Aero\Platform\Models\Enterprise\SupportTicket;
use Aero\Platform\Services\Enterprise\HelpCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HelpCenterController extends Controller
{
    public function __construct(private readonly HelpCenterService $svc) {}

    public function articles(): Response
    {
        return Inertia::render('Platform/Admin/HelpCenter/Articles', [
            'articles' => $this->svc->listArticles(),
        ]);
    }

    public function storeArticle(StoreArticleRequest $request): JsonResponse
    {
        $article = $this->svc->createArticle([
            ...$request->validated(),
            'author_id' => (string) Auth::guard('landlord')->id(),
        ]);

        return response()->json(['message' => 'Article created.', 'id' => $article->id], 201);
    }

    public function updateArticle(StoreArticleRequest $request, KbArticle $article): JsonResponse
    {
        $this->svc->updateArticle($article, $request->validated());

        return response()->json(['message' => 'Article updated.']);
    }

    public function publishArticle(KbArticle $article): JsonResponse
    {
        $this->svc->publishArticle($article);

        return response()->json(['message' => 'Article published.']);
    }

    public function deleteArticle(KbArticle $article): JsonResponse
    {
        $this->svc->deleteArticle($article);

        return response()->json(['message' => 'Article deleted.']);
    }

    public function tickets(Request $request): Response
    {
        $filters = $request->only(['status', 'priority']);

        return Inertia::render('Platform/Admin/HelpCenter/Tickets', [
            'tickets' => $this->svc->listTickets($filters),
        ]);
    }

    public function reply(StoreTicketReplyRequest $request, SupportTicket $ticket): JsonResponse
    {
        $message = $this->svc->replyTicket($ticket, [
            ...$request->validated(),
            'author_type' => 'staff',
            'author_id' => (string) Auth::guard('landlord')->id(),
        ]);

        return response()->json(['message' => 'Reply sent.', 'id' => $message->id], 201);
    }

    public function assign(Request $request, SupportTicket $ticket): JsonResponse
    {
        $validated = $request->validate(['assignee_id' => ['required', 'string']]);
        $this->svc->assignTicket($ticket, $validated['assignee_id']);

        return response()->json(['message' => 'Ticket assigned.']);
    }

    public function escalate(SupportTicket $ticket): JsonResponse
    {
        $this->svc->escalateTicket($ticket);

        return response()->json(['message' => 'Ticket escalated.']);
    }

    public function close(SupportTicket $ticket): JsonResponse
    {
        $this->svc->closeTicket($ticket);

        return response()->json(['message' => 'Ticket closed.']);
    }
}
