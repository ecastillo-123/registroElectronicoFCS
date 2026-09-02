<?php

use App\Http\Controllers\AvisoPrivacidadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->get('/admin/avisoprivacidad', [AvisoPrivacidadController::class, 'descargar'])
    ->name('admin.avisoprivacidad.descargar');
