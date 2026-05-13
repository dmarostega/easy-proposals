<?php

namespace App\Mail;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProposalViewedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Proposal $proposal) {}

    public function build(): self
    {
        return $this
            ->subject('Proposta visualizada: '.$this->proposal->title)
            ->markdown('emails.proposals.viewed', [
                'proposal' => $this->proposal,
                'url' => route('propostas.show', $this->proposal),
            ]);
    }
}
