<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Proposal;
use App\Models\ServiceItem;
use App\Policies\CustomerPolicy;
use App\Policies\ProposalPolicy;
use App\Policies\ServiceItemPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(ServiceItem::class, ServiceItemPolicy::class);
        Gate::policy(Proposal::class, ProposalPolicy::class);

        Schema::defaultStringLength(191);

    }
}
