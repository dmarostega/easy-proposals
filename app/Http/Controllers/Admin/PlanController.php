<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminPlanRequest;
use App\Models\Plan;

class PlanController extends Controller
{
    public function index()
    {
        return response()->json(Plan::latest()->paginate());
    }

    public function store(AdminPlanRequest $request)
    {
        return response()->json(Plan::create($request->validated()), 201);
    }

    public function show(Plan $plan)
    {
        return response()->json($plan);
    }

    public function update(AdminPlanRequest $request, Plan $plan)
    {
        $plan->update($request->validated());

        return response()->json($plan);
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();

        return response()->noContent();
    }
}
