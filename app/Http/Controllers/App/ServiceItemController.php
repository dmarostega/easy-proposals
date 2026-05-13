<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceItemRequest;
use App\Models\ServiceItem;
use Illuminate\Http\Request;

class ServiceItemController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->expectsJson()) {
            return app(AppPageController::class)($request);
        }

        $query = $request->user()->serviceItems();

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

    public function store(ServiceItemRequest $request)
    {
        return response()->json($request->user()->serviceItems()->create($request->validated()), 201);
    }

    public function show(ServiceItem $serviceItem)
    {
        $this->authorize('view', $serviceItem);

        return response()->json($serviceItem);
    }

    public function update(ServiceItemRequest $request, ServiceItem $serviceItem)
    {
        $this->authorize('update', $serviceItem);
        $serviceItem->update($request->validated());

        return response()->json($serviceItem);
    }

    public function destroy(ServiceItem $serviceItem)
    {
        $this->authorize('delete', $serviceItem);
        $serviceItem->delete();

        return response()->noContent();
    }
}
