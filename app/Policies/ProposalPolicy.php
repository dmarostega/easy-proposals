<?php

namespace App\Policies;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Proposal $proposal): bool
    {
        return $proposal->user_id === $user->id;
    }

    public function update(User $user, Proposal $proposal): bool
    {
        return $proposal->user_id === $user->id && ! $this->isFinal($proposal);
    }

    public function delete(User $user, Proposal $proposal): bool
    {
        return $proposal->user_id === $user->id && ! $this->isFinal($proposal);
    }

    private function isFinal(Proposal $proposal): bool
    {
        return in_array($proposal->status, [
            ProposalStatus::Approved,
            ProposalStatus::Rejected,
            ProposalStatus::Expired,
        ], true);
    }
}
