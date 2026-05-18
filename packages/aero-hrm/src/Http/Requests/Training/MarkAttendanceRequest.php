<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class MarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hrmac', 'hrm.training.training-attendance.mark');
    }

    public function rules(): array
    {
        return [
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*' => ['required', 'string', 'in:attended,no_show'],
        ];
    }
}
