<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Settings;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\HrmTaskTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class HrmTaskTemplateController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(): Response
    {
        return Inertia::render('HRM/Settings/TaskTemplates/Index', [
            'templates' => HrmTaskTemplate::orderBy('type')
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:onboarding,offboarding'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tasks' => ['required', 'array', 'min:1'],
            'tasks.*.title' => ['required', 'string', 'max:255'],
            'tasks.*.due_days' => ['required', 'integer', 'min:0'],
            'tasks.*.assignee_type' => ['required', 'string', 'in:employee,manager,hr'],
        ]);

        DB::transaction(function () use ($validated) {
            $template = HrmTaskTemplate::create($validated);

            $this->audit->log(
                event: 'SETTINGS_UPDATED',
                action: 'create',
                subject: null,
                description: "Created task template: {$template->name} ({$template->type})",
            );
        });

        return back()->with('success', 'Task template created.');
    }

    public function update(Request $request, HrmTaskTemplate $taskTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:onboarding,offboarding'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tasks' => ['required', 'array', 'min:1'],
            'tasks.*.title' => ['required', 'string', 'max:255'],
            'tasks.*.due_days' => ['required', 'integer', 'min:0'],
            'tasks.*.assignee_type' => ['required', 'string', 'in:employee,manager,hr'],
            'active' => ['boolean'],
        ]);

        DB::transaction(function () use ($validated, $taskTemplate) {
            $taskTemplate->update($validated);

            $this->audit->log(
                event: 'SETTINGS_UPDATED',
                action: 'update',
                subject: null,
                description: "Updated task template: {$taskTemplate->name} (ID {$taskTemplate->id})",
            );
        });

        return back()->with('success', 'Task template updated.');
    }

    public function destroy(HrmTaskTemplate $taskTemplate): RedirectResponse
    {
        DB::transaction(function () use ($taskTemplate) {
            $this->audit->log(
                event: 'SETTINGS_UPDATED',
                action: 'delete',
                subject: null,
                description: "Deleted task template: {$taskTemplate->name} (ID {$taskTemplate->id})",
            );

            $taskTemplate->delete();
        });

        return back()->with('success', 'Task template deleted.');
    }
}
