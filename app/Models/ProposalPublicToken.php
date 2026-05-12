<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalPublicToken extends Model
{
    use HasFactory;

    protected $fillable = ['proposal_id', 'token', 'expires_at', 'last_viewed_at'];
    protected function casts(): array { return ['expires_at' => 'datetime', 'last_viewed_at' => 'datetime']; }
    public function proposal(): BelongsTo { return $this->belongsTo(Proposal::class); }
}
