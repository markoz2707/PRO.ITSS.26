# Wytyczne dla deweloperów

## 🛠️ Środowisko developerskie

### Wymagania
- PHP 8.0+
- MariaDB 10.5+
- Composer (opcjonalnie)
- Git

### Instalacja środowiska deweloperskiego

```bash
# Klonuj repozytorium
git clone <repository-url>
cd PRO.ITSS.26

# Uruchom instalację
chmod +x install.sh
./install.sh

# Włącz tryb debug w config/config.php
'app' => [
    'debug' => true
]
```

---

## 📁 Struktura projektu

```
PRO.ITSS.26/
├── config/              # Konfiguracja aplikacji
├── database/            # Schematy bazy danych i migracje
├── src/
│   ├── Core/           # Klasy bazowe (Database, Router, etc.)
│   ├── Models/         # Modele danych
│   ├── Controllers/    # Kontrolery API
│   └── Services/       # Serwisy biznesowe
├── views/              # Widoki HTML/PHP
├── public/             # Publiczny katalog (index.php, assets)
├── uploads/            # Przesłane pliki
├── logs/               # Logi aplikacji
└── cron/               # Zadania cron
```

---

## 🔧 Standardy kodu

### PHP

#### Namespace i autoloading
```php
namespace ITSS\Models;

use ITSS\Core\Database;
use ITSS\Core\Logger;
```

#### Formatowanie
- **PSR-12** coding standard
- 4 spacje (nie tabulatory)
- Nawiasy klamrowe na nowej linii dla klas i funkcji

#### Nazewnictwo
- Klasy: `PascalCase` (np. `InvoiceItem`)
- Metody: `camelCase` (np. `getByProject`)
- Zmienne: `snake_case` (np. `$user_id`)
- Stałe: `UPPER_CASE` (np. `DEFAULT_CURRENCY`)

### SQL

```sql
-- Nazwy tabel: snake_case, liczba mnoga
CREATE TABLE invoice_items (...)

-- Nazwy kolumn: snake_case
ALTER TABLE invoices ADD COLUMN business_type VARCHAR(100)

-- Indeksy: idx_<tabela>_<kolumny>
CREATE INDEX idx_invoices_business_type ON invoices(business_type)

-- Klucze obce: fk_<tabela>_<kolumna>
CONSTRAINT fk_invoices_project_id FOREIGN KEY (project_id)
```

---

## 🗄️ Praca z bazą danych

### Tworzenie nowych tabel

1. Dodaj definicję do `database/schema_extended.sql` (lub utwórz nowy plik migracji)
2. Dodaj model w `src/Models/`
3. Zaktualizuj dokumentację w `STRUCTURE.md`

### Przykład modelu

```php
namespace ITSS\Models;

use ITSS\Core\Database;

class ExampleModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        return $this->db->fetchAll('SELECT * FROM examples ORDER BY created_at DESC');
    }

    public function create(array $data, int $userId): int
    {
        return $this->db->insert('examples', [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
```

---

## 🔌 Dodawanie nowych API endpoints

### 1. Dodaj routing w `src/routes.php`

```php
// GET endpoint
$router->get('/api/examples', 'ExampleController@index');

// POST endpoint
$router->post('/api/examples', 'ExampleController@create');

// Parametryczny endpoint
$router->get('/api/examples/{id}', 'ExampleController@show');
```

### 2. Utwórz kontroler w `src/Controllers/`

```php
namespace ITSS\Controllers;

use ITSS\Core\Request;
use ITSS\Core\Response;
use ITSS\Models\ExampleModel;

class ExampleController
{
    private ExampleModel $model;

    public function __construct()
    {
        $this->model = new ExampleModel();
    }

    public function index(Request $request): Response
    {
        $examples = $this->model->getAll();
        return Response::json($examples);
    }

    public function create(Request $request): Response
    {
        $data = $request->json();
        $userId = $_SESSION['user_id'];

        $id = $this->model->create($data, $userId);

        return Response::json(['id' => $id, 'success' => true], 201);
    }
}
```

---

## 🎨 Dodawanie widoków

### 1. Utwórz widok w `views/`

```php
<?php
$pageTitle = 'Przykładowy widok - ITSS';
ob_start();
?>

<h2>Przykładowy widok</h2>

<div class="card">
    <div class="card-header">Zawartość</div>
    <p>Lorem ipsum...</p>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
```

