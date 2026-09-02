<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AvisoPrivacidadController extends Controller
{
    public function descargar(): BinaryFileResponse
    {
        $path = base_path('avisoprivacidad.pdf');

        abort_unless(is_file($path), 404, 'No se encontró el aviso de privacidad.');

        return response()->download($path, 'avisoprivacidad.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}