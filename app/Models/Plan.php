<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'monthly_price_cents', 'monthly_proposal_limit', 'customer_limit', 'allows_pdf', 'allows_custom_logo', 'is_active'];

    protected function casts(): array
    {
        return [
            'allows_pdf' => 'boolean',
            'allows_custom_logo' => 'boolean',
            'is_active' => 'boolean',
            'monthly_price_cents' => 'integer',
            'monthly_proposal_limit' => 'integer',
            'customer_limit' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
