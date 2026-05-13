<?php

namespace App\Services;

use App\Mail\ProposalApprovedMail;
use App\Mail\ProposalSentMail;
use App\Models\Proposal;
use Illuminate\Support\Facades\Mail;

class ProposalNotificationService
{
    public function sendToCustomer(Proposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'publicToken', 'user']);

        if (! $proposal->customer->email) {
            return;
        }

        Mail::to($proposal->customer->email)->send(new ProposalSentMail($proposal));
    }

    public function notifyOwnerAboutApproval(Proposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'publicToken', 'user']);

        Mail::to($proposal->user->email)->send(new ProposalApprovedMail($proposal));
    }
}
