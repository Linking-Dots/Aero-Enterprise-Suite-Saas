<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\EmailTemplate;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): Response
    {
        return Inertia::render('Core/Settings/Index', [
            'section' => 'templates',
            'summary' => \Aero\Core\Services\SettingsSummary::build(),
            'templates' => EmailTemplate::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'unique:email_templates,slug'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'body_text' => ['nullable', 'string'],
            'category' => ['required', 'in:system,transactional,marketing'],
            'variables' => ['nullable', 'array'],
        ]);

        $template = EmailTemplate::create($data);

        $this->audit->log(
            AuditEventType::RECORD_CREATED->value,
            'created',
            $template,
            'Email template created'
        );

        return back()->with('success', 'Template created.');
    }

    /**
     * Resolve the template manually. On tenant subdomains the FIRST route
     * parameter is {tenant}; a scalar-typed method arg gets that positionally
     * (e.g. 'democorp'), and model binding is unreliable here — so we read the
     * {template} route key explicitly and bind on the tenant connection.
     */
    private function resolveTemplate(): EmailTemplate
    {
        return EmailTemplate::findOrFail(request()->route('template'));
    }

    public function update(Request $request): RedirectResponse
    {
        $template = $this->resolveTemplate();

        abort_if(
            $template->is_locked && $request->has('slug'),
            403,
            'Cannot modify locked template slug.'
        );

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'body_html' => ['sometimes', 'required', 'string'],
            'body_text' => ['nullable', 'string'],
            'category' => ['sometimes', 'required', 'in:system,transactional,marketing'],
            'is_active' => ['boolean'],
        ]);

        $template->update($data);

        $this->audit->log(
            AuditEventType::RECORD_UPDATED->value,
            'updated',
            $template,
            'Email template updated'
        );

        return back()->with('success', 'Template updated.');
    }

    public function destroy(): RedirectResponse
    {
        $template = $this->resolveTemplate();

        abort_if($template->is_locked, 403, 'Cannot delete a locked system template.');

        $this->audit->log(
            AuditEventType::RECORD_DELETED->value,
            'deleted',
            $template,
            'Email template deleted'
        );

        $template->delete();

        return back()->with('success', 'Template deleted.');
    }

    public function preview(): HttpResponse
    {
        $template = $this->resolveTemplate();

        return response($template->body_html)->header('Content-Type', 'text/html');
    }
}
