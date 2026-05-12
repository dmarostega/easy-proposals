<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Http\Requests\AdminPlanRequest; use App\Models\Plan;
class PlanController extends Controller { public function index(){return response()->json(Plan::latest()->paginate());} public function store(AdminPlanRequest $r){return response()->json(Plan::create($r->validated()),201);} public function show(Plan $plan){return response()->json($plan);} public function update(AdminPlanRequest $r, Plan $plan){$plan->update($r->validated()); return response()->json($plan);} public function destroy(Plan $plan){$plan->delete(); return response()->noContent();}}
