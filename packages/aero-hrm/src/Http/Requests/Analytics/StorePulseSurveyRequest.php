<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePulseSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hrmac', 'hrm.pulse-surveys.survey-list.create');
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['required'],
            'questions.*.type' => ['required', 'string', 'in:scale,choice,text'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.options' => ['sometimes', 'nullable', 'array'],
            'anonymous' => ['sometimes', 'boolean'],
            'closes_at' => ['nullable', 'date'],
            'audience_filter' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
