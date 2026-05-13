<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppPageController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('app.spa', [
            'title' => 'Área autenticada - Proposta Fácil',
            'page' => 'app',
            'user' => $request->user()->load('plan'),
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }
}
