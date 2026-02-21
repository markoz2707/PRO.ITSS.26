#!/bin/bash

# ITSS Project Management System - Installation Script
# Copyright (c) 2024 ITSS Sp. z o.o.

set -e

echo "================================================"
echo "ITSS Project Management System - Instalacja"
echo "================================================"
echo ""

# Kolory
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Funkcje pomocnicze
error() {
    echo -e "${RED}[BŁĄD]${NC} $1"
    exit 1
}

success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[UWAGA]${NC} $1"
}

info() {
    echo -e "[INFO] $1"
}

# Sprawdź czy skrypt jest uruchamiany jako root
if [ "$EUID" -eq 0 ]; then
    warning "Nie uruchamiaj tego skryptu jako root. Użyj sudo tylko gdy będzie potrzebne."
fi

# Sprawdź wymagania systemowe
info "Sprawdzanie wymagań systemowych..."

# PHP
if ! command -v php &> /dev/null; then
    error "PHP nie jest zainstalowane. Zainstaluj PHP 8.0 lub nowszy."
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
info "Wykryto PHP $PHP_VERSION"

# MySQL/MariaDB
if ! command -v mysql &> /dev/null; then
    error "MySQL/MariaDB nie jest zainstalowane."
fi

success "Wymagania systemowe spełnione"
echo ""

# Konfiguracja bazy danych
info "Konfiguracja bazy danych"
echo ""

read -p "Nazwa bazy danych [itss_projects]: " DB_NAME
DB_NAME=${DB_NAME:-itss_projects}

read -p "Użytkownik bazy danych [itss_user]: " DB_USER
DB_USER=${DB_USER:-itss_user}

read -sp "Hasło użytkownika bazy danych: " DB_PASSWORD
echo ""

read -p "Host bazy danych [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

# Tworzenie bazy danych
info "Tworzenie bazy danych..."
echo ""

read -sp "Podaj hasło root MySQL: " MYSQL_ROOT_PASSWORD
echo ""

mysql -u root -p"$MYSQL_ROOT_PASSWORD" << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'$DB_HOST' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'$DB_HOST';
FLUSH PRIVILEGES;
EOF

if [ $? -eq 0 ]; then
    success "Baza danych utworzona"
else
    error "Nie udało się utworzyć bazy danych"
fi

# Import schematu
info "Importowanie schematu bazy danych..."

mysql -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < database/schema.sql
if [ $? -eq 0 ]; then
    success "Schemat podstawowy zaimportowany"
else
    error "Nie udało się zaimportować schematu podstawowego"
fi

mysql -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < database/schema_extended.sql
if [ $? -eq 0 ]; then
    success "Rozszerzenia schematu zaimportowane"
else
    warning "Nie udało się zaimportować rozszerzeń schematu (może już istnieją)"
fi

mysql -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < database/schema_reconciliation.sql
if [ $? -eq 0 ]; then
    success "Schemat uspójniania danych zaimportowany"
else
    warning "Nie udało się zaimportować schematu uspójniania (może już istnieje)"
fi

echo ""

# Konfiguracja aplikacji
info "Konfiguracja aplikacji..."

if [ ! -f config/config.php ]; then
    cp config/config.example.php config/config.php
    success "Utworzono plik konfiguracyjny"
else
    warning "Plik config/config.php już istnieje, pomijam"
fi

# Aktualizacja konfiguracji bazy danych
info "Aktualizowanie konfiguracji bazy danych..."

sed -i "s/'name' => 'itss_projects'/'name' => '$DB_NAME'/g" config/config.php
sed -i "s/'user' => 'root'/'user' => '$DB_USER'/g" config/config.php
sed -i "s/'password' => ''/'password' => '$DB_PASSWORD'/g" config/config.php
sed -i "s/'host' => 'localhost'/'host' => '$DB_HOST'/g" config/config.php

success "Konfiguracja bazy danych zaktualizowana"
echo ""

# Uprawnienia katalogów
info "Ustawianie uprawnień katalogów..."

chmod -R 755 .
chmod -R 775 uploads
chmod -R 775 logs
chmod 600 config/config.php

if command -v chown &> /dev/null; then
    read -p "Użytkownik serwera WWW [www-data]: " WWW_USER
    WWW_USER=${WWW_USER:-www-data}

    sudo chown -R $WWW_USER:$WWW_USER uploads
    sudo chown -R $WWW_USER:$WWW_USER logs

    success "Uprawnienia ustawione"
else
    warning "Nie można ustawić właściciela plików (brak polecenia chown)"
fi

echo ""

# Azure AD
info "Konfiguracja Azure AD (opcjonalne)"
echo ""
echo "Aby skonfigurować integrację z Microsoft 365:"
echo "1. Przejdź do https://portal.azure.com"
echo "2. Azure Active Directory → App registrations → New registration"
echo "3. Skopiuj Application (client) ID, Directory (tenant) ID"
echo "4. Utwórz Client Secret"
echo "5. Wprowadź te dane w pliku config/config.php w sekcji 'azure'"
echo ""

read -p "Czy chcesz wprowadzić dane Azure AD teraz? (t/n): " CONFIGURE_AZURE
if [ "$CONFIGURE_AZURE" = "t" ]; then
    read -p "Azure Tenant ID: " AZURE_TENANT_ID
    read -p "Azure Client ID: " AZURE_CLIENT_ID
    read -sp "Azure Client Secret: " AZURE_CLIENT_SECRET
    echo ""

    sed -i "s/'tenant_id' => ''/'tenant_id' => '$AZURE_TENANT_ID'/g" config/config.php
    sed -i "s/'client_id' => ''/'client_id' => '$AZURE_CLIENT_ID'/g" config/config.php
    sed -i "s/'client_secret' => ''/'client_secret' => '$AZURE_CLIENT_SECRET'/g" config/config.php

    success "Konfiguracja Azure AD zapisana"
fi

echo ""

# Composer
if [ -f composer.json ]; then
    if command -v composer &> /dev/null; then
        info "Instalowanie zależności Composer..."
        composer install --no-dev --optimize-autoloader
        success "Zależności zainstalowane"
    else
        warning "Composer nie jest zainstalowany, pomijam"
    fi
fi

echo ""
echo "================================================"
echo -e "${GREEN}Instalacja zakończona pomyślnie!${NC}"
echo "================================================"
echo ""
echo "Następne kroki:"
echo ""
echo "1. Skonfiguruj Azure AD w pliku config/config.php"
echo "2. Skonfiguruj Dynamics CRM (sekcja 'dynamics_crm')"
echo "3. Skonfiguruj ServiceDesk Plus (sekcja 'servicedesk')"
echo "4. Skonfiguruj serwer WWW (Apache/Nginx)"
echo "5. Otwórz aplikację w przeglądarce"
echo "6. Zaloguj się przez Microsoft 365"
echo "7. Nadaj sobie rolę 'admin' w bazie danych:"
echo "   UPDATE users SET role = 'admin' WHERE email = 'twoj.email@itss.pl';"
echo ""
echo "Dokumentacja:"
echo "- README.md - Ogólny przegląd"
echo "- INSTALLATION.md - Szczegółowa instalacja"
echo "- IMPORT_GUIDE.md - Przewodnik importu faktur"
echo ""
echo "W razie problemów sprawdź logi w katalogu: logs/"
echo ""
