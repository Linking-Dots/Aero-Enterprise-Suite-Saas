<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hrmac', 'hrm.training.training-programs.update');
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'exists:training_categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description' => ['sometimes', 'nullable', 'string'],
            'delivery_mode' => ['sometimes', 'string', 'in:in_person,virtual,self_paced'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1'],
            'learning_objectives' => ['sometimes', 'nullable', 'array'],
            'learning_objectives.*' => ['string'],
            'prerequisites' => ['sometimes', 'nullable', 'array'],
            'prerequisites.*' => ['string'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string'],
            'is_mandatory' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'materials' => ['sometimes', 'nullable', 'array'],
            'materials.*.kind' => ['required', 'string', 'in:link,file'],
            'materials.*.label' => ['required', 'string', 'max:255'],
            'materials.*.url' => ['nullable', 'string', 'url', 'max:2048'],
            'materials.*.file_path' => ['nullable', 'string', 'max:2048'],
            'materials.*.display_order' => ['integer', 'min:0'],
        ];
    }
}
