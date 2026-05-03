<?php

namespace Aero\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'industry' => ['nullable', 'string', 'max:100'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'fiscal_year_start' => ['nullable', 'date'],
            'fiscal_year_end' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => 'Company Name',
            'legal_name' => 'Legal Name',
            'tax_id' => 'Tax ID',
            'vat_number' => 'VAT Number',
            'registration_number' => 'Registration Number',
            'industry' => 'Industry',
            'website_url' => 'Website URL',
            'email' => 'Email',
            'support_email' => 'Support Email',
            'phone' => 'Phone',
            'mobile_number' => 'Mobile Number',
            'fax' => 'Fax',
            'contact_person' => 'Contact Person',
            'address_line1' => 'Address Line 1',
            'address_line2' => 'Address Line 2',
            'city' => 'City',
            'state' => 'State / Province',
            'postal_code' => 'Postal Code',
            'country' => 'Country',
            'timezone' => 'Timezone',
            'fiscal_year_start' => 'Fiscal Year Start',
            'fiscal_year_end' => 'Fiscal Year End',
        ];
    }
}
