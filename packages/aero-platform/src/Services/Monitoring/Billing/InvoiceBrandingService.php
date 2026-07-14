<?php

namespace Aero\Platform\Services\Monitoring\Billing;

use Aero\Platform\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InvoiceBrandingService
{
    /**
     * Default branding configuration.
     */
    protected array $defaultBranding = [
        'company_name' => null,
        'logo_url' => null,
        'logo_path' => null,
        'primary_color' => '#2563eb',
        'secondary_color' => '#1e40af',
        'accent_color' => '#6366f1',
        'font_family' => 'Helvetica, Arial, sans-serif',
        'logo_position' => 'left', // left, center, right
        'show_watermark' => false,
        'address' => null,
        'phone' => null,
        'email' => null,
        'website' => null,
        'tax_id' => null,
        'bank_name' => null,
        'account_number' => null,
        'routing_number' => null,
        'swift_code' => null,
        'payment_instructions' => null,
        'thank_you_message' => 'Thank you for your business!',
        'footer_text' => null,
        'terms' => null,
    ];

    /**
     * Get branding configuration for a tenant.
     */
    public function getTenantBranding(?Tenant $tenant = null): array
    {
        if (! $tenant) {
            return $this->defaultBranding;
        }

        $tenantBranding = $tenant->data['invoice_branding'] ?? [];

        return array_merge($this->defaultBranding, $tenantBranding);
    }

    /**
     * Get the logo URL for a tenant.
     */
    public function getTenantLogo(?Tenant $tenant = null): ?string
    {
        if (! $tenant) {
            return null;
        }

        // Check for tenant-specific invoice logo
        $invoiceLogo = $tenant->data['invoice_branding']['logo_path'] ?? null;
        if ($invoiceLogo && Storage::disk('public')->exists($invoiceLogo)) {
            return Storage::disk('public')->url($invoiceLogo);
        }

        // Fall back to system settings logo
        $systemLogo = $tenant->data['branding']['logo_light'] ?? null;
        if ($systemLogo && Storage::disk('public')->exists($systemLogo)) {
            return Storage::disk('public')->url($systemLogo);
        }

        // Fall back to the white-label chain's central per-tenant layer
        // (managed from the /white-label console).
        try {
            $central = \Aero\Platform\Models\Infra\TenantBranding::query()
                ->where('tenant_id', $tenant->id)
                ->first();
            $path = $central?->logo_path;
            if ($path && Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        } catch (\Throwable) {
            // Central branding is optional — never block invoice rendering.
        }

        return null;
    }

    /**
     * Get tenant organization info for invoice.
     */
    public function getOrganizationInfo(?Tenant $tenant = null): array
    {
        if (! $tenant) {
            return [
                'company_name' => config('app.name'),
                'legal_name' => config('app.name'),
                'address' => null,
                'phone' => null,
                'email' => null,
                'website' => config('app.url'),
                'tax_id' => null,
                'registration_number' => null,
            ];
        }

        $org = $tenant->data['organization'] ?? [];

        return [
            'company_name' => $org['company_name'] ?? $tenant->name,
            'legal_name' => $org['legal_name'] ?? $tenant->name,
            'address' => $this->formatAddress($org),
            'phone' => $org['support_phone'] ?? null,
            'email' => $org['support_email'] ?? null,
            'website' => $org['website_url'] ?? null,
            'tax_id' => $org['tax_id'] ?? null,
            'registration_number' => $org['registration_number'] ?? null,
        ];
    }

    /**
     * Format the address from organization data.
     */
    protected function formatAddress(array $org): ?string
    {
        $parts = array_filter([
            $org['address_line1'] ?? null,
            $org['address_line2'] ?? null,
            implode(', ', array_filter([
                $org['city'] ?? null,
                $org['state'] ?? null,
                $org['postal_code'] ?? null,
            ])),
            $org['country'] ?? null,
        ]);

        return ! empty($parts) ? implode("\n", $parts) : null;
    }

    /**
     * Save tenant invoice branding configuration.
     */
    public function saveTenantBranding(Tenant $tenant, array $branding): bool
    {
        $data = $tenant->data;
        $data['invoice_branding'] = array_merge(
            $data['invoice_branding'] ?? [],
            $branding
        );
        $tenant->data = $data;

        return $tenant->save();
    }

    /**
     * Upload and save invoice logo for a tenant.
     */
    public function uploadInvoiceLogo(Tenant $tenant, UploadedFile $logo): string
    {
        // Delete old logo if exists
        $oldLogo = $tenant->data['invoice_branding']['logo_path'] ?? null;
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        // Store new logo
        $path = $logo->store("tenants/{$tenant->id}/branding", 'public');

        // Update tenant data
        $data = $tenant->data;
        $data['invoice_branding']['logo_path'] = $path;
        $tenant->data = $data;
        $tenant->save();

        return Storage::disk('public')->url($path);
    }

    /**
     * Generate invoice PDF with tenant branding.
     */
    public function generateBrandedInvoicePdf(array $invoiceData, ?Tenant $tenant = null): \Barryvdh\DomPDF\PDF
    {
        $branding = $this->getTenantBranding($tenant);
        $organization = $this->getOrganizationInfo($tenant);
        $logoUrl = $this->getTenantLogo($tenant);

        $data = array_merge($invoiceData, [
            'branding' => $branding,
            'organization' => $organization,
            'logo_url' => $logoUrl,
        ]);

        $pdf = Pdf::loadView('pdf.invoice-branded', $data);

        // Apply PDF settings
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption([
            'dpi' => 150,
            'defaultFont' => 'sans-serif',
            'isRemoteEnabled' => true,
        ]);

        return $pdf;
    }

    /**
     * Get available invoice templates.
     */
    public function getAvailableTemplates(): array
    {
        return [
            'default' => [
                'name' => 'Standard Invoice',
                'description' => 'Clean, professional invoice layout',
                'preview' => '/assets/templates/invoice-default.png',
            ],
            'modern' => [
                'name' => 'Modern Invoice',
                'description' => 'Contemporary design with accent colors',
                'preview' => '/assets/templates/invoice-modern.png',
            ],
            'minimal' => [
                'name' => 'Minimal Invoice',
                'description' => 'Simple, text-focused design',
                'preview' => '/assets/templates/invoice-minimal.png',
            ],
            'detailed' => [
                'name' => 'Detailed Invoice',
                'description' => 'Comprehensive layout with all details',
                'preview' => '/assets/templates/invoice-detailed.png',
            ],
        ];
    }

    /**
     * Get the active template for a tenant.
     */
    public function getActiveTemplate(?Tenant $tenant = null): string
    {
        if ($tenant) {
            return $tenant->data['invoice_branding']['template'] ?? 'default';
        }

        return 'default';
    }

    /**
     * Set the active template for a tenant.
     */
    public function setActiveTemplate(Tenant $tenant, string $template): bool
    {
        $availableTemplates = array_keys($this->getAvailableTemplates());
        if (! in_array($template, $availableTemplates)) {
            throw new \InvalidArgumentException("Invalid template: {$template}");
        }

        $data = $tenant->data;
        $data['invoice_branding']['template'] = $template;
        $tenant->data = $data;

        return $tenant->save();
    }

    /**
     * Generate branded invoice PDF.
     */
    public function generateBrandedInvoice(array $invoice, ?Tenant $tenant = null): string
    {
        $branding = $this->getTenantBranding($tenant);

        // Merge logo URL if available
        if (! isset($branding['logo_url'])) {
            $branding['logo_url'] = $this->getTenantLogo($tenant);
        }

        // Merge organization info
        $org = $this->getOrganizationInfo($tenant);
        $branding = array_merge($branding, [
            'company_name' => $branding['company_name'] ?? $org['company_name'],
            'address' => $branding['address'] ?? $org['address'],
            'phone' => $branding['phone'] ?? $org['phone'],
            'email' => $branding['email'] ?? $org['email'],
            'website' => $branding['website'] ?? $org['website'],
        ]);

        $pdf = Pdf::loadView('invoices.branded', [
            'invoice' => $invoice,
            'branding' => $branding,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption([
            'dpi' => 150,
            'defaultFont' => 'sans-serif',
            'isRemoteEnabled' => true,
        ]);

        return $pdf->output();
    }

    /**
     * Generate branded receipt PDF.
     */
    public function generateBrandedReceipt(array $receipt, ?Tenant $tenant = null): string
    {
        $branding = $this->getTenantBranding($tenant);

        // Merge logo URL if available
        if (! isset($branding['logo_url'])) {
            $branding['logo_url'] = $this->getTenantLogo($tenant);
        }

        // Merge organization info
        $org = $this->getOrganizationInfo($tenant);
        $branding = array_merge($branding, [
            'company_name' => $branding['company_name'] ?? $org['company_name'],
            'address' => $branding['address'] ?? $org['address'],
            'phone' => $branding['phone'] ?? $org['phone'],
            'email' => $branding['email'] ?? $org['email'],
            'website' => $branding['website'] ?? $org['website'],
        ]);

        $pdf = Pdf::loadView('invoices.receipt', [
            'receipt' => $receipt,
            'branding' => $branding,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption([
            'dpi' => 150,
            'defaultFont' => 'sans-serif',
            'isRemoteEnabled' => true,
        ]);

        return $pdf->output();
    }
}
