<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json([
    'status' => 'ok',
    'time' => now()->toIso8601String(),
]));
