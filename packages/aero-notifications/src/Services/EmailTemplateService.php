<?php

namespace Aero\Notifications\Services;

use Aero\Notifications\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Collection;

/**
 * Email Template Service
 *
 * Core business logic for managing email templates including
 * CRUD operations, rendering, and variable management.
 */
class EmailTemplateService
{
    /**
     * Current tenant id, dual-mode-safe. In SaaS tenant context this is the active
     * tenant; on central/admin or standalone (no stancl tenant() helper / no tenant)
     * it is null — which correctly scopes to the single-tenant/global templates.
     * B-49: the bare tenant('id') 500'd in standalone ("undefined function tenant()").
     */
    private function currentTenantId(): ?string
    {
        return function_exists('tenant') && tenant() ? tenant('id') : null;
    }

    /**
     * Get all email templates for the current tenant.
     */
    public function getTemplates(array $filters = []): Collection
    {
        $query = EmailTemplate::where('tenant_id', $this->currentTenantId());

        if (isset($filters['category'])) {
            $query->category($filters['category']);
        }

        if (isset($filters['is_system'])) {
            $query->where('is_system', $filters['is_system']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->latest()->get();
    }

    /**
     * Get a specific email template by ID.
     */
    public function getTemplate(int $id): ?EmailTemplate
    {
        return EmailTemplate::where('tenant_id', $this->currentTenantId())->findOrFail($id);
    }

    /**
     * Get template by name.
     */
    public function getTemplateByName(string $name): ?EmailTemplate
    {
        return EmailTemplate::where('tenant_id', $this->currentTenantId())
            ->where('name', $name)
            ->active()
            ->first();
    }

    /**
     * Create a new email template.
     */
    public function createTemplate(array $data): EmailTemplate
    {
        $template = EmailTemplate::create([
            'tenant_id' => $this->currentTenantId(),
            'name' => $data['name'],
            'subject' => $data['subject'],
            'html_content' => $data['html_content'],
            'plain_content' => $data['plain_content'] ?? null,
            'variables' => $this->extractVariables($data['html_content'], $data['subject']),
            'category' => $data['category'] ?? 'system',
            'is_system' => $data['is_system'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        return $template;
    }

    /**
     * Update an existing email template.
     */
    public function updateTemplate(EmailTemplate $template, array $data): EmailTemplate
    {
        $updateData = [
            'name' => $data['name'] ?? $template->name,
            'subject' => $data['subject'] ?? $template->subject,
            'html_content' => $data['html_content'] ?? $template->html_content,
            'plain_content' => $data['plain_content'] ?? $template->plain_content,
        ];

        // Update variables if content changed
        if (isset($data['html_content']) || isset($data['subject'])) {
            $updateData['variables'] = $this->extractVariables(
                $data['html_content'] ?? $template->html_content,
                $data['subject'] ?? $template->subject
            );
        }

        if (isset($data['category'])) {
            $updateData['category'] = $data['category'];
        }

        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }

        $template->update($updateData);

        return $template->fresh();
    }

    /**
     * Delete an email template.
     */
    public function deleteTemplate(EmailTemplate $template): void
    {
        $template->delete();
    }

    /**
     * Render a template with provided data.
     */
    public function renderTemplate(EmailTemplate $template, array $data = []): array
    {
        return [
            'subject' => $template->renderSubject($data),
            'html' => $template->render($data),
            'plain' => $template->plain_content ?? strip_tags($template->render($data)),
        ];
    }

    /**
     * Preview a template with test data.
     */
    public function previewTemplate(EmailTemplate $template, array $testData = []): array
    {
        // Provide default test data if none provided
        $defaultTestData = [
            'company_name' => config('app.name', 'aeos365'),
            'user_name' => 'John Doe',
            'email' => 'john@example.com',
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i'),
        ];

        $data = array_merge($defaultTestData, $testData);

        return $this->renderTemplate($template, $data);
    }

    /**
     * Get available template categories.
     */
    public function getCategories(): array
    {
        return [
            'system' => 'System Notifications',
            'marketing' => 'Marketing',
            'transactional' => 'Transactional',
            'onboarding' => 'Onboarding',
            'billing' => 'Billing',
        ];
    }

    /**
     * Extract variables from template content and subject.
     */
    public function extractVariables(string $content, string $subject): array
    {
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $content, $contentMatches);
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $subject, $subjectMatches);

        return array_unique(array_merge($contentMatches[1] ?? [], $subjectMatches[1] ?? []));
    }

    /**
     * Get system default templates (for seeding).
     */
    public function getSystemDefaults(): array
    {
        return [
            [
                'name' => 'quota-warning',
                'subject' => '{{company_name}} - Quota Usage Warning',
                'category' => 'system',
                'is_system' => true,
                'html_content' => '<p>You are using {{percentage}}% of your {{quota_type}} quota. Consider upgrading your plan to avoid service interruption.</p>',
                'variables' => ['company_name', 'percentage', 'quota_type'],
            ],
            [
                'name' => 'quota-critical',
                'subject' => '{{company_name}} - URGENT: Quota Limit Reached',
                'category' => 'system',
                'is_system' => true,
                'html_content' => '<p>You have exceeded your {{quota_type}} quota. Please upgrade to avoid service interruption within {{grace_days}} days.</p>',
                'variables' => ['company_name', 'quota_type', 'grace_days'],
            ],
            [
                'name' => 'trial-expiry',
                'subject' => '{{company_name}} - Your Trial is Ending Soon',
                'category' => 'system',
                'is_system' => true,
                'html_content' => '<p>Your trial expires in {{days_remaining}} days. Subscribe now to continue using all features.</p>',
                'variables' => ['company_name', 'days_remaining'],
            ],
            [
                'name' => 'subscription-renewed',
                'subject' => '{{company_name}} - Subscription Renewed',
                'category' => 'system',
                'is_system' => true,
                'html_content' => '<p>Your subscription has been renewed successfully. Thank you for choosing {{company_name}}!</p>',
                'variables' => ['company_name'],
            ],
            [
                'name' => 'payment-failed',
                'subject' => '{{company_name}} - Payment Failed',
                'category' => 'billing',
                'is_system' => true,
                'html_content' => '<p>We were unable to process your payment. Please update your payment method to avoid service interruption.</p>',
                'variables' => ['company_name'],
            ],
            [
                'name' => 'password-reset',
                'subject' => '{{company_name}} - Password Reset Request',
                'category' => 'system',
                'is_system' => true,
                'html_content' => '<p>You requested a password reset. Click the link below to reset your password:</p><p>{{reset_url}}</p>',
                'variables' => ['company_name', 'reset_url'],
            ],
            [
                'name' => 'email-verification',
                'subject' => '{{company_name}} - Verify Your Email',
                'category' => 'system',
                'is_system' => true,
                'html_content' => '<p>Please verify your email address by clicking the link below:</p><p>{{verification_url}}</p>',
                'variables' => ['company_name', 'verification_url'],
            ],
        ];
    }
}
