<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Auth\Models\User;
use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\PlatformUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Platform-scoped Users command centre. Owns the read surface (overview + drawer
 * detail) and the security operations that aren't part of the shared aero-auth
 * user CRUD (lock/unlock, force-password-reset, revoke-sessions, reset-2FA).
 * All create/update/delete/role/impersonate/bulk writes continue to route
 * through the shared UserAdminController.
 */
class PlatformUserController extends Controller
{
    public function __construct(
        private readonly PlatformUserService $svc,
        private readonly AuditServiceInterface $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Users/P2/Index', [
            ...$this->svc->overview(),
            'roles' => DB::table('roles')->where('guard_name', 'landlord')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Drawer payload: sessions + audit activity. */
    public function detail(int $id): JsonResponse
    {
        User::withTrashed()->findOrFail($id);

        return response()->json($this->svc->detail($id));
    }

    /** Lock or unlock a staff account (clears/sets account_locked_at). */
    public function toggleLock(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $locked = $user->account_locked_at !== null;

        $user->forceFill([
            'account_locked_at' => $locked ? null : now(),
            'locked_reason' => $locked ? null : 'Locked by administrator',
        ])->save();

        $this->audit->log(
            event: 'auth.user.'.($locked ? 'unlocked' : 'locked'),
            action: $locked ? 'unlocked' : 'locked',
            subject: $user,
            description: "Account for '{$user->email}' ".($locked ? 'unlocked.' : 'locked.'),
        );

        return back()->with('success', $locked ? 'Account unlocked.' : 'Account locked.');
    }

    /** Require the user to set a new password on next sign-in. */
    public function forcePasswordReset(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->forceFill(['force_password_reset' => true])->save();

        $this->audit->log(
            event: 'auth.user.force_password_reset',
            action: 'force_password_reset',
            subject: $user,
            description: "Forced password reset for '{$user->email}'.",
        );

        return back()->with('success', 'Password reset required on next sign-in.');
    }

    /** Sign the user out of every device by clearing their sessions. */
    public function revokeSessions(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        $deleted = 0;
        try {
            $deleted = DB::table('sessions')->where('user_id', $user->id)->delete();
        } catch (\Illuminate\Database\QueryException) {
            // sessions table absent — nothing to revoke
        }

        $this->audit->log(
            event: 'auth.user.sessions_revoked',
            action: 'sessions_revoked',
            subject: $user,
            description: "Revoked {$deleted} session(s) for '{$user->email}'.",
        );

        return back()->with('success', "Revoked {$deleted} session(s).");
    }

    /** Clear the user's two-factor secret so they must re-enrol. */
    public function resetTwoFactor(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->audit->log(
            event: 'auth.user.two_factor_reset',
            action: 'two_factor_reset',
            subject: $user,
            description: "Reset two-factor for '{$user->email}'.",
        );

        return back()->with('success', 'Two-factor reset — the user must re-enrol.');
    }

    /** Stream every staff account as CSV. */
    public function export(): StreamedResponse
    {
        $rows = $this->svc->exportRows();
        $filename = 'platform-users-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Roles', 'Status', '2FA', 'Last login', 'Logins', 'Joined']);
            foreach ($rows as $r) {
                fputcsv($out, array_values($r));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
