<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['nullable', 'string', 'max:255'],
            'contact_details' => ['nullable', 'string', 'max:2000'],
            'default_footer_text' => ['nullable', 'string', 'max:2000'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->hasFile('logo') && ! $this->user()?->plan?->allows_custom_logo) {
                    $validator->errors()->add('logo', 'Seu plano atual não permite enviar logo personalizada.');
                }
            },
        ];
    }
}
