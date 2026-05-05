<?php

declare(strict_types=1);

namespace Aero\Forms\Services;

use Aero\Forms\Models\Form;
use Aero\Forms\Models\FormSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Form Builder Service
 *
 * Core business logic for managing forms and submissions.
 */
class FormBuilderService
{
    /**
     * Create a new form.
     */
    public function createForm(array $data): Form
    {
        return Form::create([
            'user_id' => Auth::id(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'schema' => $data['schema'],
            'conditional_logic' => $data['conditional_logic'] ?? null,
            'is_published' => $data['is_published'] ?? false,
            'allow_multiple_submissions' => $data['allow_multiple_submissions'] ?? true,
            'require_authentication' => $data['require_authentication'] ?? false,
            'expires_at' => $data['expires_at'] ?? null,
            'max_submissions' => $data['max_submissions'] ?? null,
            'success_message' => $data['success_message'] ?? null,
            'redirect_url' => $data['redirect_url'] ?? null,
        ]);
    }

    /**
     * Update an existing form.
     */
    public function updateForm(Form $form, array $data): Form
    {
        $form->update($data);
        return $form->fresh();
    }

    /**
     * Publish a form.
     */
    public function publishForm(Form $form): Form
    {
        $form->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        return $form->fresh();
    }

    /**
     * Unpublish a form.
     */
    public function unpublishForm(Form $form): Form
    {
        $form->update([
            'is_published' => false,
        ]);
        return $form->fresh();
    }

    /**
     * Delete a form.
     */
    public function deleteForm(Form $form): bool
    {
        return $form->delete();
    }

    /**
     * Validate form submission data against form schema.
     */
    public function validateSubmission(Form $form, array $data): array
    {
        $rules = $this->buildValidationRules($form->schema);
        
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Build validation rules from form schema.
     */
    protected function buildValidationRules(array $schema): array
    {
        $rules = [];
        
        foreach ($schema as $field) {
            if (!isset($field['name']) || !isset($field['type'])) {
                continue;
            }

            $fieldRules = [];
            
            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // Add type-specific rules
            switch ($field['type']) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'file':
                    $fieldRules[] = 'file';
                    if (isset($field['max_size'])) {
                        $fieldRules[] = 'max:' . $field['max_size'];
                    }
                    if (isset($field['allowed_types'])) {
                        $fieldRules[] = 'mimes:' . implode(',', $field['allowed_types']);
                    }
                    break;
            }

            // Add custom validation rules
            if (isset($field['validation'])) {
                if (is_array($field['validation'])) {
                    $fieldRules = array_merge($fieldRules, $field['validation']);
                } else {
                    $fieldRules[] = $field['validation'];
                }
            }

            if (!empty($fieldRules)) {
                $rules[$field['name']] = implode('|', $fieldRules);
            }
        }

        return $rules;
    }

    /**
     * Submit a form.
     */
    public function submitForm(Form $form, array $data, ?string $ipAddress = null, ?string $userAgent = null): FormSubmission
    {
        // Check if form is accepting submissions
        if (!$form->isAcceptingSubmissions()) {
            throw new \Exception('Form is not accepting submissions.');
        }

        // Check authentication requirement
        if ($form->require_authentication && !Auth::check()) {
            throw new \Exception('Authentication required for this form.');
        }

        // Check multiple submissions
        if (!$form->allow_multiple_submissions && Auth::check()) {
            $existing = $form->submissions()->where('user_id', Auth::id())->exists();
            if ($existing) {
                throw new \Exception('You have already submitted this form.');
            }
        }

        // Validate submission
        $validation = $this->validateSubmission($form, $data);
        if (!$validation['valid']) {
            throw new \ValidationException('Validation failed', $validation['errors']);
        }

        return FormSubmission::create([
            'form_id' => $form->id,
            'user_id' => Auth::id(),
            'data' => $data,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'status' => 'submitted',
        ]);
    }

    /**
     * Get form submissions.
     */
    public function getSubmissions(Form $form, ?string $status = null, ?int $perPage = null)
    {
        $query = $form->submissions();

        if ($status) {
            $query->where('status', $status);
        }

        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    /**
     * Export form submissions to CSV.
     */
    public function exportSubmissions(Form $form): string
    {
        $submissions = $form->submissions()->get();
        
        $headers = ['ID', 'Submitted At', 'User', 'Status'];
        $rows = [];

        foreach ($submissions as $submission) {
            $row = [
                $submission->id,
                $submission->created_at->format('Y-m-d H:i:s'),
                $submission->user?->name ?? 'Guest',
                $submission->status,
            ];

            // Add form data fields
            foreach ($submission->data as $key => $value) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
                $row[] = is_array($value) ? json_encode($value) : $value;
            }

            $rows[] = $row;
        }

        $csv = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        return $csv;
    }

    /**
     * Delete a form submission.
     */
    public function deleteSubmission(FormSubmission $submission): bool
    {
        return $submission->delete();
    }

    /**
     * Update submission status.
     */
    public function updateSubmissionStatus(FormSubmission $submission, string $status, ?string $notes = null): FormSubmission
    {
        $submission->update([
            'status' => $status,
            'notes' => $notes ?? $submission->notes,
        ]);
        return $submission->fresh();
    }
}
