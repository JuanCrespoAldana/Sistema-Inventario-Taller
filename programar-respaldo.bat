@echo off
cd /d "%~dp0"
echo.
echo === Programando el respaldo diario (7:00 p. m.) ===
powershell -NoProfile -Command "$a = New-ScheduledTaskAction -Execute '%~dp0respaldo.bat' -Argument 'auto'; $t = New-ScheduledTaskTrigger -Daily -At 7pm; $s = New-ScheduledTaskSettingsSet -StartWhenAvailable; Register-ScheduledTask -TaskName 'Respaldo Sistema Taller' -Action $a -Trigger $t -Settings $s -Force | Out-Null"
if errorlevel 1 (
    echo.
    echo No se pudo programar la tarea. Ejecuta este archivo como administrador.
) else (
    echo.
    echo Listo. Cada dia a las 7:00 p. m. se guardara un respaldo en la carpeta respaldos.
    echo Si el PC esta apagado a esa hora, el respaldo se hace al encenderlo.
)
echo.
pause
