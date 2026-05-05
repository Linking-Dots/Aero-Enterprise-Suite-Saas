<?php

declare(strict_types=1);

namespace Aero\Forms\Http\Controllers;

use Aero\Core\Http\Controllers\Controller;
use Aero\Forms\Models\Form;
use Aero\Forms\Models\FormSubmission;
use Aero\Forms\Services\FormBuilderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionController extends Controller
{
    public function __construct(
        private FormBuilderService $service
    ) {}

    /**
     * Display submissions list for a form.
     */
    public function index(Request $request, Form $form): Response
    {
        $this->authorizeForm($form);

        $status = $request->get('status');
        $submissions = $this->service->getSubmissions($form, $status, 25);

        return Inertia::render('Core/Forms/Submissions', [
            'title' => "Submissions - {$form->name}",
            'form' => $form,
            'submissions' => $submissions,
            'status' => $status,
        ]);
    }

    /**
     * Show a specific submission.
     */
    public function show(Form $form, FormSubmission $submission): JsonResponse
    {
        $this->authorizeForm($form);
        $this->authorizeSubmission($form, $submission);

        return response()->json($submission);
    }

    /**
     * Update submission status.
     */
    public function update(Request $request, Form $form, FormSubmission $submission)
    {
        $this->authorizeForm($form);
        $this->authorizeSubmission($form, $submission);

        $validated = $request->validate([
            'status' => 'required|in:submitted,reviewed,archived',
            'notes' => 'nullable|string',
        ]);

        $submission = $this->service->updateSubmissionStatus(
            $submission,
            $validated['status'],
            $validated['notes'] ?? null
        );

        return redirect()->route('core.forms.submissions.index', $form->id)
            ->with('success', 'Submission updated successfully.');
    }

    /**
     * Delete a submission.
     */
    public function destroy(Form $form, FormSubmission $submission)
    {
        $this->authorizeForm($form);
        $this->authorizeSubmission($form, $submission);

        $this->service->deleteSubmission($submission);

        return redirect()->route('core.forms.submissions.index', $form->id)
            ->with('success', 'Submission deleted successfully.');
    }

    /**
     * Export submissions to CSV.
     */
    public function export(Form $form): StreamedResponse
    {
        $this->authorizeForm($form);

        $csv = $this->service->exportSubmissions($form);

        return new StreamedResponse(function () use ($csv) {
            echo $csv;
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="submissions-' . $form->id . '.csv"',
        ]);
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

    /**
     * Authorize that the submission belongs to the form.
     */
    private function authorizeSubmission(Form $form, FormSubmission $submission): void
    {
        if ($submission->form_id !== $form->id) {
            abort(404, 'Submission not found.');
        }
    }
}
