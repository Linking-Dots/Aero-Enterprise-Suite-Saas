<?php

declare(strict_types=1);

namespace Aero\Forms\Http\Controllers;

use Aero\Core\Http\Controllers\Controller;
use Aero\Forms\Models\Form;
use Aero\Forms\Services\FormBuilderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormController extends Controller
{
    public function __construct(
        private FormBuilderService $service
    ) {}

    /**
     * Display forms list.
     */
    public function index(): Response
    {
        $forms = Form::byUser(auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Core/Forms/Index', [
            'title' => 'Forms',
            'forms' => $forms,
        ]);
    }

    /**
     * Show form builder for creating a new form.
     */
    public function create(): Response
    {
        return Inertia::render('Core/Forms/Create', [
            'title' => 'Create Form',
        ]);
    }

    /**
     * Store a new form.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schema' => 'required|array',
            'conditional_logic' => 'nullable|array',
            'allow_multiple_submissions' => 'boolean',
            'require_authentication' => 'boolean',
            'expires_at' => 'nullable|date',
            'max_submissions' => 'nullable|integer',
            'success_message' => 'nullable|string',
            'redirect_url' => 'nullable|url',
        ]);

        $form = $this->service->createForm($validated);

        return redirect()->route('core.forms.edit', $form->id)
            ->with('success', "Form '{$form->name}' created successfully.");
    }

    /**
     * Show form builder for editing a form.
     */
    public function edit(Form $form): Response
    {
        $this->authorizeForm($form);

        return Inertia::render('Core/Forms/Edit', [
            'title' => "Edit {$form->name}",
            'form' => $form,
        ]);
    }

    /**
     * Update a form.
     */
    public function update(Request $request, Form $form)
    {
        $this->authorizeForm($form);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'schema' => 'sometimes|array',
            'conditional_logic' => 'nullable|array',
            'allow_multiple_submissions' => 'sometimes|boolean',
            'require_authentication' => 'sometimes|boolean',
            'expires_at' => 'nullable|date',
            'max_submissions' => 'nullable|integer',
            'success_message' => 'nullable|string',
            'redirect_url' => 'nullable|url',
        ]);

        $form = $this->service->updateForm($form, $validated);

        return redirect()->route('core.forms.edit', $form->id)
            ->with('success', "Form '{$form->name}' updated successfully.");
    }

    /**
     * Publish a form.
     */
    public function publish(Form $form)
    {
        $this->authorizeForm($form);

        $form = $this->service->publishForm($form);

        return redirect()->route('core.forms.edit', $form->id)
            ->with('success', "Form '{$form->name}' published successfully.");
    }

    /**
     * Unpublish a form.
     */
    public function unpublish(Form $form)
    {
        $this->authorizeForm($form);

        $form = $this->service->unpublishForm($form);

        return redirect()->route('core.forms.edit', $form->id)
            ->with('success', "Form '{$form->name}' unpublished successfully.");
    }

    /**
     * Delete a form.
     */
    public function destroy(Form $form)
    {
        $this->authorizeForm($form);

        $name = $form->name;
        $this->service->deleteForm($form);

        return redirect()->route('core.forms.index')
            ->with('success', "Form '{$name}' deleted successfully.");
    }

    /**
     * Authorize that the user can access the form.
     */
    private function authorizeForm(Form $form): void
    {
        if ($form->user_id !== auth()->id()) {
            abort(403, 'You do not have permission to access this form.');
        }
    }
}
