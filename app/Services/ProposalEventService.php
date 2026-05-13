<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\ProposalEvent;
use App\Models\User;

class ProposalEventService
{
    public function record(Proposal $proposal, string $type, string $message, ?User $user = null, array $metadata = []): ProposalEvent
    {
        return $proposal->events()->create([
            'user_id' => $user?->id,
            'type' => $type,
            'message' => $message,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }
}
