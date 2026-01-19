# ITSS Project Management System

System zarządzania projektami, kosztami, przychodami i premiami dla ITSS.

## 🚀 Szybki start

**Nowy użytkownik?** Przejdź do [QUICK_START.md](QUICK_START.md) - instalacja w 5 minut!

```bash
# Linux/macOS
chmod +x install.sh && ./install.sh

# Windows
install.bat
```

---

## Funkcjonalności

### 1. Zarządzanie projektami
- Synchronizacja projektów z Dynamics 365 CRM
- Przypisanie handlowców i architektów
- Śledzenie statusu projektów
- Automatyczne zliczanie godzin pracy z ServiceDesk Plus

### 2. Zarządzanie fakturami
- Faktury kosztowe i przychodowe z rozszerzonymi atrybutami
- Pozycje faktur (szczegółowe rozpisanie)
- Mapowanie faktur do projektów (wiele do wielu)
- Śledzenie statusu płatności
- Import masowy z plików CSV/Excel
- Business Type, Segment, Sector
- Kody MPK (DH1, DH2, GNP, DO, OG, EU1, EU2, ONO, KSDO)
- Kategorie i opiekunowie handlowi
- Przygotowanie pod import z KSeF

### 3. Zarządzanie dokumentami
- Przechowywanie umów i załączników
- Załączniki do faktur
- Protokoły odbioru
- Inne dokumenty projektowe

### 4. Godziny pracy
- Automatyczna synchronizacja z ManageEngine ServiceDesk Plus
- Podział na realizację, presales i wsparcie
- Raportowanie na poziomie projektu i użytkownika

### 5. System urlopowy
- Składanie wniosków urlopowych
- Proces akceptacji dwupoziomowy:
  - Zatwierdzenie przez lidera zespołu
  - Zatwierdzenie przez kierownika/dyrektora
- Historia zmian statusów
- Podsumowania urlopów

### 6. System premiowy
- Premie od marży (marża 1 i marża 2):
  - Marża 1 = Przychody - Koszty bezpośrednie
  - Marża 2 = Marża 1 - Koszty pracy
- Premie od godzin (stała stawka za godzinę dla inżynierów)
- Premie dla helpdesku:
  - Procentowa z określonej puli
  - Od ilości rozwiązanych zgłoszeń
- Automatyczne obliczanie premii
- Proces zatwierdzania premii

### 7. Integracje
- **Microsoft 365 / Azure AD** - autentykacja użytkowników
- **Dynamics 365 CRM** - synchronizacja projektów
- **ManageEngine ServiceDesk Plus** - godziny pracy i zgłoszenia
- **Czasomat ITSS** - iframe w menu aplikacji

## Wymagania systemowe

- PHP 8.0 lub nowszy
- MariaDB 10.5 lub nowszy
- Serwer WWW (Apache, Nginx)
- Rozszerzenia PHP:
  - PDO
  - PDO_MySQL
  - curl
  - json
  - mbstring

## Instalacja

### 1. Klonowanie repozytorium

```bash
git clone <repository-url>
cd PRO.ITSS.26
```

### 2. Konfiguracja bazy danych

Utwórz bazę danych MariaDB:

```sql
CREATE DATABASE itss_projects CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'itss_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON itss_projects.* TO 'itss_user'@'localhost';
FLUSH PRIVILEGES;
```

Zaimportuj schemat bazy danych:

```bash
# Podstawowy schemat
mysql -u itss_user -p itss_projects < database/schema.sql

# Rozszerzenia dla faktur i kosztów
mysql -u itss_user -p itss_projects < database/schema_extended.sql
```

### 3. Konfiguracja aplikacji

Skopiuj plik konfiguracyjny:

```bash
cp config/config.example.php config/config.php
```

Edytuj `config/config.php` i wypełnij wszystkie wymagane dane:

- Dane dostępowe do bazy danych
- Dane Azure AD (tenant_id, client_id, client_secret)
- Dane Dynamics CRM
- Dane ServiceDesk Plus
- Pozostałe ustawienia

### 4. Konfiguracja Azure AD

