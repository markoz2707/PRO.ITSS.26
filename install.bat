@echo off
REM ITSS Project Management System - Installation Script for Windows
REM Copyright (c) 2024 ITSS Sp. z o.o.

echo ================================================
echo ITSS Project Management System - Instalacja
echo ================================================
echo.

REM Sprawdź czy PHP jest zainstalowane
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [BLAD] PHP nie jest zainstalowane.
    echo Zainstaluj PHP 8.0 lub nowszy: https://windows.php.net/download/
    pause
    exit /b 1
)

php -r "echo 'Wykryto PHP ' . PHP_VERSION . PHP_EOL;"
echo.

REM Sprawdź czy MySQL jest zainstalowane
where mysql >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [BLAD] MySQL/MariaDB nie jest zainstalowane.
    echo Zainstaluj MySQL/MariaDB lub XAMPP/WAMP
    pause
    exit /b 1
)

echo [OK] Wymagania systemowe spelniione
echo.

REM Konfiguracja bazy danych
echo Konfiguracja bazy danych
echo.

set /p DB_NAME="Nazwa bazy danych [itss_projects]: "
if "%DB_NAME%"=="" set DB_NAME=itss_projects

set /p DB_USER="Uzytkownik bazy danych [root]: "
if "%DB_USER%"=="" set DB_USER=root

set /p DB_PASSWORD="Haslo uzytkownika bazy danych: "

set /p DB_HOST="Host bazy danych [localhost]: "
if "%DB_HOST%"=="" set DB_HOST=localhost

echo.
echo Tworzenie bazy danych...
echo.

REM Tworzenie pliku SQL tymczasowego
echo CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; > temp_create_db.sql
echo USE %DB_NAME%; >> temp_create_db.sql

REM Import schematu
mysql -u %DB_USER% -p%DB_PASSWORD% < temp_create_db.sql
if %ERRORLEVEL% EQU 0 (
    echo [OK] Baza danych utworzona
) else (
    echo [BLAD] Nie udalo sie utworzyc bazy danych
    del temp_create_db.sql
    pause
    exit /b 1
)

del temp_create_db.sql

echo Importowanie schematu bazy danych...

mysql -u %DB_USER% -p%DB_PASSWORD% %DB_NAME% < database\schema.sql
if %ERRORLEVEL% EQU 0 (
    echo [OK] Schemat podstawowy zaimportowany
) else (
    echo [BLAD] Nie udalo sie zaimportowac schematu
    pause
    exit /b 1
)

mysql -u %DB_USER% -p%DB_PASSWORD% %DB_NAME% < database\schema_extended.sql
if %ERRORLEVEL% EQU 0 (
    echo [OK] Rozszerzenia schematu zaimportowane
) else (
    echo [UWAGA] Nie udalo sie zaimportowac rozsszerzen (moga juz istniec)
)

echo.

REM Konfiguracja aplikacji
if not exist config\config.php (
    copy config\config.example.php config\config.php
    echo [OK] Utworzono plik konfiguracyjny
) else (
    echo [UWAGA] Plik config\config.php juz istnieje
)

echo.
echo Aktualizowanie konfiguracji bazy danych...

REM Aktualizacja konfiguracji (używamy PowerShell do edycji pliku)
powershell -Command "(Get-Content config\config.php) -replace \"'name' => 'itss_projects'\", \"'name' => '%DB_NAME%'\" | Set-Content config\config.php"
powershell -Command "(Get-Content config\config.php) -replace \"'user' => 'root'\", \"'user' => '%DB_USER%'\" | Set-Content config\config.php"
powershell -Command "(Get-Content config\config.php) -replace \"'password' => ''\", \"'password' => '%DB_PASSWORD%'\" | Set-Content config\config.php"
powershell -Command "(Get-Content config\config.php) -replace \"'host' => 'localhost'\", \"'host' => '%DB_HOST%'\" | Set-Content config\config.php"

echo [OK] Konfiguracja bazy danych zaktualizowana
echo.

REM Tworzenie katalogow
if not exist uploads mkdir uploads
if not exist uploads\documents mkdir uploads\documents
if not exist uploads\invoices mkdir uploads\invoices
if not exist logs mkdir logs

echo [OK] Katalogi utworzone
echo.

REM Composer (jesli dostepny)
where composer >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo Instalowanie zaleznosci Composer...
    composer install --no-dev --optimize-autoloader
    echo [OK] Zaleznosci zainstalowane
) else (
    echo [UWAGA] Composer nie jest zainstalowany, pomijam
)

echo.
echo ================================================
echo Instalacja zakonczona pomyslnie!
echo ================================================
echo.
echo Nastepne kroki:
echo.
echo 1. Skonfiguruj Azure AD w pliku config\config.php
echo 2. Skonfiguruj Dynamics CRM (sekcja 'dynamics_crm')
echo 3. Skonfiguruj ServiceDesk Plus (sekcja 'servicedesk')
echo 4. Uruchom serwer WWW (np. XAMPP, WAMP lub php -S localhost:8000 -t public)
echo 5. Otworz aplikacje w przegladarce: http://localhost:8000
echo 6. Zaloguj sie przez Microsoft 365
echo 7. Nadaj sobie role 'admin' w bazie danych
echo.
echo Dokumentacja:
echo - README.md - Ogolny przeglad
echo - INSTALLATION.md - Szczegolowa instalacja
echo - IMPORT_GUIDE.md - Przewodnik importu faktur
echo.
echo W razie problemow sprawdz logi w katalogu: logs\
echo.

pause
