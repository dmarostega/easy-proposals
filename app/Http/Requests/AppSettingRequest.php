<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PortugueseValidationMessages;
use Illuminate\Foundation\Http\FormRequest;

class AppSettingRequest extends FormRequest
{
    use PortugueseValidationMessages;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return ['settings' => ['required', 'array'], 'settings.*' => ['nullable', 'string']];
    }
}
