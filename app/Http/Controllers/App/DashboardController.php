<?php

namespace App\Http\Controllers\App;

use App\Enums\ProposalStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        if (! $request->expectsJson()) {
            return app(AppPageController::class)($request);
        }

        $user = $request->user();
        $proposals = $user->proposals();

        if ($request->filled('from')) {
            $proposals->whereDate('created_at', '>=', $request->date('from')->toDateString());
        }

        if ($request->filled('to')) {
            $proposals->whereDate('created_at', '<=', $request->date('to')->toDateString());
        }

        return response()->json([
            'created_in_period' => (clone $proposals)->count(),
            'approved_in_period' => (clone $proposals)->where('status', ProposalStatus::Approved)->count(),
            'pending_in_period' => (clone $proposals)->whereIn('status', [ProposalStatus::Draft, ProposalStatus::Sent, ProposalStatus::Viewed])->count(),
            'approved_total_in_period' => (float) (clone $proposals)->where('status', ProposalStatus::Approved)->sum('total'),
            'plan_limit' => $user->plan?->monthly_proposal_limit,
        ]);
    }
}
