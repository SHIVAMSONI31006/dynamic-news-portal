@echo off
title Start Dual Localhost Servers - News Portal
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0start-dual-servers.ps1"
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Server startup encountered an error.
    pause
)
