<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'plan_id', 'role', 'is_active', 'business_name', 'logo_path',
        'primary_color', 'secondary_color', 'default_footer_text', 'contact_details',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool { return $this->role === UserRole::Admin; }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function customers(): HasMany { return $this->hasMany(Customer::class); }
    public function serviceItems(): HasMany { return $this->hasMany(ServiceItem::class); }
    public function proposals(): HasMany { return $this->hasMany(Proposal::class); }
}
