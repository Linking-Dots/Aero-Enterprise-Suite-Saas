<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\KbArticle;
use Aero\Platform\Models\Enterprise\SupportTicket;
use Aero\Platform\Models\Enterprise\SupportTicketMessage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HelpCenterService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function listArticles(int $perPage = 25): LengthAwarePaginator
    {
        return KbArticle::orderByDesc('created_at')->paginate($perPage);
    }

    public function createArticle(array $data): KbArticle
    {
        return DB::transaction(function () use ($data): KbArticle {
            $data['slug'] ??= Str::slug($data['title']) . '-' . Str::random(6);
            $article = KbArticle::create($data);

            $this->audit->log(
                event: AuditEventType::KB_ARTICLE_CREATED->value,
                action: 'create',
                subject: $article,
                description: "KB article created: {$article->title}"
            );

            return $article;
        });
    }

    public function updateArticle(KbArticle $article, array $data): KbArticle
    {
        return DB::transaction(function () use ($article, $data): KbArticle {
            $article->update($data);

            $this->audit->log(
                event: AuditEventType::KB_ARTICLE_UPDATED->value,
                action: 'update',
                subject: $article,
                description: "KB article updated: {$article->title}"
            );

            return $article->refresh();
        });
    }

    public function publishArticle(KbArticle $article): KbArticle
    {
        return DB::transaction(function () use ($article): KbArticle {
            $article->update(['is_published' => true, 'published_at' => now()]);

            $this->audit->log(
                event: AuditEventType::KB_ARTICLE_PUBLISHED->value,
                action: 'publish',
                subject: $article,
                description: "KB article published: {$article->title}"
            );

            return $article->refresh();
        });
    }

    public function deleteArticle(KbArticle $article): void
    {
        DB::transaction(function () use ($article): void {
            $title = $article->title;
            $article->delete();

            $this->audit->log(
                event: AuditEventType::KB_ARTICLE_DELETED->value,
                action: 'delete',
                description: "KB article deleted: {$title}"
            );
        });
    }

    public function listTickets(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return SupportTicket::with(['messages'])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn ($q) => $q->where('priority', $filters['priority']))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function replyTicket(SupportTicket $ticket, array $data): SupportTicketMessage
    {
        return DB::transaction(function () use ($ticket, $data): SupportTicketMessage {
            $message = SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'author_type' => $data['author_type'] ?? 'staff',
                'author_id' => $data['author_id'],
                'body' => $data['body'],
            ]);

            if ($ticket->status === 'open') {
                $ticket->update(['status' => 'pending']);
            }

            $this->audit->log(
                event: AuditEventType::TICKET_REPLIED->value,
                action: 'reply',
                subject: $ticket,
                description: "Reply added to ticket {$ticket->id}"
            );

            return $message;
        });
    }

    public function assignTicket(SupportTicket $ticket, string $assigneeId): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $assigneeId): SupportTicket {
            $ticket->update(['assignee_id' => $assigneeId]);

            $this->audit->log(
                event: AuditEventType::TICKET_ASSIGNED->value,
                action: 'assign',
                subject: $ticket,
                description: "Ticket {$ticket->id} assigned to {$assigneeId}"
            );

            return $ticket->refresh();
        });
    }

    public function escalateTicket(SupportTicket $ticket): SupportTicket
    {
        return DB::transaction(function () use ($ticket): SupportTicket {
            $ticket->update(['priority' => 'urgent']);

            $this->audit->log(
                event: AuditEventType::TICKET_ESCALATED->value,
                action: 'escalate',
                subject: $ticket,
                description: "Ticket {$ticket->id} escalated to urgent"
            );

            return $ticket->refresh();
        });
    }

    public function closeTicket(SupportTicket $ticket): SupportTicket
    {
        return DB::transaction(function () use ($ticket): SupportTicket {
            $ticket->update(['status' => 'closed']);

            $this->audit->log(
                event: AuditEventType::TICKET_CLOSED->value,
                action: 'close',
                subject: $ticket,
                description: "Ticket {$ticket->id} closed"
            );

            return $ticket->refresh();
        });
    }
}
