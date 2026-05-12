<?php
namespace App\Http\Controllers\App;
use App\Enums\ProposalStatus; use App\Http\Controllers\Controller; use Illuminate\Http\Request;
class DashboardController extends Controller { public function __invoke(Request $request) { $user=$request->user(); return response()->json(['created_this_month'=>$user->proposals()->whereBetween('created_at',[now()->startOfMonth(),now()->endOfMonth()])->count(),'approved'=>$user->proposals()->where('status',ProposalStatus::Approved)->count(),'pending'=>$user->proposals()->whereIn('status',[ProposalStatus::Draft,ProposalStatus::Sent,ProposalStatus::Viewed])->count(),'approved_total'=>(float)$user->proposals()->where('status',ProposalStatus::Approved)->sum('total'),'plan_limit'=>$user->plan?->monthly_proposal_limit]); }}
