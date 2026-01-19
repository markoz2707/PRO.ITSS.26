# Szybki start ITSS Project Management System

## ⚡ 5-minutowa instalacja

### 1. Uruchom skrypt instalacyjny

#### Linux/macOS:
```bash
chmod +x install.sh
./install.sh
```

#### Windows:
```cmd
install.bat
```

Skrypt automatycznie:
- ✅ Utworzy bazę danych
- ✅ Zaimportuje schemat (podstawowy + rozszerzenia)
- ✅ Skonfiguruje połączenie z bazą
- ✅ Ustawi uprawnienia katalogów
- ✅ Zainstaluje zależności Composer (jeśli dostępny)

---

## 2. Konfiguracja Azure AD (Microsoft 365)

### Szybka konfiguracja:

1. Otwórz https://portal.azure.com
2. **Azure Active Directory** → **App registrations** → **New registration**
3. Wypełnij:
   - **Name:** ITSS Project Management
   - **Redirect URI:** `http://localhost/auth/callback`
4. Skopiuj dane:
   - **Application (client) ID**
   - **Directory (tenant) ID**
5. **Certificates & secrets** → **New client secret** → Skopiuj wartość
6. **API permissions** → **Add permission** → **Microsoft Graph** → Delegated:
   - ✅ openid
   - ✅ profile
   - ✅ email
   - ✅ User.Read

### Edytuj `config/config.php`:
```php
'azure' => [
    'tenant_id' => 'TWOJ-TENANT-ID',
    'client_id' => 'TWOJ-CLIENT-ID',
    'client_secret' => 'TWOJ-CLIENT-SECRET',
    'redirect_uri' => 'http://localhost/auth/callback',
],
```

---

## 3. Uruchom serwer WWW

### Opcja A - PHP Built-in Server (szybki test):
```bash
cd public
php -S localhost:8000
```

Otwórz: http://localhost:8000

### Opcja B - Apache/Nginx:
Skonfiguruj VirtualHost (patrz [INSTALLATION.md](INSTALLATION.md))

---

## 4. Pierwsze logowanie

1. Otwórz aplikację w przeglądarce
2. Kliknij **Zaloguj przez Microsoft 365**
3. Zaloguj się swoim kontem Microsoft
4. Zostaniesz dodany do systemu jako użytkownik

### Nadaj sobie uprawnienia administratora:
```sql
mysql -u itss_user -p itss_projects
```
```sql
UPDATE users SET role = 'admin' WHERE email = 'twoj.email@itss.pl';
```

---

## 5. Import faktur z CSV (opcjonalnie)

1. W aplikacji przejdź do **Faktury** → **Import faktur**
2. Wybierz plik CSV
3. Wybierz typ: **Przychód** lub **Koszt**
4. Kliknij **Importuj**

### Format przykładowego pliku CSV:
```csv
Nazwa;KONTRAHENT;DATA WYSTAWIENIA;NETTO;BRUTTO;KATEGORIA
FS/001/2024;Firma ABC;31.03.2024;1000.00;1230.00;Usługa IT
```

Szczegóły: [IMPORT_GUIDE.md](IMPORT_GUIDE.md)

---

## 6. Konfiguracja integracji (opcjonalnie)

### Dynamics 365 CRM:
```php
'dynamics_crm' => [
    'url' => 'https://twoja-organizacja.crm4.dynamics.com',
    'client_id' => 'CRM-CLIENT-ID',
    'client_secret' => 'CRM-CLIENT-SECRET',
],
```

### ServiceDesk Plus:
```php
'servicedesk' => [
    'url' => 'https://your-servicedesk.com',
    'api_key' => 'TWOJ-API-KEY',
],
```

---

## 🎯 Gotowe!

Teraz możesz:
- ✅ Zarządzać projektami
- ✅ Dodawać faktury (ręcznie lub import CSV)
- ✅ Składać wnioski urlopowe
- ✅ Obliczać premie projektowe
- ✅ Śledzić godziny pracy

---

## 📚 Pełna dokumentacja

- [README.md](README.md) - Ogólny przegląd
- [INSTALLATION.md](INSTALLATION.md) - Szczegółowa instalacja
- [IMPORT_GUIDE.md](IMPORT_GUIDE.md) - Przewodnik importu faktur
- [STRUCTURE.md](STRUCTURE.md) - Struktura projektu
- [CHANGELOG_EXTENDED.md](CHANGELOG_EXTENDED.md) - Historia zmian

---

## 🆘 Pomoc

### Problem: Nie mogę się zalogować
**Rozwiązanie:** Sprawdź konfigurację Azure AD w `config/config.php`

### Problem: Błąd połączenia z bazą danych
**Rozwiązanie:** Sprawdź dane w sekcji `database` w `config/config.php`

### Problem: Import CSV nie działa
**Rozwiązanie:** Sprawdź format pliku i logi w `logs/`

### Więcej:
Sprawdź pełną dokumentację w [INSTALLATION.md](INSTALLATION.md) - sekcja "Rozwiązywanie problemów"

---

**Wersja:** 1.1.0
**Copyright:** ITSS Sp. z o.o.
