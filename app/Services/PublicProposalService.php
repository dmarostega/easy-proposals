<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\ProposalPublicToken;
use Illuminate\Support\Facades\DB;

class PublicProposalService
{
    public function __construct(
        private readonly ProposalDeliveryService $deliveryService,
        private readonly ProposalEventService $events,
    ) {}

    public function findByToken(string $token): Proposal
    {
        $publicToken = ProposalPublicToken::query()
            ->where('token', $token)
            ->with('proposal.items', 'proposal.customer', 'proposal.user.plan')
            ->firstOrFail();

        $publicToken->update(['last_viewed_at' => now()]);
        $proposal = $publicToken->proposal;

        if ($proposal->status === ProposalStatus::Sent) {
            $proposal->update(['status' => ProposalStatus::Viewed, 'viewed_at' => $proposal->viewed_at ?? now()]);
            $this->events->record($proposal, 'viewed', 'Cliente visualizou a proposta.');
            $this->deliveryService->notifyView($proposal->fresh(['items', 'customer', 'user', 'publicToken']));
        }

        return $proposal->fresh(['items', 'customer', 'user.plan', 'publicToken']);
    }

    public function approve(Proposal $proposal): Proposal
    {
        return DB::transaction(function () use ($proposal): Proposal {
            if ($proposal->status !== ProposalStatus::Approved) {
                $this->deliveryService->notifyApproval($proposal);
                $proposal->update(['status' => ProposalStatus::Approved, 'approved_at' => now(), 'rejected_at' => null]);
                $this->events->record($proposal, 'approved', 'Cliente aprovou a proposta.');
            }

            return $proposal->fresh(['items', 'customer']);
        });
    }

    public function reject(Proposal $proposal): Proposal
    {
        return DB::transaction(function () use ($proposal): Proposal {
            if ($proposal->status !== ProposalStatus::Rejected) {
                $this->deliveryService->notifyRejection($proposal);
                $proposal->update([
                    'status' => ProposalStatus::Rejected,
                    'rejected_at' => now(),
                    'approved_at' => null,
                ]);
                $this->events->record($proposal, 'rejected', 'Cliente recusou a proposta.');
            }

            return $proposal->fresh(['items', 'customer', 'user']);
        });
    }
}
