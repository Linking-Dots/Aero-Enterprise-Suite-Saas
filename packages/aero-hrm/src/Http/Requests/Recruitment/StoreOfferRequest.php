<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hrmac', 'hrm.recruitment.offer-letters.send');
    }

    public function rules(): array
    {
        return [
            'application_id' => ['required', 'integer', 'exists:job_applications,id'],
            'offered_salary' => ['required', 'numeric', 'min:0'],
            'salary_currency' => ['required', 'string', 'size:3'],
            'joining_date' => ['required', 'date'],
            'offer_valid_until' => ['required', 'date', 'after:today'],
        ];
    }
}
