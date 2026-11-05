<?php

use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\ContractAssetController;
use App\Http\Controllers\Api\V1\ContractController;
use App\Http\Controllers\Api\V1\ManufacturerController;
use App\Http\Controllers\Api\V1\VendorController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json([
    'status' => 'ok',
    'time' => now()->toIso8601String(),
]));

Route::apiResource('assets', AssetController::class);
Route::apiResource('contracts', ContractController::class);
Route::apiResource('manufacturers', ManufacturerController::class)->only('index');
Route::apiResource('vendors', VendorController::class)->only('index');

Route::controller(ContractAssetController::class)
    ->prefix('contracts/{contract}/assets/{asset}')
    ->name('contracts.assets.')
    ->group(function (): void {
        Route::put('/', 'attach')->name('attach');
        Route::delete('/', 'detach')->name('detach');
    });
