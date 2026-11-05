<?php

use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AssetModelController;
use App\Http\Controllers\Api\V1\ContractAssetController;
use App\Http\Controllers\Api\V1\ContractController;
use App\Http\Controllers\Api\V1\ManufacturerController;
use App\Http\Controllers\Api\V1\VendorController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json([
    'status' => 'ok',
    'time' => now()->toIso8601String(),
]));

Route::get('assets/count', [AssetController::class, 'count'])->name('assets.count');
Route::get('asset-models/count', [AssetModelController::class, 'count'])->name('asset-models.count');
Route::get('contracts/count', [ContractController::class, 'count'])->name('contracts.count');
Route::get('manufacturers/count', [ManufacturerController::class, 'count'])->name('manufacturers.count');
Route::get('vendors/count', [VendorController::class, 'count'])->name('vendors.count');

Route::apiResource('assets', AssetController::class);
Route::apiResource('asset-models', AssetModelController::class)->parameters(['asset-models' => 'assetModel']);
Route::apiResource('contracts', ContractController::class);
Route::apiResource('manufacturers', ManufacturerController::class)->only('index');
Route::apiResource('vendors', VendorController::class)->only('index');

Route::controller(ContractAssetController::class)
    ->prefix('contracts/{contract}/assets')
    ->name('contracts.assets.')
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/count', 'count')->name('count');
        Route::put('/{asset}', 'attach')->name('attach');
        Route::delete('/{asset}', 'detach')->name('detach');
    });
