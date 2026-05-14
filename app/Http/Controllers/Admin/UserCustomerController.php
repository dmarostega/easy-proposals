<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Http\Request;

class UserCustomerController extends Controller
{
    public function index(Request $request, User $user)
    {
        $query = $user->customers();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%");
            });
        }

        $perPage = min(max($request->integer('per_page', 10), 1), 50);

        return response()->json($query->latest()->paginate($perPage)->withQueryString());
    }

    public function store(CustomerRequest $request, User $user, AdminAuditService $audit)
    {
        $customer = $user->customers()->create($request->validated());
        $audit->record($request, $user, 'target_customer.created', $customer);

        return response()->json($customer, 201);
    }

    public function show(User $user, Customer $customer)
    {
        $this->ensureCustomerBelongsToUser($user, $customer);

        return response()->json($customer);
    }

    public function update(CustomerRequest $request, User $user, Customer $customer, AdminAuditService $audit)
    {
        $this->ensureCustomerBelongsToUser($user, $customer);
        $customer->update($request->validated());
        $audit->record($request, $user, 'target_customer.updated', $customer);

        return response()->json($customer);
    }

    public function destroy(Request $request, User $user, Customer $customer, AdminAuditService $audit)
    {
        $this->ensureCustomerBelongsToUser($user, $customer);
        $audit->record($request, $user, 'target_customer.deleted', $customer, ['name' => $customer->name]);
        $customer->delete();

        return response()->noContent();
    }

    private function ensureCustomerBelongsToUser(User $user, Customer $customer): void
    {
        abort_unless($customer->user_id === $user->id, 404);
    }
}
