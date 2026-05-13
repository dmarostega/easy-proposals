<?php

namespace App\Mail;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProposalApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Proposal $proposal) {}

    public function build(): self
    {
        return $this
            ->subject('Proposta aprovada: '.$this->proposal->title)
            ->markdown('emails.proposals.approved', [
                'proposal' => $this->proposal,
                'url' => route('public.proposals.show', $this->proposal->publicToken->token),
            ]);
    }
}
