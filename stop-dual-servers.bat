@echo off
title Stop Dual Localhost Servers - News Portal
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0stop-dual-servers.ps1"
echo.
pause
