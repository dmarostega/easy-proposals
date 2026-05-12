<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('app.profile.edit', [
            'user' => $request->user()->load('plan'),
        ]);
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
