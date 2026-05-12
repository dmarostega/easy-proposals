<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceItemRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'unit_price' => ['required', 'numeric', 'min:0'], 'is_active' => ['boolean']]; }
}
