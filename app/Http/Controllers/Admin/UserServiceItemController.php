<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceItemRequest;
use App\Models\ServiceItem;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Http\Request;

class UserServiceItemController extends Controller
{
    public function index(Request $request, User $user)
    {
        $query = $user->serviceItems();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $perPage = min(max($request->integer('per_page', 10), 1), 50);

        return response()->json($query->latest()->paginate($perPage)->withQueryString());
    }

    public function store(ServiceItemRequest $request, User $user, AdminAuditService $audit)
    {
        $serviceItem = $user->serviceItems()->create($request->validated());
        $audit->record($request, $user, 'target_service.created', $serviceItem);

        return response()->json($serviceItem, 201);
    }

    public function show(User $user, ServiceItem $serviceItem)
    {
        $this->ensureServiceBelongsToUser($user, $serviceItem);

        return response()->json($serviceItem);
    }

    public function update(ServiceItemRequest $request, User $user, ServiceItem $serviceItem, AdminAuditService $audit)
    {
        $this->ensureServiceBelongsToUser($user, $serviceItem);
        $serviceItem->update($request->validated());
        $audit->record($request, $user, 'target_service.updated', $serviceItem);

        return response()->json($serviceItem);
    }

    public function destroy(Request $request, User $user, ServiceItem $serviceItem, AdminAuditService $audit)
    {
        $this->ensureServiceBelongsToUser($user, $serviceItem);
        $audit->record($request, $user, 'target_service.deleted', $serviceItem, ['name' => $serviceItem->name]);
        $serviceItem->delete();

        return response()->noContent();
    }

    private function ensureServiceBelongsToUser(User $user, ServiceItem $serviceItem): void
    {
        abort_unless($serviceItem->user_id === $user->id, 404);
    }
}
