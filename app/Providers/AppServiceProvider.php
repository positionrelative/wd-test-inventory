<?php

namespace App\Providers;

use App\Contracts\Logging\ApiTrafficLoggerInterface;
use App\Contracts\Services\AssetModelServiceInterface;
use App\Contracts\Services\AssetServiceInterface;
use App\Contracts\Services\ContractAssetServiceInterface;
use App\Contracts\Services\ContractServiceInterface;
use App\Contracts\Services\ManufacturerServiceInterface;
use App\Contracts\Services\VendorServiceInterface;
use App\Services\AssetModelService;
use App\Services\AssetService;
use App\Services\ContractAssetService;
use App\Services\ContractService;
use App\Services\ManufacturerService;
use App\Services\VendorService;
use App\Services\Logging\DatabaseApiTrafficLogger;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Interface => implementation bindings.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        AssetServiceInterface::class => AssetService::class,
        AssetModelServiceInterface::class => AssetModelService::class,
        ContractAssetServiceInterface::class => ContractAssetService::class,
        ContractServiceInterface::class => ContractService::class,
        ManufacturerServiceInterface::class => ManufacturerService::class,
        VendorServiceInterface::class => VendorService::class,
        ApiTrafficLoggerInterface::class => DatabaseApiTrafficLogger::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(
            (int) config('api.rate_limit_per_minute', 60),
        )->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
