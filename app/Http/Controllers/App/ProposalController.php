<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProposalRequest;
use App\Models\Proposal;
use App\Services\ProposalDeliveryService;
use App\Services\ProposalService;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->proposals()->with('customer', 'publicToken')->latest()->paginate());
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

    public function send(Proposal $proposal, ProposalDeliveryService $deliveryService)
    {
        $this->authorize('update', $proposal);

        return response()->json($deliveryService->sendToCustomer($proposal));
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
