<?php

namespace Aero\HRM\Http\Controllers\Events;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmEvent;
use Aero\HRM\Models\HrmEventRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class HrmEventRegistrationController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(HrmEvent $event): Response
    {
        return Inertia::render('HRM/Events/Registrations/Index', [
            'event' => $event->only(['id', 'slug', 'title', 'status', 'capacity']),
            'registrations' => $event->registrations()->with('employee:id,first_name,last_name')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function store(Request $r, HrmEvent $event): RedirectResponse
    {
        $data = $r->validate([
            'attendee_name' => ['required', 'string', 'max:120'],
            'attendee_email' => ['required', 'email', 'max:255'],
            'session_id' => ['nullable', 'exists:hrm_event_sessions,id'],
        ]);

        abort_unless($event->status === HrmEvent::STATUS_PUBLISHED, 422, 'Event is not open for registration.');

        DB::transaction(function () use ($event, $data, $r) {
            if ($event->capacity !== null &&
                $event->registrations()->where('status', HrmEventRegistration::STATUS_REGISTERED)->lockForUpdate()->count() >= $event->capacity
            ) {
                abort(422, 'Event is at capacity.');
            }

            $registration = $event->registrations()->create([
                ...$data,
                'employee_id' => $r->user()?->employee?->id,
                'token' => Str::random(48),
                'status' => HrmEventRegistration::STATUS_REGISTERED,
                'registered_at' => now(),
            ]);
            $this->audit->log(event: 'EVENT_REGISTRATION_CREATED', action: 'register', subject: $registration, description: "Registered {$data['attendee_name']} for event: {$event->title}");
        });

        return back()->with('success', 'Registration successful.');
    }

    public function cancel(Request $r, HrmEventRegistration $registration): RedirectResponse
    {
        abort_if($registration->status === HrmEventRegistration::STATUS_CANCELLED, 422, 'Already cancelled.');

        DB::transaction(function () use ($registration) {
            $registration->update(['status' => HrmEventRegistration::STATUS_CANCELLED, 'cancelled_at' => now()]);
            $this->audit->log(event: 'EVENT_REGISTRATION_CANCELLED', action: 'cancel', subject: $registration, description: "Registration cancelled for {$registration->attendee_name}");
        });

        return back()->with('success', 'Registration cancelled.');
    }

    public function printToken(HrmEventRegistration $registration): Response
    {
        $registration->load('event:id,slug,title,starts_at,ends_at,location');

        return Inertia::render('HRM/Events/Registrations/Token', [
            'registration' => $registration->only(['id', 'attendee_name', 'attendee_email', 'token', 'status', 'registered_at']),
            'event' => $registration->event->only(['id', 'slug', 'title', 'starts_at', 'ends_at', 'location']),
            'tokenUrl' => route('hrm.events.public.show', $registration->event->slug).'?t='.$registration->token,
        ]);
    }
}
