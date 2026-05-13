<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserRequest;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::with('plan')->latest()->paginate());
    }

    public function show(User $user)
    {
        return response()->json($user->load('plan'));
    }

    public function update(AdminUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return response()->json($user->load('plan'));
    }

    public function destroy(User $user)
    {
        $user->update(['is_active' => false]);

        return response()->noContent();
    }
}
