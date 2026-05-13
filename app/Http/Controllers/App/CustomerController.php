<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->expectsJson()) {
            return app(AppPageController::class)($request);
        }

        return response()->json($request->user()->customers()->latest()->paginate());
    }

    public function store(CustomerRequest $request, PlanLimitService $limits)
    {
        $limits->assertCanCreateCustomer($request->user());

        return response()->json($request->user()->customers()->create($request->validated()), 201);
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        return response()->json($customer);
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        $customer->update($request->validated());

        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);
        $customer->delete();

        return response()->noContent();
    }
}
