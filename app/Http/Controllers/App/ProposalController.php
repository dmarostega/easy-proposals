<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProposalRequest;
use App\Models\Proposal;
use App\Services\ProposalDeliveryService;
use App\Services\ProposalPdfService;
use App\Services\ProposalService;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->expectsJson()) {
            return app(AppPageController::class)($request);
        }

        $query = $request->user()->proposals()->with('customer', 'publicToken')->withCount('events');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        if ($customerId = $request->integer('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        $perPage = min(max($request->integer('per_page', 10), 1), 50);

        return response()->json($query->latest()->paginate($perPage)->withQueryString());
    }

    public function store(StoreProposalRequest $request, ProposalService $service)
    {
        return response()->json($service->create($request->user(), $request->validated()), 201);
    }

    public function show(Proposal $proposal)
    {
        $this->authorize('view', $proposal);

        return response()->json($proposal->load('customer', 'items', 'publicToken', 'events'));
    }

    public function update(StoreProposalRequest $request, Proposal $proposal, ProposalService $service)
    {
        $this->authorize('update', $proposal);

        return response()->json($service->update($proposal, $request->validated()));
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

    public function pdf(Proposal $proposal, ProposalPdfService $pdfService)
    {
        $this->authorize('view', $proposal);
        abort_unless($proposal->user->plan?->allows_pdf, 403, 'Seu plano não permite PDF.');

        return $pdfService->download($proposal->load('customer', 'items', 'user'));
    }
}
