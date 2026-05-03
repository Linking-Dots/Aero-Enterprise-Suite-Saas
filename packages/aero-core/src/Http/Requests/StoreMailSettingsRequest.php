<?php

namespace Aero\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

class StoreMailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'host' => 'SMTP Host',
            'port' => 'SMTP Port',
            'encryption' => 'Encryption',
            'username' => 'SMTP Username',
            'password' => 'SMTP Password',
            'from_address' => 'From Address',
            'from_name' => 'From Name',
        ];
    }

    public function validatedMailSettings(): array
    {
        $data = $this->validated();

        if (! empty($data['password'])) {
            $data['password'] = Crypt::encryptString($data['password']);
        }

        return $data;
    }
}
