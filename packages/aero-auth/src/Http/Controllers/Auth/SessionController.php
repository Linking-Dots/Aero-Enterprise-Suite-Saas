<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Auth\Services\SessionManagementService;
use Aero\Auth\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function __construct(
        private readonly SessionManagementService $sessionService
    ) {}

    /**
     * Display the active sessions management page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sessions = $this->sessionService->getUserSessions($user);
        $currentSession = UserSession::where('user_id', $user->id)
            ->where('is_current', true)
            ->first();

        return Inertia::render('Auth/Sessions', [
            'title' => 'Active Sessions',
            'sessions' => $sessions,
            'current_session_id' => $currentSession?->id,
            'max_sessions' => config('auth.max_sessions', 5),
        ]);
    }

    /**
     * Get sessions as JSON (for live refresh).
     */
    public function paginate(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessions = $this->sessionService->getUserSessions($user);

        return response()->json([
            'sessions' => $sessions,
            'total' => count($sessions),
        ]);
    }

    /**
     * Terminate a specific session.
     */
    public function terminate(Request $request, int $sessionId): JsonResponse
    {
        $user = $request->user();

        $this->sessionService->terminateSession($user, $sessionId);

        return response()->json([
            'message' => 'Session terminated successfully.',
        ]);
    }

    /**
     * Terminate all sessions except the current one.
     */
    public function terminateAll(Request $request): JsonResponse
    {
        $user = $request->user();

        $currentSession = UserSession::where('user_id', $user->id)
            ->where('is_current', true)
            ->first();

        if ($currentSession) {
            $this->sessionService->terminateOtherSessions($user, $currentSession->id);
        }

        return response()->json([
            'message' => 'All other sessions terminated successfully.',
        ]);
    }
}
