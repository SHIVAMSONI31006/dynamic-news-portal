@echo off
title Start Both Applications (8000 and 9000)
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0start-both.ps1"
pause
