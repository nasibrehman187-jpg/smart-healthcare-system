@echo off
:: ============================================================================
:: Smart Healthcare & Diagnostic Management System
:: Standalone Portable Edition for USB / Windows PC
:: Zero Installation | Zero Configuration | Zero Internet Required
:: ============================================================================

title Smart Healthcare System - Starting...
color 0A
cls

echo ============================================================================
echo      Smart Healthcare ^& Diagnostic Management System - Portable Edition
echo ============================================================================
echo.

set "ROOT=%~dp0"
set "PHP_DIR=%ROOT%portable\php"
set "MYSQL_DIR=%ROOT%portable\mysql"
set "DATA_DIR=%ROOT%portable\mysql\data"
set "APP_DIR=%ROOT%healthcare"

:: ----------------------------------------------------------------------------
:: 1. Verify Portable Files Exist
:: ----------------------------------------------------------------------------
if not exist "%PHP_DIR%\php.exe" (
    echo [ERROR] Portable PHP not found in "%PHP_DIR%"!
    echo Please make sure the "portable" folder is copied next to START.bat.
    pause
    exit /b 1
)

if not exist "%MYSQL_DIR%\bin\mysqld.exe" (
    echo [ERROR] Portable MySQL not found in "%MYSQL_DIR%"!
    echo Please make sure the "portable" folder is copied next to START.bat.
    pause
    exit /b 1
)

:: ----------------------------------------------------------------------------
:: 2. Start Portable MySQL (Port 3306)
:: ----------------------------------------------------------------------------
echo [1/3] Checking MySQL database service...

:: Check if port 3306 is already responding
netstat -ano | findstr ":3306" | findstr "LISTENING" >nul
if %errorlevel% neq 0 (
    echo       Starting portable MariaDB on port 3306...
    start "SmartHealthcare-MySQL" /min "%MYSQL_DIR%\bin\mysqld.exe" --defaults-file="%MYSQL_DIR%\my.ini" --datadir="%DATA_DIR%" --console
    timeout /t 3 /nobreak >nul
    echo       [OK] Database server started.
) else (
    echo       [OK] MySQL is already active on port 3306.
)

:: ----------------------------------------------------------------------------
:: 3. Start Portable PHP Web Server (Port 8080)
:: ----------------------------------------------------------------------------
echo [2/3] Starting portable web server on http://localhost:8080...

:: Kill any existing instance on port 8080
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8080" ^| findstr "LISTENING"') do (
    taskkill /PID %%a /F >nul 2>&1
)

start "SmartHealthcare-WebServer" /min "%PHP_DIR%\php.exe" -S localhost:8080 -t "%APP_DIR%" -c "%PHP_DIR%\php.ini"
timeout /t 2 /nobreak >nul
echo       [OK] Web server started.

:: ----------------------------------------------------------------------------
:: 4. Launch Browser
:: ----------------------------------------------------------------------------
echo [3/3] Launching web application in your default browser...
start "" "http://localhost:8080/"

echo.
echo ============================================================================
echo   Application is LIVE at:  http://localhost:8080/
echo ============================================================================
echo.
echo   PRE-CONFIGURED TEST CREDENTIALS:
echo   ------------------------------------------------------------------------
echo   Admin Portal:   admin@healthcare.com       ^| Password: password123
echo   Doctor Portal:  engrazhariqbal34@gmail.com ^| Password: password123
echo   Patient Portal: nasibrehman187@gmail.com   ^| Password: password123
echo   ------------------------------------------------------------------------
echo.
echo   NOTE: Keep this window open while using the system.
echo   To shut down cleanly, double-click STOP.bat or press any key below.
echo ============================================================================
echo.
pause

:: Run clean shutdown if user presses a key in this window
call "%ROOT%STOP.bat"
