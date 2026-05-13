<?php

namespace App\Mail;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProposalRejectedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Proposal $proposal) {}

    public function build(): self
    {
        return $this
            ->subject('Proposta recusada: '.$this->proposal->title)
            ->view('mail.proposals.rejected')
            ->with(['proposal' => $this->proposal]);
    }
}
