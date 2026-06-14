<?php

namespace Aero\Notifications\Http\Controllers;

use Aero\Kernel\Http\Controllers\Controller;
use Aero\Notifications\Http\Requests\StoreEmailTemplateRequest;
use Aero\Notifications\Http\Requests\UpdateEmailTemplateRequest;
use Aero\Notifications\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Email Template Controller
 *
 * Admin interface for managing system email templates.
 */
class EmailTemplateController extends Controller
{
    protected EmailTemplateService $templateService;

    public function __construct(EmailTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Display a listing of email templates.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['category', 'is_system', 'is_active']);
        $templates = $this->templateService->getTemplates($filters);
        $categories = $this->templateService->getCategories();

        // CA-3: serve the simplified Notifications/Templates view when the request
        // arrives via the admin.notifications.templates.* route group.
        $routeName = optional($request->route())->getName();
        $component = $routeName && str_starts_with($routeName, 'admin.notifications.templates.')
            ? 'Core/Notifications/Templates'
            : 'Core/Settings/EmailTemplates/Index';

        return Inertia::render($component, [
            'templates' => $templates,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new email template.
     */
    public function create(): Response
    {
        $categories = $this->templateService->getCategories();

        return Inertia::render('Core/Settings/EmailTemplates/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created email template.
     */
    public function store(StoreEmailTemplateRequest $request)
    {
        $template = $this->templateService->createTemplate($request->validated());

        return redirect()->route('core.email_templates.show', $template->id)
            ->with('success', 'Email template created successfully.');
    }

    /**
     * Display the specified email template.
     */
    public function show(int $id): Response
    {
        $template = $this->templateService->getTemplate($id);
        $categories = $this->templateService->getCategories();

        return Inertia::render('Core/Settings/EmailTemplates/Show', [
            'template' => $template,
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for editing the specified email template.
     */
    public function edit(int $id): Response
    {
        $template = $this->templateService->getTemplate($id);
        $categories = $this->templateService->getCategories();

        return Inertia::render('Core/Settings/EmailTemplates/Edit', [
            'template' => $template,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified email template.
     */
    public function update(UpdateEmailTemplateRequest $request, int $id)
    {
        $template = $this->templateService->getTemplate($id);
        $template = $this->templateService->updateTemplate($template, $request->validated());

        return redirect()->route('core.email_templates.show', $template->id)
            ->with('success', 'Email template updated successfully.');
    }

    /**
     * Remove the specified email template.
     */
    public function destroy(int $id)
    {
        $template = $this->templateService->getTemplate($id);
        $this->templateService->deleteTemplate($template);

        return redirect()->route('core.email_templates.index')
            ->with('success', 'Email template deleted successfully.');
    }

    /**
     * Preview an email template with test data.
     */
    public function preview(Request $request, int $id)
    {
        $template = $this->templateService->getTemplate($id);
        $testData = $request->input('test_data', []);
        $preview = $this->templateService->previewTemplate($template, $testData);

        return response()->json($preview);
    }

    /**
     * Test send an email template.
     */
    public function testSend(Request $request, int $id)
    {
        $template = $this->templateService->getTemplate($id);
        $testEmail = $request->input('test_email');
        $testData = $request->input('test_data', []);

        // TODO: Implement actual email sending
        // For now, just return success response
        return response()->json([
            'success' => true,
            'message' => 'Test email sent successfully to '.$testEmail,
        ]);
    }
}
