<?php

namespace App\Providers;

use App\Contracts\Logging\ApiTrafficLoggerInterface;
use App\Contracts\Services\AssetServiceInterface;
use App\Contracts\Services\ContractServiceInterface;
use App\Contracts\Services\ManufacturerServiceInterface;
use App\Contracts\Services\VendorServiceInterface;
use App\Services\AssetService;
use App\Services\ContractService;
use App\Services\ManufacturerService;
use App\Services\VendorService;
use App\Services\Logging\DatabaseApiTrafficLogger;
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
        //
    }
}
