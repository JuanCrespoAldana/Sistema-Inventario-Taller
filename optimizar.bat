@echo off
cd /d "%~dp0"
echo.
echo === Limpiando cache anterior ===
call php artisan optimize:clear
echo.
echo === Aplicando cambios pendientes de base de datos ===
call php artisan migrate --force
echo.
echo === Generando cache de configuracion, rutas y vistas ===
call php artisan optimize
call php artisan view:cache
echo.
if exist "%~dp0composer.phar" (
    echo === Optimizando autoload ===
    php composer.phar dump-autoload -o
) else (
    where composer >nul 2>nul && (
        echo === Optimizando autoload ===
        call composer dump-autoload -o
    )
)
echo.
echo Listo. Vuelve a ejecutar este archivo cada vez que actualices el programa o cambies el .env.
echo.
pause
