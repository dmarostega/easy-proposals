<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreProposalRequest;
use App\Models\Proposal;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\ProposalDeliveryService;
use App\Services\ProposalPdfService;
use App\Services\ProposalService;
use Illuminate\Http\Request;

class UserProposalController extends Controller
{
    public function index(Request $request, User $user)
    {
        $query = $user->proposals()->with('customer', 'publicToken')->withCount('events');

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

    public function store(AdminStoreProposalRequest $request, User $user, ProposalService $service, AdminAuditService $audit)
    {
        $proposal = $service->create($user, $request->validated(), $request->user(), enforceLimits: false);
        $audit->record($request, $user, 'target_proposal.created', $proposal);

        return response()->json($proposal, 201);
    }

    public function show(User $user, Proposal $proposal)
    {
        $this->ensureProposalBelongsToUser($user, $proposal);

        return response()->json($proposal->load('customer', 'items', 'publicToken', 'events'));
    }

    public function update(AdminStoreProposalRequest $request, User $user, Proposal $proposal, ProposalService $service, AdminAuditService $audit)
    {
        $this->ensureProposalBelongsToUser($user, $proposal);
        $proposal = $service->update($proposal, $request->validated(), $request->user(), allowFinal: true);
        $audit->record($request, $user, 'target_proposal.updated', $proposal);

        return response()->json($proposal);
    }

    public function send(Request $request, User $user, Proposal $proposal, ProposalDeliveryService $deliveryService, AdminAuditService $audit)
    {
        $this->ensureProposalBelongsToUser($user, $proposal);
        $proposal = $deliveryService->sendToCustomer($proposal, $request->user(), allowFinal: true);
        $audit->record($request, $user, 'target_proposal.sent', $proposal);

        return response()->json($proposal);
    }

    public function destroy(Request $request, User $user, Proposal $proposal, AdminAuditService $audit)
    {
        $this->ensureProposalBelongsToUser($user, $proposal);
        $audit->record($request, $user, 'target_proposal.deleted', $proposal, ['title' => $proposal->title]);
        $proposal->delete();

        return response()->noContent();
    }

    public function pdf(User $user, Proposal $proposal, ProposalPdfService $pdfService)
    {
        $this->ensureProposalBelongsToUser($user, $proposal);

        return $pdfService->download($proposal->load('customer', 'items', 'user'));
    }

    private function ensureProposalBelongsToUser(User $user, Proposal $proposal): void
    {
        abort_unless($proposal->user_id === $user->id, 404);
    }
}