### 2. Dodaj routing

```php
$router->get('/examples', function() {
    require __DIR__ . '/../views/examples/list.php';
});
```

---

## 📝 Logowanie

```php
use ITSS\Core\Logger;

// Różne poziomy logowania
Logger::debug('Debug message', ['context' => 'value']);
Logger::info('Info message');
Logger::warning('Warning message');
Logger::error('Error message', ['error' => $e->getMessage()]);
```

Logi zapisywane są w `logs/YYYY-MM-DD.log`

---

## 🧪 Testowanie

### Testowanie manualne

1. Użyj trybu debug w `config.php`
2. Sprawdź logi w `logs/`
3. Użyj narzędzi developerskich przeglądarki

### Testowanie API

```bash
# Przykład z cURL
curl -X POST http://localhost/api/examples \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "description": "Test description"}'
```

---

## 🔐 Bezpieczeństwo

### Walidacja danych wejściowych

```php
// Zawsze waliduj dane od użytkownika
$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
if (!$email) {
    return Response::json(['error' => 'Invalid email'], 400);
}

// Używaj prepared statements (Database już to robi)
$this->db->fetchOne(
    'SELECT * FROM users WHERE email = :email',
    ['email' => $email]
);
```

### SQL Injection

❌ **NIE RÓB TEGO:**
```php
$sql = "SELECT * FROM users WHERE id = " . $_GET['id'];
```

✅ **RÓB TO:**
```php
$user = $this->db->fetchOne(
    'SELECT * FROM users WHERE id = :id',
    ['id' => $request->get('id')]
);
```

### XSS Protection

W widokach używaj `htmlspecialchars()`:
```php
<p><?= htmlspecialchars($user['name']) ?></p>
```

---

## 📦 Import/Export danych

### CSV Import

Wykorzystaj `InvoiceImportService` jako wzór:

```php
namespace ITSS\Services;

class MyImportService
{
    public function importFromCSV(string $filePath, string $delimiter = ';'): array
    {
        $imported = 0;
        $errors = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle, 0, $delimiter);

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                try {
                    $data = array_combine($header, $row);
                    // Przetwarzanie...
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }

            fclose($handle);
        }

        return ['success' => true, 'imported' => $imported, 'errors' => $errors];
    }
}
```

---

## 🔄 Synchronizacja z zewnętrznymi systemami

### Tworzenie nowego serwisu synchronizacji

```php
namespace ITSS\Services;

use ITSS\Core\Logger;

class ExternalSystemSyncService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->apiUrl = $config['external_system']['url'];
        $this->apiKey = $config['external_system']['api_key'];
    }

    public function syncData(): array
    {
        Logger::info('Starting sync with external system');

        try {
            // Implementacja synchronizacji...
            $result = ['success' => true, 'synced' => 0];

            Logger::info('Sync completed', $result);
            return $result;

        } catch (\Exception $e) {
            Logger::error('Sync failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
```

---

## 📋 Git workflow

### Branch naming
- `feature/nazwa-funkcjonalnosci` - nowa funkcjonalność
- `bugfix/opis-bledu` - poprawka błędu
- `hotfix/opis-bledu` - pilna poprawka na produkcji

### Commit messages
```
feat: Add invoice CSV import functionality
fix: Correct margin calculation in bonus module
docs: Update installation guide
refactor: Simplify database connection handling
```

### Pull Request checklist
- [ ] Kod zgodny ze standardami
- [ ] Dodano dokumentację (jeśli potrzebna)
- [ ] Przetestowano ręcznie
- [ ] Zaktualizowano CHANGELOG (jeśli duża zmiana)

---

## 📚 Dodatkowe zasoby

- **PSR-12:** https://www.php-fig.org/psr/psr-12/
- **PHP Best Practices:** https://phptherightway.com/
- **MariaDB Documentation:** https://mariadb.com/kb/en/documentation/

---

## 🆘 Pomoc

W razie problemów:
1. Sprawdź logi w `logs/`
2. Włącz `debug => true` w konfiguracji
3. Sprawdź dokumentację w `README.md` i `STRUCTURE.md`
4. Skontaktuj się z zespołem deweloperskim

---

**Wersja:** 1.1.0
**Ostatnia aktualizacja:** 2026-01-11
**Copyright:** ITSS Sp. z o.o.
