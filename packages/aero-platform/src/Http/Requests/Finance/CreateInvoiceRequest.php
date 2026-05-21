<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'billable_type' => ['required', 'string', Rule::in([
                'Aero\\Platform\\Models\\Subscription',
                'Aero\\Platform\\Models\\ProductSubscription',
            ])],
            'billable_id' => ['required', 'string', 'max:36'],
            'currency' => ['required', 'string', 'size:3'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'billing_period_start' => ['sometimes', 'date'],
            'billing_period_end' => ['sometimes', 'date', 'after_or_equal:billing_period_start'],
            'due_date' => ['sometimes', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
