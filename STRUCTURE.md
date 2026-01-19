# Struktura projektu ITSS Project Management System

## Przegląd architektury

Aplikacja została zbudowana w architekturze MVC (Model-View-Controller) w czystym PHP bez użycia zewnętrznych frameworków.

```
PRO.ITSS.26/
├── config/                 # Konfiguracja aplikacji
│   ├── config.example.php  # Przykładowa konfiguracja
│   └── config.php          # Faktyczna konfiguracja (nie w repo)
│
├── database/               # Schemat bazy danych
│   └── schema.sql          # Definicje tabel
│
├── public/                 # Publiczny katalog (DocumentRoot)
│   └── index.php           # Punkt wejścia aplikacji
│
├── src/                    # Kod źródłowy
│   ├── Core/               # Podstawowe komponenty
│   │   ├── Database.php    # Klasa do obsługi bazy danych
│   │   ├── Logger.php      # System logowania
│   │   ├── Request.php     # Obsługa żądań HTTP
│   │   ├── Response.php    # Obsługa odpowiedzi HTTP
│   │   ├── Router.php      # Router URL
│   │   └── Session.php     # Zarządzanie sesją
│   │
│   ├── Models/             # Modele danych
│   │   ├── User.php
│   │   ├── Project.php
│   │   ├── Invoice.php
│   │   ├── Document.php
│   │   ├── WorkHour.php
│   │   ├── LeaveRequest.php
│   │   ├── BonusScheme.php
│   │   └── CalculatedBonus.php
│   │
│   ├── Modules/            # Moduły funkcjonalne
│   │   └── Auth/
│   │       ├── AuthController.php
│   │       ├── AzureADService.php
│   │       └── AuthMiddleware.php
│   │
│   ├── Services/           # Usługi biznesowe
│   │   ├── DynamicsCRMService.php
│   │   ├── ServiceDeskService.php
│   │   └── BonusCalculationService.php
│   │
│   └── routes.php          # Definicje tras
│
├── views/                  # Widoki HTML
│   ├── layout.php          # Główny layout
│   ├── dashboard.php       # Dashboard
│   ├── czasomat.php        # Integracja Czasomatu
│   ├── projects/
│   │   ├── list.php
│   │   └── detail.php
│   ├── invoices/
│   │   ├── list.php
│   │   └── create.php
│   ├── documents/
│   │   └── list.php
│   ├── leaves/
│   │   ├── list.php
│   │   ├── create.php
│   │   └── detail.php
│   └── bonuses/
│       ├── list.php
│       ├── schemes.php
│       └── calculate.php
│
├── uploads/                # Przesłane pliki
│   ├── documents/
│   └── invoices/
│
├── logs/                   # Logi aplikacji
│
├── cron/                   # Zadania cron
│   └── sync.php            # Automatyczna synchronizacja
│
├── .htaccess               # Konfiguracja Apache
├── .gitignore
├── composer.json
├── README.md
├── INSTALLATION.md
└── STRUCTURE.md
```

## Komponenty Core

### Database.php
Singleton do zarządzania połączeniem z bazą danych MariaDB.

**Główne metody:**
- `getInstance()` - Pobiera instancję
- `query()` - Wykonuje zapytanie SQL
- `insert()` - Wstawia rekord
- `update()` - Aktualizuje rekordy
- `delete()` - Usuwa rekordy
- `beginTransaction()`, `commit()`, `rollback()` - Transakcje

### Router.php
Prosty router URL obsługujący metody GET, POST, PUT, DELETE.

**Przykład:**
```php
$router->get('/projects', function($req, $res) { ... });
$router->post('/api/invoices', function($req, $res) { ... });
```

### Request.php & Response.php
Obiekty do obsługi żądań i odpowiedzi HTTP.

**Request:**
- `query()` - Parametry GET
- `post()` - Dane POST
- `json()` - Dane JSON
- `file()` - Przesłane pliki

**Response:**
- `json()` - Odpowiedź JSON
- `html()` - Odpowiedź HTML
- `redirect()` - Przekierowanie
- `download()` - Pobieranie pliku

### Session.php
Zarządzanie sesją PHP z dodatkowymi funkcjami.

**Funkcje:**
- `set()`, `get()`, `has()`, `remove()`
- `flash()` - Dane sesji na jedno żądanie
- `regenerate()` - Regeneracja ID sesji

### Logger.php
Prosty logger zapisujący do plików w formacie dziennym.

**Poziomy:**
- `debug()`, `info()`, `warning()`, `error()`

## Modele danych

Wszystkie modele dziedziczą z podstawowego wzorca Active Record.

### User
Użytkownicy systemu zsynchronizowani z Microsoft 365.

**Role:**
- `admin` - Administrator
- `director` - Dyrektor
- `manager` - Kierownik
- `team_leader` - Lider zespołu
- `employee` - Pracownik
- `helpdesk` - Helpdesk

### Project
Projekty synchronizowane z Dynamics CRM.

**Statusy:**
- `planning` - Planowanie
- `active` - Aktywny
- `completed` - Zakończony
- `on_hold` - Wstrzymany
- `cancelled` - Anulowany

### Invoice
Faktury kosztowe i przychodowe.

**Typy:**
- `cost` - Faktura kosztowa
- `revenue` - Faktura przychodowa

**Statusy płatności:**
- `pending` - Oczekująca
- `paid` - Zapłacona
- `overdue` - Zaległa
- `cancelled` - Anulowana

