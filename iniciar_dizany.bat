@echo off
title Iniciar Sistema Dizany

cd /d C:\xampp\htdocs\dizany

:: Elimina symlink si ya existe (y está roto o incorrecto)
IF EXIST public\storage (
    rmdir public\storage
)

:: Crea el symlink
php artisan storage:link

:: Iniciar servidor de desarrollo
start cmd /k "php artisan serve"

:: Iniciar en segundo plano las tareas SUNAT (reintentos, resumen diario y tickets)
powershell -NoProfile -Command "Start-Process php -ArgumentList 'artisan','schedule:work' -WorkingDirectory 'C:\xampp\htdocs\dizany' -WindowStyle Hidden"

:: Abrir el navegador después de 2 segundos
timeout /t 2 >nul
start http://localhost:8000/login

pause
