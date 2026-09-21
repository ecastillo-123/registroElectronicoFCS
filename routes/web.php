<?php

use App\Http\Controllers\AvisoPrivacidadController;
use App\Http\Controllers\ExpedientePdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->get('/admin/avisoprivacidad', [AvisoPrivacidadController::class, 'descargar'])
    ->name('admin.avisoprivacidad.descargar');

Route::middleware('auth')->get('/admin/expediente/pdf', [ExpedientePdfController::class, 'show'])
    ->name('admin.expediente.pdf');
