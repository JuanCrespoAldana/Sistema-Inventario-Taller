@echo off
setlocal
cd /d "%~dp0"

set "DB_HOST=127.0.0.1"
set "DB_PORT=3306"
set "DB_DATABASE=sistema_taller"
set "DB_USERNAME=root"
set "DB_PASSWORD="
set "COPIA_EXTRA="

for %%K in (DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD) do (
    for /f "usebackq tokens=2 delims==" %%V in (`findstr /b /c:"%%K=" ".env"`) do set "%%K=%%V"
)

set "MYSQLDUMP="
for /d %%D in ("%~dp0..\..\bin\mysql\*" "C:\laragon\bin\mysql\*") do (
    if exist "%%~D\bin\mysqldump.exe" set "MYSQLDUMP=%%~D\bin\mysqldump.exe"
)
if not defined MYSQLDUMP (
    where mysqldump >nul 2>nul && set "MYSQLDUMP=mysqldump"
)
if not defined MYSQLDUMP (
    echo No se encontro mysqldump. Revisa que Laragon este instalado.
    if /i not "%~1"=="auto" pause
    exit /b 1
)

set "CARPETA=%~dp0respaldos"
if not exist "%CARPETA%" mkdir "%CARPETA%"

for /f %%F in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HHmm"') do set "FECHA=%%F"
set "ARCHIVO=%CARPETA%\%DB_DATABASE%_%FECHA%"

echo.
echo === Creando respaldo de la base de datos %DB_DATABASE% ===

if defined DB_PASSWORD (
    "%MYSQLDUMP%" -h %DB_HOST% -P %DB_PORT% -u %DB_USERNAME% --password=%DB_PASSWORD% --single-transaction --routines --default-character-set=utf8mb4 %DB_DATABASE% > "%ARCHIVO%.sql"
) else (
    "%MYSQLDUMP%" -h %DB_HOST% -P %DB_PORT% -u %DB_USERNAME% --single-transaction --routines --default-character-set=utf8mb4 %DB_DATABASE% > "%ARCHIVO%.sql"
)

if errorlevel 1 (
    echo.
    echo No se pudo crear el respaldo. Revisa que MySQL este encendido en Laragon.
    del "%ARCHIVO%.sql" >nul 2>nul
    if /i not "%~1"=="auto" pause
    exit /b 1
)

powershell -NoProfile -Command "Compress-Archive -Path '%ARCHIVO%.sql' -DestinationPath '%ARCHIVO%.zip' -Force"
del "%ARCHIVO%.sql" >nul 2>nul

if defined COPIA_EXTRA (
    if exist "%COPIA_EXTRA%" copy /y "%ARCHIVO%.zip" "%COPIA_EXTRA%" >nul
)

forfiles /p "%CARPETA%" /m "*.zip" /d -30 /c "cmd /c del @path" >nul 2>nul

echo.
echo Respaldo guardado en: %ARCHIVO%.zip
echo Se conservan los respaldos de los ultimos 30 dias.
echo.
if /i not "%~1"=="auto" pause
