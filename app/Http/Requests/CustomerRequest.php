<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PortugueseValidationMessages;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    use PortugueseValidationMessages;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string', 'max:50'], 'document' => ['nullable', 'string', 'max:80'], 'address' => ['nullable', 'string'], 'notes' => ['nullable', 'string']];
    }
}