1. Zaloguj się do [Azure Portal](https://portal.azure.com)
2. Przejdź do **Azure Active Directory** > **App registrations**
3. Kliknij **New registration**
4. Wypełnij formularz:
   - Name: ITSS Project Management
   - Supported account types: Accounts in this organizational directory only
   - Redirect URI: `http://your-domain.com/auth/callback`
5. Po utworzeniu aplikacji:
   - Skopiuj **Application (client) ID** do `config.php` jako `client_id`
   - Skopiuj **Directory (tenant) ID** do `config.php` jako `tenant_id`
6. Utwórz Client Secret:
   - Przejdź do **Certificates & secrets**
   - Kliknij **New client secret**
   - Skopiuj wartość do `config.php` jako `client_secret`
7. Ustaw uprawnienia API:
   - Przejdź do **API permissions**
   - Dodaj **Microsoft Graph** > **Delegated permissions**:
     - openid
     - profile
     - email
     - User.Read

### 5. Konfiguracja Dynamics CRM

1. Zarejestruj aplikację w Azure AD dla CRM
2. Przypisz odpowiednie uprawnienia do Dynamics 365
3. Uzupełnij dane w `config.php` w sekcji `dynamics_crm`

### 6. Konfiguracja ServiceDesk Plus

1. Wygeneruj API Key w ServiceDesk Plus
2. Uzyskaj Technician Key
3. Uzupełnij dane w `config.php` w sekcji `servicedesk`

### 7. Konfiguracja serwera WWW

#### Apache

Upewnij się, że mod_rewrite jest włączony:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Przykładowa konfiguracja VirtualHost:

```apache
<VirtualHost *:80>
    ServerName itss-projects.local
    DocumentRoot /path/to/PRO.ITSS.26/public

    <Directory /path/to/PRO.ITSS.26/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/itss-error.log
    CustomLog ${APACHE_LOG_DIR}/itss-access.log combined
</VirtualHost>
```

#### Nginx

Przykładowa konfiguracja:

```nginx
server {
    listen 80;
    server_name itss-projects.local;
    root /path/to/PRO.ITSS.26/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

### 8. Uprawnienia katalogów

Ustaw odpowiednie uprawnienia:

```bash
chmod -R 755 /path/to/PRO.ITSS.26
chmod -R 775 /path/to/PRO.ITSS.26/uploads
chmod -R 775 /path/to/PRO.ITSS.26/logs
chown -R www-data:www-data /path/to/PRO.ITSS.26/uploads
chown -R www-data:www-data /path/to/PRO.ITSS.26/logs
```

### 9. Konfiguracja Cron

Dodaj zadanie cron dla automatycznej synchronizacji:

```bash
crontab -e
```

Dodaj linię (synchronizacja co godzinę):

```
0 * * * * php /path/to/PRO.ITSS.26/cron/sync.php >> /path/to/PRO.ITSS.26/logs/cron.log 2>&1
```

## Pierwsze uruchomienie

1. Otwórz przeglądarkę i przejdź do adresu aplikacji
2. Zostaniesz przekierowany do logowania Microsoft 365
3. Po zalogowaniu zostaniesz dodany do bazy jako użytkownik
4. Administrator systemu musi przypisać odpowiednią rolę użytkownika w bazie danych:

```sql
UPDATE users SET role = 'admin' WHERE email = 'twoj.email@itss.pl';
```

## Struktura ról

- **admin** - pełny dostęp do systemu
- **director** - dyrektor, zatwierdza urlopy kierowników
- **manager** - kierownik, zatwierdza urlopy zatwierdzone przez liderów
- **team_leader** - lider zespołu, pierwsza akceptacja urlopów
- **employee** - pracownik standardowy
- **helpdesk** - pracownik helpdesku z premiami od zgłoszeń

## Hierarchia zatwierdzania urlopów

1. Pracownik składa wniosek → status: `pending_team_leader`
2. Lider zespołu zatwierdza → status: `pending_manager`
3. Kierownik/Dyrektor zatwierdza → status: `approved`

Specjalne przypadki:
- Wnioski zastępcy zatwierdza dyrektor
- Wnioski dyrektora zatwierdza prezes lub osoba go zastępująca

## Obliczanie premii

### Marża 1 i Marża 2

```
Marża 1 = Przychody (zapłacone) - Koszty bezpośrednie (zapłacone)
Marża 2 = Marża 1 - Koszty pracy (godziny * stawka)
```

Premia = Marża × Procent

### Premia godzinowa (inżynierowie)

```
Premia = Suma godzin przepracowanych × Stawka godzinowa
```

### Premia helpdesk

**Wariant 1 - Procentowa z puli:**
```
Premia = Określona pula × Procent
```

**Wariant 2 - Od liczby zgłoszeń:**
```
Premia = Liczba rozwiązanych zgłoszeń × Stała stawka
```

## API Endpoints

### Autentykacja
- `GET /auth/login` - Przekierowanie do logowania M365
- `GET /auth/callback` - Callback OAuth2
- `GET /auth/logout` - Wylogowanie
- `GET /auth/check` - Sprawdzenie statusu zalogowania

### Projekty
- `GET /api/projects` - Lista projektów
- `GET /api/projects/{id}` - Szczegóły projektu
- `GET /api/projects/{id}/financials` - Dane finansowe projektu
- `POST /api/projects` - Utworzenie projektu

### Faktury
- `GET /api/invoices` - Lista faktur
- `POST /api/invoices` - Utworzenie faktury
- `POST /api/invoices/{id}/mark-paid` - Oznaczenie jako zapłacona

### Urlopy
- `GET /api/leaves` - Lista wniosków
- `POST /api/leaves` - Złożenie wniosku
- `POST /api/leaves/{id}/approve-team-leader` - Zatwierdzenie przez lidera
- `POST /api/leaves/{id}/approve-manager` - Zatwierdzenie przez kierownika
- `POST /api/leaves/{id}/reject` - Odrzucenie wniosku

### Premie
- `GET /api/bonuses/schemes` - Lista schematów premiowych
- `POST /api/bonuses/schemes` - Utworzenie schematu
- `POST /api/bonuses/calculate` - Obliczenie premii
- `GET /api/bonuses/calculated` - Lista obliczonych premii

### Synchronizacja
- `POST /api/sync/crm` - Ręczna synchronizacja CRM
- `POST /api/sync/servicedesk` - Ręczna synchronizacja ServiceDesk

## Bezpieczeństwo

- Wszystkie hasła przechowywane w `config.php` powinny być chronione
- W produkcji ustaw `debug => false` w konfiguracji
- Użyj HTTPS w środowisku produkcyjnym
- Regularnie aktualizuj logi i monitoruj dostęp
- Ogranicz dostęp do plików konfiguracyjnych

## Wsparcie

W przypadku problemów:
1. Sprawdź logi w katalogu `/logs`
2. Sprawdź konfigurację w `config/config.php`
3. Sprawdź uprawnienia katalogów
4. Sprawdź logi serwera WWW

## Licencja

Proprietary - ITSS Sp. z o.o.
