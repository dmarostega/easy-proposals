<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminPageController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('app.spa', [
            'title' => 'Admin - Proposta Fácil',
            'page' => 'admin',
            'user' => $request->user()->load('plan'),
            'isAdmin' => true,
        ]);
    }
}
