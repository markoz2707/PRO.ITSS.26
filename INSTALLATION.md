# Instrukcja instalacji ITSS Project Management System

## Automatyczna instalacja (Zalecane)

### Linux/macOS
```bash
chmod +x install.sh
./install.sh
```

### Windows
```cmd
install.bat
```

Skrypt automatycznie:
- Utworzy bazę danych
- Zaimportuje schemat (podstawowy + rozszerzenia)
- Skonfiguruje połączenie z bazą
- Ustawi uprawnienia katalogów
- Zainstaluje zależności Composer (jeśli dostępny)

## Ręczna instalacja

### 1. Wymagania wstępne

Upewnij się, że masz zainstalowane:
- PHP 8.0+
- MariaDB 10.5+
- Apache lub Nginx
- Composer (opcjonalnie)

### 2. Instalacja krok po kroku

#### Krok 1: Baza danych

```bash
# Zaloguj się do MySQL/MariaDB
mysql -u root -p

# Utwórz bazę danych
CREATE DATABASE itss_projects CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Utwórz użytkownika
CREATE USER 'itss_user'@'localhost' IDENTIFIED BY 'TwojeHaslo123!';

# Nadaj uprawnienia
GRANT ALL PRIVILEGES ON itss_projects.* TO 'itss_user'@'localhost';
FLUSH PRIVILEGES;

# Wyjdź
EXIT;

# Zaimportuj schemat
mysql -u itss_user -p itss_projects < database/schema.sql
```

#### Krok 2: Konfiguracja aplikacji

```bash
# Skopiuj przykładową konfigurację
cp config/config.example.php config/config.php

# Edytuj konfigurację
nano config/config.php
```

Wypełnij następujące sekcje w `config/config.php`:

**Baza danych:**
```php
'database' => [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'itss_projects',
    'user' => 'itss_user',
    'password' => 'TwojeHaslo123!',
    'charset' => 'utf8mb4'
],
```

#### Krok 3: Azure AD (Microsoft 365)

1. Przejdź do https://portal.azure.com
2. Azure Active Directory → App registrations → New registration
3. Wypełnij:
   - Name: `ITSS Project Management`
   - Redirect URI: `http://localhost/auth/callback` (zmień na produkcyjny URL)
4. Po utworzeniu:
   - Skopiuj `Application (client) ID`
   - Skopiuj `Directory (tenant) ID`
5. Certificates & secrets → New client secret → Skopiuj wartość
6. API permissions → Add a permission → Microsoft Graph → Delegated:
   - `openid`
   - `profile`
   - `email`
   - `User.Read`
7. Uzupełnij w config.php:

```php
'azure' => [
    'tenant_id' => 'twoj-tenant-id',
    'client_id' => 'twoj-client-id',
    'client_secret' => 'twoj-client-secret',
    'redirect_uri' => 'http://localhost/auth/callback',
    'scopes' => ['openid', 'profile', 'email', 'User.Read']
],
```

#### Krok 4: Dynamics 365 CRM

1. Zarejestruj nową aplikację w Azure AD dla CRM
2. Nadaj uprawnienia do Dynamics 365
3. Uzupełnij w config.php:

```php
'dynamics_crm' => [
    'url' => 'https://itss.crm4.dynamics.com',
    'api_version' => '9.2',
    'client_id' => 'crm-client-id',
    'client_secret' => 'crm-client-secret',
    'resource' => 'https://itss.crm4.dynamics.com',
    'sync_interval' => 3600,
    'projects_entity' => 'opportunities'
],
```

#### Krok 5: ServiceDesk Plus

1. W ServiceDesk Plus wygeneruj API Key:
   - Admin → API → Generate Key
2. Pobierz Technician Key
3. Uzupełnij w config.php:

```php
'servicedesk' => [
    'url' => 'https://your-servicedesk.com',
    'api_key' => 'twoj-api-key',
    'sync_interval' => 1800,
    'technician_key' => 'twoj-technician-key'
],
```

#### Krok 6: Uprawnienia katalogów

```bash
# Ustaw właściciela
chown -R www-data:www-data /var/www/html/itss-projects

# Ustaw uprawnienia
chmod -R 755 /var/www/html/itss-projects
chmod -R 775 /var/www/html/itss-projects/uploads
chmod -R 775 /var/www/html/itss-projects/logs
```

#### Krok 7: Konfiguracja Apache

