@echo off
REM Webhook Log Viewer for Windows
REM Usage: view-webhook-logs.bat [lines]

setlocal enabledelayedexpansion

set LINES=%1
if "%LINES%"=="" set LINES=50

for /f "tokens=1-3 delims=/ " %%a in ('date /t') do (
    set YEAR=%%c
    set MONTH=%%a
    set DAY=%%b
)
if "%MONTH:~0,1%"==" " set MONTH=0%MONTH:~1,1%
if "%DAY:~0,1%"==" " set DAY=0%DAY:~1,1%

set LOG_FILE=storage\logs\webhook-%YEAR%-%MONTH%-%DAY%.log

echo ==================================================
echo 🔍 Paystack Webhook Logs Viewer
echo ==================================================
echo.

if exist "%LOG_FILE%" (
    echo 📄 Viewing last %LINES% lines from: %LOG_FILE%
    echo ==================================================
    echo.

    REM Display last N lines (Windows doesn't have tail, so use PowerShell)
    powershell -Command "Get-Content '%LOG_FILE%' -Tail %LINES%"

    echo.
    echo ==================================================
    echo 📊 Statistics:

    REM Count webhooks
    for /f %%i in ('findstr /C:"WEBHOOK RECEIVED" "%LOG_FILE%" ^| find /c /v ""') do set TOTAL=%%i
    echo - Total webhooks today: !TOTAL!

    for /f %%i in ('findstr /C:"Event processed successfully" "%LOG_FILE%" ^| find /c /v ""') do set SUCCESS=%%i
    echo - Successful: !SUCCESS!

    for /f %%i in ('findstr /C:"ERROR" "%LOG_FILE%" ^| find /c /v ""') do set ERRORS=%%i
    echo - Errors: !ERRORS!

    echo.
    echo 💡 Tip: Run 'view-webhook-logs.bat 100' to see last 100 lines
    echo 💡 Tip: Check full log at: %LOG_FILE%
) else (
    echo ⚠️  Log file not found: %LOG_FILE%
    echo.
    echo Possible reasons:
    echo 1. No webhooks received today
    echo 2. Webhooks not configured in Paystack
    echo 3. Storage directory not writable
    echo.
    echo 📋 Available webhook logs:
    dir /b storage\logs\webhook-*.log 2>nul || echo   (none found)
    echo.
    echo 🔧 Next steps:
    echo 1. Visit: https://yourdomain.com/test-webhook.php
    echo 2. Configure webhooks in Paystack dashboard
    echo 3. Send a test webhook from Paystack
)

echo ==================================================
pause
