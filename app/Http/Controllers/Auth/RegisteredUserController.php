<?php

namespace App\Http\Controllers\Auth;

use App\Enums\PlanSlug;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    public function create() { return view('auth.register'); }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $freePlan = Plan::query()->where('slug', PlanSlug::Free->value)->first();
        $user = User::create([...$request->safe()->only(['name', 'email', 'password']), 'role' => UserRole::User, 'plan_id' => $freePlan?->id, 'is_active' => true]);
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
