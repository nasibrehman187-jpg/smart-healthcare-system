@echo off
:: ============================================================================
:: Smart Healthcare & Diagnostic Management System
:: Clean Shutdown Script
:: ============================================================================

title Smart Healthcare System - Stopping...
color 0C
cls

echo ============================================================================
echo      Shutting Down Smart Healthcare System...
echo ============================================================================
echo.

set "ROOT=%~dp0"
set "MYSQL_ADMIN=%ROOT%portable\mysql\bin\mysqladmin.exe"

:: 1. Stop PHP Web Server
echo [1/2] Stopping portable web server...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8080" ^| findstr "LISTENING" 2^>nul') do (
    taskkill /PID %%a /F >nul 2>&1
)
echo       [OK] Web server stopped.

:: 2. Cleanly Stop MariaDB Server
echo [2/2] Stopping portable database server...
if exist "%MYSQL_ADMIN%" (
    "%MYSQL_ADMIN%" --host=127.0.0.1 --port=3306 -u root shutdown >nul 2>&1
)

:: Ensure any remaining portable mysqld process is stopped
for /f "tokens=2" %%a in ('tasklist /FI "IMAGENAME eq mysqld.exe" /FO LIST 2^>nul ^| findstr "PID:"') do (
    wmic process where "ProcessId=%%a and ExecutablePath like '%%portable%%'" call terminate >nul 2>&1
)
echo       [OK] Database server safely stopped.

echo.
echo ============================================================================
echo   All portable services have been stopped safely.
echo   You can now safely eject your USB drive.
echo ============================================================================
echo.
timeout /t 3 /nobreak >nul
