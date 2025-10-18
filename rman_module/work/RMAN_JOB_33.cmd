@echo off
chcp 65001 >nul
powershell -ExecutionPolicy Bypass -File "C:\wamp64\www\evaluador_riesgos\rman_module\config/../bin/run_rman.ps1" -Script "C:\wamp64\www\evaluador_riesgos\rman_module\config/../work/rmadb01060.rma" -Log "C:\wamp64\www\evaluador_riesgos\rman_module\config/../work/rmadb01060.log" ^& exit /b %ERRORLEVEL%
