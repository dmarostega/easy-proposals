<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppSettingRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() === true; }
    public function rules(): array { return ['settings' => ['required', 'array'], 'settings.*' => ['nullable', 'string']]; }
}
