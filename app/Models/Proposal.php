<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'customer_id', 'title', 'description', 'valid_until', 'subtotal', 'discount', 'total', 'notes', 'commercial_terms', 'status', 'sent_at', 'viewed_at', 'approved_at', 'rejected_at'];

    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany { return $this->hasMany(ProposalItem::class); }
    public function publicToken(): HasOne { return $this->hasOne(ProposalPublicToken::class); }
}
