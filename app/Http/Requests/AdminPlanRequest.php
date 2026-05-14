<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PortugueseValidationMessages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminPlanRequest extends FormRequest
{
    use PortugueseValidationMessages;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:80', Rule::unique('plans', 'slug')->ignore($this->route('plan'))], 'monthly_price_cents' => ['required', 'integer', 'min:0'], 'monthly_proposal_limit' => ['nullable', 'integer', 'min:0'], 'customer_limit' => ['nullable', 'integer', 'min:0'], 'allows_pdf' => ['boolean'], 'allows_custom_logo' => ['boolean'], 'is_active' => ['boolean']];
    }
}
