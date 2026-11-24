<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Ocurrió un problema') | Sistema Taller</title>

    <link href="{{ asset('vendor/bootstrap/bootstrap.lite.min.css') }}?v={{ filemtime(public_path('vendor/bootstrap/bootstrap.lite.min.css')) }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons-lite/bootstrap-icons.min.css') }}?v={{ filemtime(public_path('vendor/bootstrap-icons-lite/bootstrap-icons.min.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
</head>

<body>
    <div class="error-page">
        <div class="error-card">
            <div class="error-code">@yield('codigo', '')</div>
            <h1 class="h4 fw-bold mt-3 mb-2">@yield('titulo', 'Ocurrió un problema')</h1>
            <p class="text-secondary mb-4">@yield('mensaje', 'Intenta nuevamente en unos momentos.')</p>

            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="javascript:history.back()" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <i class="bi bi-house-door"></i> Ir al inicio
                </a>
            </div>
        </div>
    </div>
</body>

</html>
