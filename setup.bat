@echo off
REM BetLedger Setup Script for Windows
REM Run this script to set up BetLedger on Windows

echo =========================================
echo BetLedger - Windows Setup Script
echo =========================================
echo.

REM Check PHP
php --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please install PHP 8.0+ or add it to your system PATH
    pause
    exit /b 1
)

for /f "tokens=2" %%i in ('php -v ^| find "PHP"') do (
    echo PHP %%i found
)

REM Create .env file if not exists
if not exist .env (
    echo.
    echo Creating .env file from .env.example...
    copy .env.example .env
    echo Edit .env with your database credentials
)

REM Create directories
if not exist logs mkdir logs
echo Created logs directory

if not exist uploads mkdir uploads
echo Created uploads directory

echo.
echo =========================================
echo Setup Steps:
echo =========================================
echo.
echo 1. Edit .env file with your database credentials
echo 2. Start MySQL/MariaDB
echo 3. Import database schema:
echo    mysql -u root -p database_name ^< database\schema.sql
echo 4. Start PHP server:
echo    php -S localhost:8000 -t public
echo 5. Open http://localhost:8000 in your browser
echo 6. Register and start tracking bets!
echo.
echo For more details, see README.md
echo.
pause
