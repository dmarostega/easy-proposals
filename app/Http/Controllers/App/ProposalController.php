<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProposalRequest;
use App\Models\Proposal;
use App\Services\ProposalNotificationService;
use App\Services\ProposalService;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->expectsJson()) {
            return app(AppPageController::class)($request);
        }

        return response()->json(
            $request->user()->proposals()->with('customer', 'publicToken')->latest()->paginate()
        );
    }

    public function store(StoreProposalRequest $request, ProposalService $service)
    {
        return response()->json($service->create($request->user(), $request->validated()), 201);
    }

    public function show(Proposal $proposal)
    {
        $this->authorize('view', $proposal);

        return response()->json($proposal->load('customer', 'items', 'publicToken'));
    }

    public function update(StoreProposalRequest $request, Proposal $proposal, ProposalService $service)
    {
        $this->authorize('update', $proposal);

        return response()->json($service->update($proposal, $request->validated()));
    }

    public function send(Proposal $proposal, ProposalService $service, ProposalNotificationService $notifications)
    {
        $this->authorize('update', $proposal);

        $proposal = $service->markAsSent($proposal);
        $notifications->sendToCustomer($proposal);

        return response()->json($proposal);
    }

    public function destroy(Proposal $proposal)
    {
        $this->authorize('delete', $proposal);
        $proposal->delete();

        return response()->noContent();
    }

    public function pdf(Proposal $proposal)
    {
        $this->authorize('view', $proposal);
        abort_unless($proposal->user->plan?->allows_pdf, 403, 'Seu plano não permite PDF.');

        return response()->view('pdf.proposal', ['proposal' => $proposal->load('customer', 'items')]);
    }
}
