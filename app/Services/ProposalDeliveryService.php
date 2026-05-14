<?php

namespace App\Services;

use App\Mail\ProposalApprovedMail;
use App\Mail\ProposalRejectedMail;
use App\Mail\ProposalSentMail;
use App\Mail\ProposalViewedMail;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ProposalDeliveryService
{
    public function __construct(private readonly ProposalService $proposalService) {}

    public function sendToCustomer(Proposal $proposal, ?User $actor = null): Proposal
    {
        $proposal->loadMissing(['customer', 'items', 'publicToken', 'user']);
        $this->proposalService->assertProposalIsEditable($proposal);
        $this->sendProposalMail($proposal);

        return $this->proposalService->markAsSent($proposal, $actor)->fresh(['customer', 'items', 'publicToken']);
    }

    public function sendToCustomerWithoutStatusChange(Proposal $proposal): Proposal
    {
        $proposal->loadMissing(['customer', 'items', 'publicToken', 'user']);
        $this->sendProposalMail($proposal);

        return $proposal->fresh(['customer', 'items', 'publicToken']);
    }

    public function notifyView(Proposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'items', 'publicToken', 'user']);
        Mail::to($proposal->user->email)->send(new ProposalViewedMail($proposal));
    }

    public function notifyApproval(Proposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'items', 'publicToken', 'user']);
        Mail::to($proposal->user->email)->send(new ProposalApprovedMail($proposal));
    }

    public function notifyRejection(Proposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'items', 'user']);
        Mail::to($proposal->user->email)->send(new ProposalRejectedMail($proposal));
    }

    private function sendProposalMail(Proposal $proposal): void
    {
        if (! $proposal->customer->email) {
            throw ValidationException::withMessages([
                'customer.email' => 'Cadastre um e-mail para o cliente antes de enviar a proposta.',
            ]);
        }

        $this->proposalService->ensurePublicToken($proposal);

        Mail::to($proposal->customer->email)->send(new ProposalSentMail($proposal->fresh([
            'customer',
            'items',
            'publicToken',
            'user',
        ])));
    }
}
