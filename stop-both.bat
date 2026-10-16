@echo off
title Stop Both Applications (8000 and 9000)
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0stop-both.ps1"
pause
