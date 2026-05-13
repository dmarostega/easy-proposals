<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\ProposalPublicToken;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProposalService
{
    public function __construct(
        private readonly PlanLimitService $limits,
        private readonly ProposalCalculator $calculator,
    ) {}

    public function create(User $user, array $data): Proposal
    {
        $this->limits->assertCanCreateProposal($user);

        return DB::transaction(function () use ($user, $data): Proposal {
            $totals = $this->calculator->totals($data['items'], (float) ($data['discount'] ?? 0));
            $proposal = $user->proposals()->create(array_merge(Arr::only($data, [
                'customer_id', 'title', 'description', 'valid_until', 'notes', 'commercial_terms',
            ]), $totals, ['status' => ProposalStatus::Draft]));

            $this->syncItems($proposal, $data['items']);
            $this->ensurePublicToken($proposal);

            return $proposal->load(['customer', 'items', 'publicToken']);
        });
    }

    public function update(Proposal $proposal, array $data): Proposal
    {
        return DB::transaction(function () use ($proposal, $data): Proposal {
            $totals = $this->calculator->totals($data['items'], (float) ($data['discount'] ?? 0));

            $proposal->update(array_merge(Arr::only($data, [
                'customer_id', 'title', 'description', 'valid_until', 'notes', 'commercial_terms',
            ]), $totals));

            $proposal->items()->delete();
            $this->syncItems($proposal, $data['items']);
            $this->ensurePublicToken($proposal);

            return $proposal->load(['customer', 'items', 'publicToken']);
        });
    }

    public function markAsSent(Proposal $proposal): Proposal
    {
        $this->ensurePublicToken($proposal);

        $proposal->update([
            'status' => ProposalStatus::Sent,
            'sent_at' => now(),
        ]);

        return $proposal->load(['customer', 'items', 'publicToken', 'user']);
    }

    public function ensurePublicToken(Proposal $proposal): ProposalPublicToken
    {
        return $proposal->publicToken()->firstOrCreate([], ['token' => Str::random(64)]);
    }

    private function syncItems(Proposal $proposal, array $items): void
    {
        foreach ($items as $item) {
            $proposal->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => round((float) $item['quantity'] * (float) $item['unit_price'], 2),
            ]);
        }
    }
}
