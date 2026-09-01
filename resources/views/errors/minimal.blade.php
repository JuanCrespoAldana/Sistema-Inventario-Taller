<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('titulo', 'Ocurrió un problema') · Sistema Taller</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #1e293b;
        }
        .error-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            padding: 48px 40px;
            max-width: 420px;
            width: 90%;
            text-align: center;
        }
        .error-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 20px;
        }
        .error-codigo {
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .error-titulo {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .error-mensaje {
            color: #64748b;
            font-size: 0.92rem;
            margin-bottom: 26px;
            line-height: 1.5;
        }
        .btn-volver {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .btn-volver:hover {
            background: #1d4ed8;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="bi @yield('icono', 'bi-exclamation-triangle')"></i>
        </div>
        <div class="error-codigo">Error @yield('codigo', '')</div>
        <div class="error-titulo">@yield('titulo', 'Ocurrió un problema')</div>
        <div class="error-mensaje">@yield('mensaje', 'Intenta nuevamente en unos momentos.')</div>
        <a href="{{ url('/') }}" class="btn-volver">
            <i class="bi bi-house-door"></i> Volver al inicio
        </a>
    </div>
</body>
</html>