### LeaveRequest
Wnioski urlopowe z workflow zatwierdzania.

**Statusy:**
- `draft` - Szkic
- `pending_team_leader` - Oczekuje na lidera
- `pending_manager` - Oczekuje na kierownika
- `approved` - Zatwierdzony
- `rejected` - Odrzucony
- `cancelled` - Anulowany

### BonusScheme
Schematy premiowe dla użytkowników.

**Typy premii:**
- `margin_1` - Premia od marży 1
- `margin_2` - Premia od marży 2
- `hourly_rate` - Stała stawka godzinowa
- `tickets_fixed` - Stała kwota za zgłoszenie
- `tickets_percent` - Procent z puli za zgłoszenia

## Serwisy

### DynamicsCRMService
Integracja z Dynamics 365 CRM.

**Funkcje:**
- `syncProjects()` - Synchronizuje projekty
- `getProjectDetails()` - Pobiera szczegóły projektu

### ServiceDeskService
Integracja z ManageEngine ServiceDesk Plus.

**Funkcje:**
- `syncWorkHours()` - Synchronizuje godziny pracy
- `syncHelpdeskTickets()` - Synchronizuje zgłoszenia

### BonusCalculationService
Obliczanie premii według różnych schematów.

**Funkcje:**
- `calculateBonusForUser()` - Dla pojedynczego użytkownika
- `calculateBonusesForPeriod()` - Dla wszystkich w okresie

## Proces autentykacji

1. Użytkownik klika "Zaloguj"
2. Przekierowanie do Azure AD OAuth2
3. Callback z kodem autoryzacyjnym
4. Wymiana kodu na token dostępu
5. Pobranie informacji o użytkowniku z Microsoft Graph
6. Utworzenie/aktualizacja użytkownika w bazie
7. Utworzenie sesji
8. Przekierowanie do dashboardu

## Proces synchronizacji

### CRM (Dynamics 365)
1. Cron wywołuje `cron/sync.php`
2. Autoryzacja przez OAuth2
3. Pobranie projektów z API
4. Mapowanie na strukturę lokalną
5. Aktualizacja lub utworzenie projektów
6. Logowanie wyników

### ServiceDesk Plus
1. Cron wywołuje `cron/sync.php`
2. Autoryzacja przez API Key
3. Pobranie godzin pracy z ostatnich 30 dni
4. Pobranie zgłoszeń helpdesku
5. Mapowanie użytkowników i projektów
6. Aktualizacja bazy danych
7. Logowanie wyników

## Workflow urlopów

```
Pracownik składa wniosek
    ↓
[pending_team_leader]
    ↓
Lider zespołu zatwierdza/odrzuca
    ↓
[pending_manager]
    ↓
Kierownik/Dyrektor zatwierdza/odrzuca
    ↓
[approved] lub [rejected]
```

## Obliczanie premii

### Marża 1
```
Marża 1 = Σ(Faktury przychodowe zapłacone) - Σ(Faktury kosztowe zapłacone)
Premia = Marża 1 × Procent
```

### Marża 2
```
Koszty pracy = Σ(Godziny × Stawka godzinowa)
Marża 2 = Marża 1 - Koszty pracy
Premia = Marża 2 × Procent
```

### Premia godzinowa
```
Premia = Σ(Godziny przepracowane) × Stawka
```

### Premia helpdesk
```
Wariant 1: Premia = Liczba zgłoszeń × Stała stawka
Wariant 2: Premia = Pula × Procent
```

## API Endpoints

Wszystkie endpointy API zwracają JSON.

### Format odpowiedzi sukcesu
```json
{
    "success": true,
    "data": { ... }
}
```

### Format odpowiedzi błędu
```json
{
    "success": false,
    "error": "Opis błędu"
}
```

## Bezpieczeństwo

1. **Autentykacja** - Azure AD OAuth2
2. **Autoryzacja** - Role i middleware
3. **SQL Injection** - Prepared statements
4. **XSS** - htmlspecialchars() w widokach
5. **CSRF** - Token w sesji (do implementacji)
6. **Pliki** - Walidacja typu i rozmiaru
7. **Logi** - Osobne pliki dzienne

## Konwencje kodowania

### Nazewnictwo
- Klasy: `PascalCase`
- Metody: `camelCase`
- Zmienne: `camelCase`
- Stałe: `UPPER_CASE`
- Pliki: `PascalCase.php`

### Baza danych
- Tabele: `snake_case` (liczba mnoga)
- Kolumny: `snake_case`
- Klucze obce: `{tabela}_id`

### Komentarze
```php
/**
 * Opis metody
 *
 * @param typ $nazwa Opis parametru
 * @return typ Opis wyniku
 */
```

## Rozszerzanie systemu

### Dodawanie nowego modułu

1. Utwórz model w `src/Models/`
2. Dodaj tabelę do `database/schema.sql`
3. Utwórz widoki w `views/`
4. Dodaj trasy w `src/routes.php`
5. (Opcjonalnie) Utwórz serwis w `src/Services/`

### Dodawanie nowej integracji

1. Utwórz serwis w `src/Services/`
2. Dodaj konfigurację w `config/config.php`
3. Dodaj wywołanie w `cron/sync.php`
4. Dodaj endpoint API w `src/routes.php`

---

**Wersja:** 1.0
**Data:** 2024
**Copyright:** ITSS Sp. z o.o.
