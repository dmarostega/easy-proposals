<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Mail\ProposalApprovedMail;
use App\Mail\ProposalRejectedMail;
use App\Mail\ProposalSentMail;
use App\Models\Proposal;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ProposalDeliveryService
{
    public function __construct(private readonly ProposalService $proposalService) {}

    public function sendToCustomer(Proposal $proposal): Proposal
    {
        $proposal->loadMissing(['customer', 'items', 'publicToken', 'user']);

        if (! $proposal->customer->email) {
            throw ValidationException::withMessages([
                'customer.email' => 'Cadastre um e-mail para o cliente antes de enviar a proposta.',
            ]);
        }

        $this->proposalService->ensurePublicToken($proposal);

        if (! in_array($proposal->status, [ProposalStatus::Approved, ProposalStatus::Rejected], true)) {
            $proposal->forceFill([
                'status' => ProposalStatus::Sent,
                'sent_at' => $proposal->sent_at ?? now(),
            ])->save();
        }

        Mail::to($proposal->customer->email)->send(new ProposalSentMail($proposal->fresh([
            'customer',
            'items',
            'publicToken',
            'user',
        ])));

        return $proposal->fresh(['customer', 'items', 'publicToken']);
    }

    public function notifyApproval(Proposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'items', 'user']);
        Mail::to($proposal->user->email)->send(new ProposalApprovedMail($proposal));
    }

    public function notifyRejection(Proposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'items', 'user']);
        Mail::to($proposal->user->email)->send(new ProposalRejectedMail($proposal));
    }
}
