<?php

namespace Aero\HRM\Http\Requests\Asset;

use Aero\HRM\Models\HrmAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tag' => ['required', 'string', 'max:64', 'unique:hrm_assets,tag'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:hrm_asset_categories,id'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'purchased_on' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in([
                HrmAsset::STATUS_AVAILABLE,
                HrmAsset::STATUS_ALLOCATED,
                HrmAsset::STATUS_MAINTENANCE,
                HrmAsset::STATUS_RETIRED,
            ])],
            'notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
