@echo off
cd /d %~dp0bukaiapp
echo Starting Flutter Mobile App on Chrome (http://localhost:8085) ...
flutter run -d chrome --web-port=8085
pause
