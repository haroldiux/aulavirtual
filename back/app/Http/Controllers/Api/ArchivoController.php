<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArchivoController extends Controller
{
    public function subir(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'max:65536'], // 64MB (match nginx client_max_body_size)
            'carpeta' => ['nullable', 'string', 'max:100'],
        ]);

        $carpeta = $request->input('carpeta', 'general');
        $file = $request->file('archivo');
        $extension = $file->getClientOriginalExtension();
        $nombre = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $nombreLimpio = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre);
        $ruta = $file->storeAs("{$carpeta}", "{$nombreLimpio}-" . uniqid() . ".{$extension}", 's3');

        $url = Storage::disk('s3')->url($ruta);

        return response()->json([
            'data' => [
                'ruta' => $ruta,
                'url' => $url,
                'nombre' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'tamano' => $file->getSize(),
            ],
        ], 201);
    }

    public function descargar(Request $request, string $path): JsonResponse
    {
        abort_unless(Storage::disk('s3')->exists($path), 404, 'Archivo no encontrado.');

        $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));

        return response()->json([
            'data' => [
                'url' => $url,
                'ruta' => $path,
            ],
        ]);
    }
}
