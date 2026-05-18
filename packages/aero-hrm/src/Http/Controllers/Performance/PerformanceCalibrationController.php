<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Performance;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\CalibrationSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceCalibrationController extends Controller
{
    /**
     * List calibration sessions.
     * Gate: hrm.performance.calibration.view
     */
    public function index(): Response
    {
        $this->authorize('hrm.performance.calibration.view');

        $sessions = CalibrationSession::with(['cycle'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('HRM/Performance/Calibration/Index', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Update a calibration session grid / status.
     * Gate: hrm.performance.calibration.manage
     */
    public function update(Request $request, CalibrationSession $session): RedirectResponse
    {
        $this->authorize('hrm.performance.calibration.manage');

        $validated = $request->validate([
            'grid' => ['nullable', 'array'],
            'name' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', 'string', 'in:draft,active,closed'],
        ]);

        DB::transaction(function () use ($session, $validated): void {
            $session->update($validated);
        });

        return back()->with('success', 'Calibration session updated.');
    }
}