Utwórz plik `/etc/apache2/sites-available/itss-projects.conf`:

```apache
<VirtualHost *:80>
    ServerName itss-projects.local
    ServerAdmin admin@itss.pl
    DocumentRoot /var/www/html/itss-projects/public

    <Directory /var/www/html/itss-projects/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/itss-projects-error.log
    CustomLog ${APACHE_LOG_DIR}/itss-projects-access.log combined
</VirtualHost>
```

Aktywuj:
```bash
a2enmod rewrite
a2ensite itss-projects
systemctl restart apache2
```

#### Krok 8: Konfiguracja Nginx (alternatywa)

Utwórz plik `/etc/nginx/sites-available/itss-projects`:

```nginx
server {
    listen 80;
    server_name itss-projects.local;
    root /var/www/html/itss-projects/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }

    access_log /var/log/nginx/itss-projects-access.log;
    error_log /var/log/nginx/itss-projects-error.log;
}
```

Aktywuj:
```bash
ln -s /etc/nginx/sites-available/itss-projects /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

#### Krok 9: Cron dla automatycznej synchronizacji

```bash
crontab -e
```

Dodaj linię (synchronizacja co godzinę):
```
0 * * * * php /var/www/html/itss-projects/cron/sync.php >> /var/www/html/itss-projects/logs/cron.log 2>&1
```

#### Krok 10: Pierwsze logowanie

1. Otwórz przeglądarkę: `http://itss-projects.local`
2. Zostaniesz przekierowany do logowania Microsoft
3. Po zalogowaniu sprawdź w bazie:

```sql
SELECT * FROM users WHERE email = 'twoj.email@itss.pl';
```

4. Nadaj rolę administratora:

```sql
UPDATE users SET role = 'admin' WHERE email = 'twoj.email@itss.pl';
```

5. Odśwież stronę

## Testowanie instalacji

### Test 1: Autentykacja
- Zaloguj się przez Microsoft 365
- Sprawdź czy widzisz dashboard

### Test 2: Synchronizacja CRM
- Dashboard → Synchronizuj CRM
- Sprawdź logi: `tail -f logs/$(date +%Y-%m-%d).log`

### Test 3: Synchronizacja ServiceDesk
- Dashboard → Synchronizuj ServiceDesk
- Sprawdź logi

### Test 4: Tworzenie wniosku urlopowego
- Wnioski urlopowe → Złóż nowy wniosek
- Wypełnij formularz i wyślij

## Rozwiązywanie problemów

### Problem: Nie można połączyć się z bazą danych

**Rozwiązanie:**
```bash
# Sprawdź czy MySQL działa
systemctl status mysql

# Sprawdź uprawnienia
mysql -u itss_user -p
SHOW GRANTS;
```

### Problem: Błąd 500 po zalogowaniu

**Rozwiązanie:**
```bash
# Sprawdź uprawnienia katalogów
ls -la uploads/
ls -la logs/

# Sprawdź logi Apache/Nginx
tail -f /var/log/apache2/itss-projects-error.log
tail -f /var/log/nginx/itss-projects-error.log
```

### Problem: Azure AD redirect mismatch

**Rozwiązanie:**
- Sprawdź czy Redirect URI w Azure AD zgadza się z `config.php`
- Upewnij się że URL jest identyczny (http vs https)

### Problem: Cron nie działa

**Rozwiązanie:**
```bash
# Sprawdź logi cron
tail -f logs/cron.log

# Testuj manualnie
php cron/sync.php

# Sprawdź czy cron jest aktywny
systemctl status cron
```

## Bezpieczeństwo produkcyjne

Przed wdrożeniem na produkcję:

1. **Zmień debug na false:**
```php
'app' => [
    'debug' => false
],
```

2. **Użyj HTTPS:**
- Zainstaluj certyfikat SSL
- Przekieruj HTTP → HTTPS

3. **Zabezpiecz config.php:**
```bash
chmod 600 config/config.php
```

4. **Regularnie aktualizuj hasła**

5. **Monitoruj logi:**
```bash
tail -f logs/*.log
```

## Wsparcie

W razie problemów:
1. Sprawdź logi w katalogu `/logs`
2. Sprawdź logi serwera WWW
3. Sprawdź konfigurację PHP: `php -i | grep error`
4. Sprawdź konfigurację aplikacji w `config/config.php`

---

**Copyright © 2024 ITSS Sp. z o.o.**
