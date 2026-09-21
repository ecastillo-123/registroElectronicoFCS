<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\WorkforceController;
use App\Http\Controllers\Api\EvidenceController;
use App\Http\Controllers\Api\ImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/estado', [AuthController::class, 'estado']);

        Route::post('/dispositivo', [DeviceController::class, 'registrar']);

        Route::post('/checar', [CheckInController::class, 'checar']);
        Route::post('/checar/sync', [CheckInController::class, 'syncPending']);

        Route::get('/trabajadores', [WorkforceController::class, 'workers']);
        Route::post('/trabajadores', [WorkforceController::class, 'storeWorker']);
        Route::patch('/trabajadores/{employee}', [WorkforceController::class, 'updateWorker']);
        Route::get('/jornadas', [WorkforceController::class, 'shifts']);
        Route::post('/jornadas', [WorkforceController::class, 'storeShift']);
        Route::get('/calendario', [WorkforceController::class, 'calendar']);
        Route::post('/calendario', [WorkforceController::class, 'storeCalendar']);
        Route::get('/reglas', [WorkforceController::class, 'rules']);
        Route::post('/reglas', [WorkforceController::class, 'storeRule']);
        Route::get('/incidencias', [WorkforceController::class, 'incidents']);
        Route::post('/incidencias', [WorkforceController::class, 'storeIncident']);
        Route::get('/correcciones', [WorkforceController::class, 'corrections']);
        Route::post('/correcciones', [WorkforceController::class, 'storeCorrection']);
        Route::post('/correcciones/{correction}/autorizar', [WorkforceController::class, 'authorizeCorrection']);
        Route::post('/jornada/calcular', [WorkforceController::class, 'calculate']);
        Route::get('/auditoria/verificar', [WorkforceController::class, 'audit']);
        Route::get('/auditoria', [WorkforceController::class, 'auditEvents']);
        Route::get('/trabajadores/{employee}/expediente', [WorkforceController::class, 'dossier']);
        Route::get('/reportes/{employee}/pdf', [WorkforceController::class, 'reportPdf']);
        Route::get('/reportes/{employee}/excel', [WorkforceController::class, 'reportExcel']);
        Route::get('/alertas', [WorkforceController::class, 'alerts']);
        Route::patch('/alertas/{alert}/resolver', [WorkforceController::class, 'resolveAlert']);
        Route::post('/evidencias', [EvidenceController::class, 'store']);
        Route::post('/importacion/empleados', [ImportController::class, 'employees']);
    });
});
