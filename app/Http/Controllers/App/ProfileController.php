<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountRequest;
use App\Http\Requests\ProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return app(AppPageController::class)($request);
    }

    public function updateAccount(AccountRequest $request)
    {
        $user = $request->user();
        $data = Arr::only($request->validated(), ['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user->update($data);

        if ($request->expectsJson()) {
            return response()->json($user->fresh('plan'));
        }

        return back()->with('status', 'Conta atualizada com sucesso.');
    }

    public function update(ProfileRequest $request)
    {
        $user = $request->user();
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

        if ($request->expectsJson()) {
            return response()->json($user->fresh('plan'));
        }

        return back()->with('status', 'Perfil atualizado com sucesso.');
    }
}
