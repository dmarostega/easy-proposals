<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class PlanLimitService
{
    public function assertCanCreateProposal(User $user): void
    {
        $limit = $user->plan?->monthly_proposal_limit;
        if ($limit === null) {
            return;
        }

        $createdThisMonth = $user->proposals()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        if ($createdThisMonth >= $limit) {
            throw ValidationException::withMessages([
                'plan' => "Seu plano permite criar até {$limit} propostas por mês.",
            ]);
        }
    }

    public function assertCanCreateCustomer(User $user): void
    {
        $limit = $user->plan?->customer_limit;
        if ($limit !== null && $user->customers()->count() >= $limit) {
            throw ValidationException::withMessages(['plan' => "Seu plano permite cadastrar até {$limit} clientes."]);
        }
    }
}
