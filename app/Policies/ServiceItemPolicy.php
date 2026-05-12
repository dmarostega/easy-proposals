<?php
namespace App\Policies;
use App\Models\ServiceItem; use App\Models\User;
class ServiceItemPolicy { public function viewAny(User $user): bool { return true; } public function create(User $user): bool { return true; } public function update(User $user, ServiceItem $serviceItem): bool { return $serviceItem->user_id === $user->id; } public function delete(User $user, ServiceItem $serviceItem): bool { return $serviceItem->user_id === $user->id; } public function view(User $user, ServiceItem $serviceItem): bool { return $serviceItem->user_id === $user->id; } }
