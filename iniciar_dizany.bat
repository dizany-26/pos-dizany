@echo off
title Iniciar Sistema Dizany

cd /d C:\xampp\htdocs\dizany

:: Elimina symlink si ya existe (y está roto o incorrecto)
IF EXIST public\storage (
    rmdir public\storage
)

:: Crea el symlink
php artisan storage:link

:: Iniciar servidor accesible desde esta PC y desde los dispositivos de la red local
start cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

:: Iniciar en segundo plano las tareas SUNAT (reintentos, resumen diario y tickets)
powershell -NoProfile -Command "Start-Process php -ArgumentList 'artisan','schedule:work' -WorkingDirectory 'C:\xampp\htdocs\dizany' -WindowStyle Hidden"

:: Abrir el navegador después de 2 segundos
timeout /t 2 >nul
start http://localhost:8000/login

pause
