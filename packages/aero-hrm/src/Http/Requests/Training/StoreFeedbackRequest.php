<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ownership check is done in the controller
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'instructor_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'overall_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'would_recommend' => ['required', 'boolean'],
            'what_worked' => ['nullable', 'string', 'max:2000'],
            'what_didnt_work' => ['nullable', 'string', 'max:2000'],
            'suggestions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
