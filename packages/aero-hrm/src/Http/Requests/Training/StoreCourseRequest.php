<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hrmac', 'hrm.training.training-programs.create');
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:training_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'delivery_mode' => ['required', 'string', 'in:in_person,virtual,self_paced'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'learning_objectives' => ['nullable', 'array'],
            'learning_objectives.*' => ['string'],
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'is_mandatory' => ['boolean'],
            'is_active' => ['boolean'],
            'materials' => ['nullable', 'array'],
            'materials.*.kind' => ['required', 'string', 'in:link,file'],
            'materials.*.label' => ['required', 'string', 'max:255'],
            'materials.*.url' => ['nullable', 'string', 'url', 'max:2048'],
            'materials.*.file_path' => ['nullable', 'string', 'max:2048'],
            'materials.*.display_order' => ['integer', 'min:0'],
        ];
    }
}
