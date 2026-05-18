<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class RejectApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hrmac', 'hrm.recruitment.applicants.update');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
