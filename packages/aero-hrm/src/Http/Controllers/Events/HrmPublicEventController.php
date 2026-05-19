<?php

namespace Aero\HRM\Http\Controllers\Events;

use Aero\HRM\Models\HrmEvent;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmPublicEventController extends Controller
{
    public function show(HrmEvent $event): Response
    {
        abort_unless($event->is_public && $event->status === HrmEvent::STATUS_PUBLISHED, 404);

        return Inertia::render('HRM/Events/Public/Show', [
            'event' => $event->only(['id', 'slug', 'title', 'description', 'location', 'starts_at', 'ends_at']),
            'sessions' => $event->sessions()->orderBy('starts_at')
                ->get(['id', 'title', 'starts_at', 'ends_at', 'location', 'capacity']),
        ]);
    }
}
