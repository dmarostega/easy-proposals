<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ProposalNotificationService;
use App\Services\PublicProposalService;

class PublicProposalController extends Controller
{
    public function show(string $token, PublicProposalService $service)
    {
        return view('public.proposal', ['proposal' => $service->findByToken($token)]);
    }

    public function approve(string $token, PublicProposalService $service, ProposalNotificationService $notifications)
    {
        $proposal = $service->findByToken($token);
        $proposal = $service->approve($proposal);
        $notifications->notifyOwnerAboutApproval($proposal);

        return redirect()->route('public.proposals.show', $token)->with('status', 'Proposta aprovada com sucesso.');
    }

    public function reject(string $token, PublicProposalService $service)
    {
        $proposal = $service->findByToken($token);
        $service->reject($proposal);

        return redirect()->route('public.proposals.show', $token)->with('status', 'Proposta recusada.');
    }
}
