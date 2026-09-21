<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Models\Employee;
use App\Models\Evidence;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['archivo' => ['required', 'file', 'max:20480'], 'employee_id' => ['nullable', 'exists:employees,id'], 'evidenciable_type' => ['required', 'in:employee,incident,correction'], 'evidenciable_id' => ['required', 'integer']]);
        $file = $data['archivo'];
        $contents = file_get_contents($file->getRealPath());
        $sha256 = hash('sha256', $contents);
        $path = $file->storeAs('evidence', now()->format('YmdHis').'-'.$sha256.'-'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName()));
        $target = match ($data['evidenciable_type'] ?? null) {
            'incident' => Incident::findOrFail($data['evidenciable_id']),
            'correction' => CorrectionRequest::findOrFail($data['evidenciable_id']),
            'employee' => Employee::findOrFail($data['evidenciable_id']),
            default => null,
        };
        $evidence = new Evidence(['employee_id' => $data['employee_id'] ?? null, 'nombre_archivo' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'ruta' => $path, 'sha256' => $sha256, 'tamano_bytes' => strlen($contents), 'subido_por' => $request->user()->id]);
        if ($target) { $target->evidences()->save($evidence); } else { $evidence->save(); }
        return response()->json($evidence, 201);
    }
}
