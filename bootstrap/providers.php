<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\ProductionSafetyServiceProvider;
use App\Providers\RateLimitingServiceProvider;
use App\Providers\TrustedProxyServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ProductionSafetyServiceProvider::class,
    RateLimitingServiceProvider::class,
    TrustedProxyServiceProvider::class,
];
