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

    public function profile(): Response
    {
        return Inertia::render('Core/Organization/Profile', ['org' => $this->getOrCreate()]);
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
        return Inertia::render('Core/Organization/Identity', ['org' => $this->getOrCreate()]);
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
        $org = $this->getOrCreate();

        return Inertia::render('Core/Organization/Addresses', ['addresses' => $org->addresses ?? []]);
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
        return Inertia::render('Core/Organization/FiscalYear', ['org' => $this->getOrCreate()]);
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
        $org = $this->getOrCreate();

        return Inertia::render('Core/Organization/Contacts', ['contacts' => $org->contacts ?? []]);
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
