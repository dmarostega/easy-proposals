<?php
namespace App\Http\Controllers\Admin;
use App\Enums\ProposalStatus; use App\Http\Controllers\Controller; use App\Models\Plan; use App\Models\Proposal; use App\Models\User;
class ReportController extends Controller { public function __invoke(){ return response()->json(['users'=>User::count(),'users_by_plan'=>Plan::withCount('users')->get(['id','name','slug']),'proposals_created'=>Proposal::count(),'proposals_approved'=>Proposal::where('status',ProposalStatus::Approved)->count()]); }}
