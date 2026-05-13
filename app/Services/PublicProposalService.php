<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\ProposalPublicToken;
use Illuminate\Support\Facades\DB;

class PublicProposalService
{
    public function findByToken(string $token): Proposal
    {
        $publicToken = ProposalPublicToken::query()->where('token', $token)->with('proposal.items', 'proposal.customer', 'proposal.user.plan')->firstOrFail();
        $publicToken->update(['last_viewed_at' => now()]);
        $proposal = $publicToken->proposal;

        if ($proposal->status === ProposalStatus::Sent || $proposal->status === ProposalStatus::Draft) {
            $proposal->update(['status' => ProposalStatus::Viewed, 'viewed_at' => $proposal->viewed_at ?? now()]);
        }

        return $proposal->fresh(['items', 'customer', 'user.plan', 'publicToken']);
    }

    public function approve(Proposal $proposal): Proposal
    {
        return DB::transaction(function () use ($proposal): Proposal {
            if ($proposal->status !== ProposalStatus::Approved) {
                $proposal->update(['status' => ProposalStatus::Approved, 'approved_at' => now(), 'rejected_at' => null]);
            }

            return $proposal->fresh(['items', 'customer']);
        });
    }

    public function reject(Proposal $proposal): Proposal
    {
        return DB::transaction(function () use ($proposal): Proposal {
            $proposal->update(['status' => ProposalStatus::Rejected, 'rejected_at' => now(), 'approved_at' => null]);
            return $proposal->fresh(['items', 'customer']);
        });
    }
}
