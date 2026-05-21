<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Requests\Admin\AdvancedBilling;

use Aero\Platform\Models\DunningRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertDunningRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'day_offset' => ['required', 'integer', 'min:0', 'max:90'],
            'action' => ['required', Rule::in([DunningRule::ACTION_RETRY, DunningRule::ACTION_EMAIL, DunningRule::ACTION_SUSPEND, DunningRule::ACTION_MARK_UNPAID])],
            'email_template_id' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
            'order_index' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
