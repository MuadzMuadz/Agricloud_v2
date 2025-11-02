<?php

namespace App\Providers;

use App\Models\{Warehouse, Items, Movements, Needs};
use App\Policies\{WarehousePolicy, ItemPolicy, MovementPolicy, NeedPolicy};
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Warehouse::class => WarehousePolicy::class,
        Items::class => ItemPolicy::class,
        Movements::class => MovementPolicy::class,
        Needs::class => NeedPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

