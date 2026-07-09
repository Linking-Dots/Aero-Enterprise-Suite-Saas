<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\OrganizationProfile;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationProfileController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    private function getOrCreate(): OrganizationProfile
    {
        return OrganizationProfile::firstOrCreate([]);
    }

    /**
     * Unified Organization command-center payload. All five nav-component GET
     * routes render the same `Core/Organization/Index` page; `section` selects
     * the initially-active in-page tab so deep links keep working. The five POST
     * update endpoints below are unchanged — each section saves independently.
     */
    private function commandCenter(string $section): Response
    {
        $org = $this->getOrCreate();

        return Inertia::render('Core/Organization/Index', [
            'org' => $org->only([
                'company_name', 'legal_name', 'registration_number', 'industry',
                'company_size', 'website', 'phone', 'email', 'logo_path',
                'tax_id', 'vat_number', 'country', 'currency',
                'fiscal_year_start', 'fiscal_year_end', 'timezone', 'date_format',
            ]),
            'addresses' => $org->addresses ?? [],
            'contacts' => $org->contacts ?? [],
            'section' => $section,
        ]);
    }

    public function profile(): Response
    {
        return $this->commandCenter('profile');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['sometimes', 'string', 'max:255'],
            'legal_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'registration_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'industry' => ['sometimes', 'nullable', 'string'],
            'company_size' => ['sometimes', 'nullable', 'string'],
            'website' => ['sometimes', 'nullable', 'url'],
            'phone' => ['sometimes', 'nullable', 'string'],
            'email' => ['sometimes', 'nullable', 'email'],
        ]);

        DB::transaction(function () use ($data) {
            $org = $this->getOrCreate();
            $org->update($data);
            $this->audit->log(
                AuditEventType::SETTINGS_UPDATED->value,
                'updated',
                $org,
                'Organization profile updated',
                null,
                null,
                ['section' => 'org_profile']
            );
        });

        return back()->with('success', 'Organization profile updated.');
    }

    public function identity(): Response
    {
        return $this->commandCenter('identity');
    }

    public function updateIdentity(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tax_id' => ['sometimes', 'nullable', 'string'],
            'vat_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
        ]);

        DB::transaction(function () use ($data) {
            $org = $this->getOrCreate();
            $org->update($data);
            $this->audit->log(
                AuditEventType::SETTINGS_UPDATED->value,
                'updated',
                $org,
                'Tax/legal identity updated',
                null,
                null,
                ['section' => 'org_identity']
            );
        });

        return back()->with('success', 'Tax/legal identity updated.');
    }

    public function addresses(): Response
    {
        return $this->commandCenter('addresses');
    }

    public function updateAddresses(Request $request): RedirectResponse
    {
        $request->validate([
            'addresses' => ['required', 'array'],
            'addresses.*.type' => ['required', 'in:billing,shipping,office,other'],
            'addresses.*.line1' => ['required', 'string'],
            'addresses.*.city' => ['required', 'string'],
            'addresses.*.country' => ['required', 'string', 'size:2'],
            'addresses.*.is_primary' => ['boolean'],
        ]);

        $org = $this->getOrCreate();
        $org->update(['addresses' => $request->addresses]);

        $this->audit->log(
            AuditEventType::SETTINGS_UPDATED->value,
            'updated',
            $org,
            'Addresses updated',
            null,
            null,
            ['section' => 'org_addresses']
        );

        return back()->with('success', 'Addresses updated.');
    }

    public function fiscalYear(): Response
    {
        return $this->commandCenter('fiscal');
    }

    public function updateFiscalYear(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fiscal_year_start' => ['required', 'string', 'regex:/^\d{2}-\d{2}$/'],
            'fiscal_year_end' => ['required', 'string', 'regex:/^\d{2}-\d{2}$/'],
            'timezone' => ['required', 'string', 'timezone'],
            'date_format' => ['required', 'string'],
        ]);

        $org = $this->getOrCreate();
        $org->update($data);

        $this->audit->log(
            AuditEventType::SETTINGS_UPDATED->value,
            'updated',
            $org,
            'Fiscal year updated',
            null,
            null,
            ['section' => 'fiscal_year']
        );

        return back()->with('success', 'Fiscal year updated.');
    }

    public function contacts(): Response
    {
        return $this->commandCenter('contacts');
    }

    public function updateContacts(Request $request): RedirectResponse
    {
        $request->validate([
            'contacts' => ['required', 'array'],
            'contacts.*.name' => ['required', 'string'],
            'contacts.*.email' => ['required', 'email'],
            'contacts.*.role' => ['required', 'string'],
            'contacts.*.phone' => ['nullable', 'string'],
            'contacts.*.is_primary' => ['boolean'],
        ]);

        $org = $this->getOrCreate();
        $org->update(['contacts' => $request->contacts]);

        $this->audit->log(
            AuditEventType::SETTINGS_UPDATED->value,
            'updated',
            $org,
            'Contacts updated',
            null,
            null,
            ['section' => 'org_contacts']
        );

        return back()->with('success', 'Contacts updated.');
    }
}
