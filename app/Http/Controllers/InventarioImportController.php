<?php

namespace App\Http\Controllers;

use App\Services\Excel\ExcelHelper;
use App\Services\Inventario\InventarioExcelService;
use App\Services\Inventario\InventarioImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class InventarioImportController extends Controller
{
    private const MODOS_STOCK = ['sumar', 'fijar', 'ignorar'];

    public function index()
    {
        return view('inventario.importar', [
            'maxFilas' => InventarioImportService::MAX_FILAS,
        ]);
    }

    public function plantilla(InventarioExcelService $excel)
    {
        try {
            return ExcelHelper::descargar($excel->plantilla(), 'plantilla_inventario.xlsx');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No fue posible generar la plantilla. Intenta nuevamente.');
        }
    }

    public function previsualizar(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'max:5120', 'mimes:xlsx,xls,csv,txt'],
            'stock' => ['required', 'in:' . implode(',', self::MODOS_STOCK)],
        ], [
            'archivo.required' => 'Seleccione el archivo de Excel que desea importar.',
            'archivo.file' => 'El archivo no se pudo cargar. Intente de nuevo.',
            'archivo.uploaded' => 'El archivo no se pudo cargar porque supera el tamaño permitido por el servidor. Divídalo en partes más pequeñas.',
            'archivo.max' => 'El archivo pesa más de 5 MB. Divídalo en partes más pequeñas.',
            'archivo.mimes' => 'El formato no es compatible. Use un archivo .xlsx, .xls o .csv.',
            'stock.required' => 'Elija qué hacer con el stock.',
            'stock.in' => 'La opción de stock elegida no es válida.',
        ]);

        ExcelHelper::preparar();
        $this->limpiarAntiguos();

        $extension = strtolower($request->file('archivo')->getClientOriginalExtension());
        $extension = in_array($extension, ['xlsx', 'xls', 'csv'], true) ? $extension : 'csv';

        $token = bin2hex(random_bytes(20));
        $directorio = $this->directorio();
        File::ensureDirectoryExists($directorio);

        $request->file('archivo')->move($directorio, "{$token}.{$extension}");

        $opciones = [
            'stock' => $request->input('stock'),
            'actualizar_datos' => $request->boolean('actualizar_datos'),
        ];

        file_put_contents("{$directorio}/{$token}.json", json_encode($opciones));

        try {
            app(InventarioImportService::class)->analizar("{$directorio}/{$token}.{$extension}", $opciones);
        } catch (\RuntimeException $e) {
            $this->borrar($token);

            return back()->withInput($request->except('archivo'))->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $this->borrar($token);

            return back()->withInput($request->except('archivo'))->with('error', 'No se pudo leer el archivo. Verifique que sea la plantilla de Excel y que no esté dañado.');
        }

        return redirect()->route('inventario.importar.vista', $token);
    }

    public function vista(string $token)
    {
        [$ruta, $opciones] = $this->localizar($token);

        if (!$ruta) {
            return redirect()->route('inventario.importar.index')
                ->with('error', 'La vista previa ya no está disponible. Cargue el archivo de nuevo.');
        }

        ExcelHelper::preparar();

        try {
            $analisis = app(InventarioImportService::class)->analizar($ruta, $opciones);
        } catch (\Throwable $e) {
            report($e);
            $this->borrar($token);

            return redirect()->route('inventario.importar.index')
                ->with('error', $e instanceof \RuntimeException ? $e->getMessage() : 'No se pudo leer el archivo.');
        }

        $orden = ['error' => 0, 'crear' => 1, 'actualizar' => 2, 'sin_cambios' => 3];
        $filas = collect($analisis['filas'])
            ->sortBy(fn($f) => $orden[$f['accion']] * 100000 + $f['fila'])
            ->values();

        $limite = 400;

        return view('inventario.importar-vista', [
            'token' => $token,
            'resumen' => $analisis['resumen'],
            'opciones' => $analisis['opciones'],
            'columnas' => $analisis['columnas'],
            'ignoradas' => $analisis['ignoradas'],
            'filas' => $filas->take($limite),
            'ocultas' => max(0, $filas->count() - $limite),
            'importables' => $analisis['resumen']['crear'] + $analisis['resumen']['actualizar'],
        ]);
    }

    public function confirmar(Request $request)
    {
        $token = (string) $request->input('token');
        [$ruta, $opciones] = $this->localizar($token);

        if (!$ruta) {
            return redirect()->route('inventario.importar.index')
                ->with('error', 'La vista previa ya no está disponible. Cargue el archivo de nuevo.');
        }

        ExcelHelper::preparar();

        try {
            $servicio = app(InventarioImportService::class);
            $analisis = $servicio->analizar($ruta, $opciones);
            $resultado = $servicio->aplicar($analisis);
        } catch (\RuntimeException $e) {
            return redirect()->route('inventario.importar.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('inventario.importar.index')
                ->with('error', 'No se pudo completar la importación y no se guardó ningún cambio. Intente de nuevo.');
        }

        $this->borrar($token);

        $partes = [];
        if ($resultado['creados']) {
            $partes[] = $resultado['creados'] . ($resultado['creados'] === 1 ? ' producto creado' : ' productos creados');
        }
        if ($resultado['actualizados']) {
            $partes[] = $resultado['actualizados'] . ($resultado['actualizados'] === 1 ? ' producto actualizado' : ' productos actualizados');
        }

        $mensaje = $partes ? 'Importación completada: ' . implode(' y ', $partes) . '.' : 'No había cambios para guardar.';

        if ($analisis['resumen']['errores'] > 0) {
            $mensaje .= ' Quedaron ' . $analisis['resumen']['errores'] . ' filas con errores sin importar.';
        }

        return redirect()->route('inventario.index')->with('success', $mensaje);
    }

    public function cancelar(Request $request)
    {
        $token = (string) $request->input('token');
        if ($this->tokenValido($token)) {
            $this->borrar($token);
        }

        return redirect()->route('inventario.importar.index');
    }

    private function directorio(): string
    {
        return storage_path('app/importaciones');
    }

    private function tokenValido(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{40}$/', $token);
    }

    private function localizar(string $token): array
    {
        if (!$this->tokenValido($token)) {
            return [null, []];
        }

        $directorio = $this->directorio();
        $archivos = glob("{$directorio}/{$token}.{xlsx,xls,csv}", GLOB_BRACE) ?: [];

        if (!$archivos || !is_file("{$directorio}/{$token}.json")) {
            return [null, []];
        }

        $opciones = json_decode((string) file_get_contents("{$directorio}/{$token}.json"), true) ?: [];

        return [$archivos[0], $opciones];
    }

    private function borrar(string $token): void
    {
        foreach (glob($this->directorio() . "/{$token}.*") ?: [] as $archivo) {
            @unlink($archivo);
        }
    }

    private function limpiarAntiguos(): void
    {
        foreach (glob($this->directorio() . '/*') ?: [] as $archivo) {
            if (is_file($archivo) && filemtime($archivo) < now()->subDay()->timestamp) {
                @unlink($archivo);
            }
        }
    }
}
