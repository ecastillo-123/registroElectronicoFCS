<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\DeviceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/estado', [AuthController::class, 'estado']);

        Route::post('/dispositivo', [DeviceController::class, 'registrar']);

        Route::post('/checar', [CheckInController::class, 'checar']);
        Route::post('/checar/sync', [CheckInController::class, 'syncPending']);
    });
});
