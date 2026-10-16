@echo off
title Stop Both Applications (8000 and 9000)
cd /d "%~dp0"
call "%~dp0stop-dual-servers.bat"
