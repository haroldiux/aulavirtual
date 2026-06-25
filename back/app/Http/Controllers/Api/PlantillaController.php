<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plantilla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlantillaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $query = Plantilla::query()->with('docente:id,nombre,email,avatar');

        // Permite ver las propias o las que sean públicas
        $query->where(function ($q) use ($usuario) {
            $q->where('docente_id', $usuario->id)
              ->orWhere('publica', true);
        });

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->string('categoria'));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->string('tipo'));
        }

        $plantillas = $query->latest()->get();

        return response()->json([
            'data' => $plantillas
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'categoria' => 'required|string|in:actividad,rubrica,preguntas,curso',
            'tipo' => 'required|string|max:100',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'datos' => 'required|array',
            'publica' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plantilla = Plantilla::create([
            'docente_id' => $request->user()->id,
            'categoria' => $request->string('categoria'),
            'tipo' => $request->string('tipo'),
            'nombre' => $request->string('nombre'),
            'descripcion' => $request->input('descripcion'),
            'datos' => $request->input('datos'),
            'publica' => $request->boolean('publica', false),
        ]);

        return response()->json([
            'data' => $plantilla->load('docente:id,nombre,email,avatar')
        ], 201);
    }

    public function update(Request $request, Plantilla $plantilla): JsonResponse
    {
        $usuario = $request->user();

        // Solo el autor puede actualizar la plantilla
        if ((int)$plantilla->docente_id !== (int)$usuario->id && !$usuario->esAdmin()) {
            return response()->json(['message' => 'No estás autorizado a modificar esta plantilla.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'datos' => 'nullable|array',
            'publica' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [
            'nombre' => $request->string('nombre'),
            'descripcion' => $request->input('descripcion'),
            'publica' => $request->boolean('publica', $plantilla->publica),
        ];

        if ($request->has('datos')) {
            $data['datos'] = $request->input('datos');
        }

        $plantilla->update($data);

        return response()->json([
            'data' => $plantilla->load('docente:id,nombre,email,avatar')
        ]);
    }

    public function destroy(Request $request, Plantilla $plantilla): JsonResponse
    {
        $usuario = $request->user();

        if ((int)$plantilla->docente_id !== (int)$usuario->id && !$usuario->esAdmin()) {
            return response()->json(['message' => 'No estás autorizado a eliminar esta plantilla.'], 403);
        }

        $plantilla->delete();

        return response()->json(['message' => 'Plantilla eliminada con éxito.']);
    }

    public function usar(Plantilla $plantilla): JsonResponse
    {
        $plantilla->increment('uso_count');

        return response()->json([
            'data' => $plantilla
        ]);
    }
}
