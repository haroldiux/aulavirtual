<?php

namespace App\Http\Controllers\Api;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Curso;
use App\Models\Matricula;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminUsuarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Usuario::query();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('rol')) {
            $query->where('rol', $request->string('rol'));
        }

        $usuarios = $query->latest()->paginate($request->integer('per_page', 25));

        return response()->json([
            'data' => $usuarios->items(),
            'meta' => [
                'page' => $usuarios->currentPage(),
                'per_page' => $usuarios->perPage(),
                'total' => $usuarios->total(),
                'last_page' => $usuarios->lastPage(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'rol' => 'required|string|in:estudiante,docente,director,admin',
            'carrera_id' => 'nullable|integer',
            'sede_id' => 'nullable|integer',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $password = $request->filled('password') 
            ? Hash::make($request->string('password')) 
            : Hash::make('unitepc123');

        $usuario = Usuario::create([
            'nombre' => $request->string('nombre'),
            'email' => $request->string('email'),
            'rol' => $request->string('rol'),
            'carrera_id' => $request->input('carrera_id'),
            'sede_id' => $request->input('sede_id'),
            'password' => $password,
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($request->string('nombre')) . '&background=6B3FA0&color=fff',
            'activo' => true,
        ]);

        return response()->json(['data' => $usuario], 201);
    }

    public function update(Request $request, Usuario $usuario): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email,' . $usuario->id,
            'rol' => 'required|string|in:estudiante,docente,director,admin',
            'carrera_id' => 'nullable|integer',
            'sede_id' => 'nullable|integer',
            'password' => 'nullable|string|min:6',
            'activo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [
            'nombre' => $request->string('nombre'),
            'email' => $request->string('email'),
            'rol' => $request->string('rol'),
            'carrera_id' => $request->input('carrera_id'),
            'sede_id' => $request->input('sede_id'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->string('password'));
        }

        if ($request->has('activo')) {
            $data['activo'] = $request->boolean('activo');
        }

        $usuario->update($data);

        return response()->json(['data' => $usuario->fresh()]);
    }

    public function destroy(Usuario $usuario): JsonResponse
    {
        // Evitar auto-eliminación si es el usuario autenticado
        if (auth()->id() === $usuario->id) {
            return response()->json(['message' => 'No puedes eliminar tu propio usuario.'], 403);
        }

        // Evitar quedar sin admins
        if ($usuario->rol === Rol::Admin && Usuario::where('rol', 'admin')->count() <= 1) {
            return response()->json(['message' => 'No puedes eliminar al único administrador del sistema.'], 403);
        }

        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado con éxito.']);
    }

    public function importarCsv(Request $request): JsonResponse
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt,xlsx',
        ]);

        $file = $request->file('csv');
        $path = $file->getRealPath();

        $rows = array_map(function ($line) {
            return str_getcsv($line, ';'); // Soporta separador ; o ,
        }, file($path));

        if (count($rows) <= 1) {
            return response()->json(['message' => 'El archivo está vacío o no tiene registros.'], 400);
        }

        $headers = array_shift($rows);
        // Limpiar headers (remover comillas, espacios)
        $headers = array_map(fn($h) => trim(str_replace('"', '', $h)), $headers);

        $creados = 0;
        $actualizados = 0;
        $errores = [];

        foreach ($rows as $index => $row) {
            if (empty($row) || count($row) < count($headers)) {
                continue;
            }

            $data = array_combine($headers, array_slice($row, 0, count($headers)));
            // Limpiar datos
            $data = array_map(fn($val) => trim(str_replace('"', '', $val)), $data);

            $validator = Validator::make($data, [
                'nombre' => 'required|string|max:255',
                'email' => 'required|email',
                'rol' => 'required|string|in:estudiante,docente,director,admin',
                'carrera_id' => 'nullable|integer',
                'sede_id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                $errores[] = [
                    'fila' => $index + 2,
                    'email' => $data['email'] ?? 'Desconocido',
                    'motivo' => implode(', ', $validator->errors()->all())
                ];
                continue;
            }

            $user = Usuario::where('email', $data['email'])->first();

            if ($user) {
                $user->update([
                    'nombre' => $data['nombre'],
                    'rol' => $data['rol'],
                    'carrera_id' => !empty($data['carrera_id']) ? intval($data['carrera_id']) : null,
                    'sede_id' => !empty($data['sede_id']) ? intval($data['sede_id']) : null,
                ]);
                $actualizados++;
            } else {
                Usuario::create([
                    'nombre' => $data['nombre'],
                    'email' => $data['email'],
                    'rol' => $data['rol'],
                    'password' => Hash::make('unitepc123'),
                    'carrera_id' => !empty($data['carrera_id']) ? intval($data['carrera_id']) : null,
                    'sede_id' => !empty($data['sede_id']) ? intval($data['sede_id']) : null,
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($data['nombre']) . '&background=6B3FA0&color=fff',
                    'activo' => true,
                ]);
                $creados++;
            }
        }

        return response()->json([
            'message' => 'Procesamiento de CSV completado.',
            'data' => [
                'creados' => $creados,
                'actualizados' => $actualizados,
                'errores' => $errores,
            ]
        ]);
    }

    public function matricularMasivo(Request $request, Curso $curso): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'usuario_ids' => 'required|array',
            'usuario_ids.*' => 'exists:usuarios,id',
            'accion' => 'required|string|in:matricular,desmatricular',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $usuarioIds = $request->input('usuario_ids');
        $accion = $request->string('accion');

        $procesados = 0;

        foreach ($usuarioIds as $uid) {
            if ($accion === 'matricular') {
                // Si ya está matriculado, asegurarse de que esté activo
                Matricula::updateOrCreate(
                    ['curso_id' => $curso->id, 'estudiante_id' => $uid],
                    ['estado' => 'activo', 'fecha_matricula' => now()]
                );
                $procesados++;
            } else {
                // Eliminar o inactivar
                Matricula::where('curso_id', $curso->id)
                    ->where('estudiante_id', $uid)
                    ->delete();
                $procesados++;
            }
        }

        return response()->json([
            'message' => 'Proceso masivo completado.',
            'data' => [
                'procesados' => $procesados,
                'accion' => $accion
            ]
        ]);
    }
}
