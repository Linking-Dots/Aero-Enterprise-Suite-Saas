<?php

namespace Aero\HRM\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class StoreClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'claim_date' => ['required', 'date'],
            'currency' => ['required', 'string', 'max:8'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'integer', 'exists:hrm_expense_categories,id'],
            'items.*.expense_date' => ['required', 'date'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.description' => ['nullable', 'string'],
            'receipts' => ['nullable', 'array'],
            'receipts.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
