<?php

namespace Aero\HRM\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class RejectClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
