<?php

declare(strict_types=1);

namespace Aero\Assistant\Http\Controllers;

use Aero\Assistant\Models\Conversation;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AeonPageController extends Controller
{
    public function index(): Response
    {
        $conversations = Conversation::where('user_id', auth()->id())
            ->whereNull('archived_at')
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);

        return Inertia::render('Aeon/Index', [
            'conversations' => $conversations,
        ]);
    }
}
