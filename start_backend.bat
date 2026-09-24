@echo off
set XAMPP_LITE_ROOT=C:\xampp_lite_8_5
cd /d %~dp0backend
echo Starting Laravel Backend Server on http://127.0.0.1:8000 ...
C:\xampp_lite_8_5\apps\php\php.exe artisan serve --host=127.0.0.1 --port=8000
pause
