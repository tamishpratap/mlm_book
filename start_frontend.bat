@echo off
cd /d %~dp0frontend
echo Starting React Vite Frontend on http://localhost:5173 ...
"C:\Users\deepa\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe" .\node_modules\vite\bin\vite.js
pause
