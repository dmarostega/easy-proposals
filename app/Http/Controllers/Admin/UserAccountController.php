<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProposalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminProfileRequest;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class UserAccountController extends Controller
{
    public function dashboard(Request $request, User $user)
    {
        $proposals = $user->proposals();

        if ($request->filled('from')) {
            $proposals->whereDate('created_at', '>=', $request->date('from')->toDateString());
        }

        if ($request->filled('to')) {
            $proposals->whereDate('created_at', '<=', $request->date('to')->toDateString());
        }

        return response()->json([
            'target_user' => $user->load('plan'),
            'created_in_period' => (clone $proposals)->count(),
            'approved_in_period' => (clone $proposals)->where('status', ProposalStatus::Approved)->count(),
            'pending_in_period' => (clone $proposals)->whereIn('status', [ProposalStatus::Draft, ProposalStatus::Sent, ProposalStatus::Viewed])->count(),
            'approved_total_in_period' => (float) (clone $proposals)->where('status', ProposalStatus::Approved)->sum('total'),
            'customers' => $user->customers()->count(),
            'services' => $user->serviceItems()->count(),
            'plan_limit' => $user->plan?->monthly_proposal_limit,
        ]);
    }

    public function profile(User $user)
    {
        return response()->json($user->load('plan'));
    }

    public function updateProfile(AdminProfileRequest $request, User $user, AdminAuditService $audit)
    {
        $data = Arr::only($request->validated(), [
            'business_name',
            'contact_details',
            'default_footer_text',
            'primary_color',
            'secondary_color',
        ]);

        if ($request->hasFile('logo')) {
            if ($user->logo_path) {
                Storage::disk('public')->delete($user->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $user->update($data);
        $audit->record($request, $user, 'target_profile.updated', $user);

        return response()->json($user->fresh('plan'));
    }

    public function auditLogs(User $user)
    {
        return response()->json(
            AdminAuditLog::with('admin:id,name,email')
                ->where('target_user_id', $user->id)
                ->latest()
                ->paginate(20)
        );
    }
}
