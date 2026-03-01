# Changelog - Rozszerzona struktura faktur i pozycji

## Wersja 1.1.0 - Rozszerzenie struktury faktur

### Nowe funkcjonalności

#### 1. Rozszerzona struktura faktur
Dodano następujące pola do tabeli `invoices`:
- `contractor` - Nazwa kontrahenta (uniwersalne pole)
- `business_type` - Typ biznesowy (np. "2.2- Bundled contracts")
- `segment` - Segment klienta (np. "2- Large-owned company")
- `sector` - Sektor działalności
- `category` - Kategoria faktury (np. "0-20.00 USŁUGA ZGODNIE Z UMOWĄ")
- `mpk_dh1`, `mpk_dh2`, `mpk_gnp`, `mpk_do`, `mpk_og`, `mpk_eu1`, `mpk_eu2`, `mpk_ono`, `mpk_ksdo` - Kody MPK (miejsca powstawania kosztów)
- `operator_client` - Operator/opiekun handlowy
- `payment_deadline_date` - Termin płatności
- `payment_received_date` - Data otrzymania płatności
- `uwagi` - Uwagi dodatkowe
- `baza_licze` - Baza licencji
- `mpt` - Dodatkowy kod

#### 2. Tabela pozycji faktur (`invoice_items`)
Nowa tabela umożliwiająca szczegółowe rozpisanie faktury na pozycje:
- Podstawowe informacje o pozycji (nazwa, opis, ilość, jednostka)
- Kwoty: netto, VAT, brutto na poziomie pozycji
- Cena jednostkowa
- Stawka VAT
- Kategoria i typ biznesowy dla pozycji
- Kody MPK dla pozycji
- Numery pozycji

#### 3. Tabela kosztów projektu (`project_costs`)
Szczegółowe śledzenie kosztów na poziomie projektu:
- Typy kosztów: invoice, labor, equipment, other
- Powiązanie z fakturami i ich pozycjami
- Kategorie kosztów
- Kontrahenci
- Kody MPK
- Pole `przelewy_wyksztalci` dla dodatkowych informacji

#### 4. Tabela przychodów projektu (`project_revenues`)
Szczegółowe śledzenie przychodów na poziomie projektu:
- Typy przychodów: invoice, service, other
- Powiązanie z fakturami i ich pozycjami
- Business type, segment, sector na poziomie przychodu
- Klienci
- Kody MPK
- Pole `przelewy_wyksztalci`

#### 5. Mapowanie faktur do projektów
Dwie nowe tabele umożliwiające przypisanie faktury do wielu projektów:
- `invoice_cost_mapping` - mapowanie faktur kosztowych
- `invoice_revenue_mapping` - mapowanie faktur przychodowych
- Procent alokacji do projektu
- Alokowana kwota netto

#### 6. Słowniki (`dictionaries`)
Centralne zarządzanie wartościami słownikowymi:
- Business types
- Segmenty
- Sektory
- Kategorie
- Kategorie kosztów
- Kody MPK

#### 7. Import faktur z CSV
Kompletny system importu z plików CSV:
- Obsługa różnych separatorów (;, ,, |)
- Automatyczne parsowanie dat z różnych formatów
- Automatyczne parsowanie kwot (z przecinkiem, kropką, spacjami)
- Obsługa wartości specjalnych (`########`, `-`, puste)
- Automatyczne powiązanie z projektami
- Automatyczne tworzenie pozycji faktury
- Automatyczne tworzenie wpisów w project_costs/revenues
- Określanie statusu płatności na podstawie dat
- Szczegółowe logowanie
- Raport z importu (ile zaimportowano, pominięto, błędy)

### Nowe modele

#### `InvoiceItem`
```php
$item = new InvoiceItem();
$items = $item->getByInvoice($invoiceId);
$total = $item->getInvoiceTotal($invoiceId);
$summary = $item->getSummaryByCategory($invoiceId);
```

#### `ProjectCost`
```php
$cost = new ProjectCost();
$costs = $cost->getByProject($projectId);
$totals = $cost->getTotalByProject($projectId);
$summary = $cost->getSummaryByCategory($projectId);
```

#### `ProjectRevenue`
```php
$revenue = new ProjectRevenue();
$revenues = $revenue->getByProject($projectId);
$totals = $revenue->getTotalByProject($projectId);
$summary = $revenue->getSummaryByBusinessType($projectId);
```

### Nowe API Endpoints

#### Pozycje faktur
```
GET  /api/invoices/{id}/items       - Lista pozycji faktury
POST /api/invoices/{id}/items       - Dodaj pozycję do faktury
```

#### Koszty i przychody projektu
```
GET /api/projects/{id}/costs        - Lista kosztów projektu
GET /api/projects/{id}/revenues     - Lista przychodów projektu
```

#### Import faktur
```
POST /api/invoices/import            - Importuj faktury z CSV
```

Parametry:
- `csv_file` (file, required) - Plik CSV
- `invoice_type` (string, required) - 'cost' lub 'revenue'
- `delimiter` (string, optional) - Separator, domyślnie ';'

Odpowiedź:
```json
{
  "success": true,
  "imported": 125,
  "skipped": 5,
  "errors": ["Row 10: ...", "Row 23: ..."]
}
```

### Nowe widoki

#### `/invoices/import`
Formularz importu faktur z CSV z:
- Wyborem typu faktury
- Wyborem separatora
- Instrukcją formatu pliku
- Postępem importu
- Raportem wyników

### Nowe serwisy

#### `InvoiceImportService`
Kompleksowy serwis do importu faktur:

```php
$service = new InvoiceImportService();
$result = $service->importFromCSV($filePath, $userId, 'revenue', ';');

// $result zawiera:
// - success: bool
// - imported: int
// - skipped: int
// - errors: array
```

Funkcje:
- `importFromCSV()` - Import z pliku CSV
- `importSingleInvoice()` - Import pojedynczej faktury
- `getValue()` - Pobieranie wartości z różnych kolumn
- `parseAmount()` - Parsowanie kwot
- `parseDate()` - Parsowanie dat z różnych formatów

### Zmiany w istniejących modelach

#### Model `Invoice`
Rozszerzono metodę `create()` o nowe pola:
- contractor, business_type, segment, sector, category
- mpk_dh1, mpk_dh2, mpk_gnp, mpk_do, mpk_og, mpk_eu1, mpk_eu2, mpk_ono, mpk_ksdo
- operator_client, payment_deadline_date, payment_received_date
- uwagi, baza_licze, mpt

Rozszerzono metodę `update()` o te same pola.

#### Model `Project`
Dodano pola:
- `opiekun_handlowy` - Opiekun handlowy projektu
- `uwagi` - Uwagi do projektu
- `baza_licze` - Baza licencji
- `termin_platnosci` - Termin płatności
- `termin_zaplaty` - Termin zapłaty

### Migracja bazy danych

#### Krok 1: Podstawowy schemat
```bash
mysql -u user -p database < database/schema.sql
```

#### Krok 2: Rozszerzenia
```bash
mysql -u user -p database < database/schema_extended.sql
```

Lub ręcznie:
```sql
-- Rozszerz tabelę invoices
ALTER TABLE invoices ADD COLUMN contractor VARCHAR(255) AFTER client_name;
ALTER TABLE invoices ADD COLUMN business_type VARCHAR(100) AFTER description;
-- ... (pozostałe pola)

-- Utwórz nowe tabele
CREATE TABLE invoice_items (...);
CREATE TABLE project_costs (...);
CREATE TABLE project_revenues (...);
CREATE TABLE invoice_cost_mapping (...);
CREATE TABLE invoice_revenue_mapping (...);
CREATE TABLE dictionaries (...);
```

### Format importowanych danych

#### Przykładowy plik CSV (Koszty)
```csv
Nazwa;KONTRAHENT;DATA WYSTAWIENIA;DATA PŁATNOŚCI;NETTO;BRUTTO;KATEGORIA;OPIS DOKUMENTU;MPK-DH1;UWAGI
FS/009/03/2025;Axentro;31.03.2025;31.03.2025;########;########;0-20.00 USŁUGA ZGODNIE Z UMOWĄ;Wynajem samochodów osobowych z umowa;########;Przelewy w wyksz. 24,438.15 zł
```

#### Przykładowy plik CSV (Przychody)
```csv
Nazwa;KONTRAHENT;DATA WYSTAWIENIA;DATA PŁATNOŚCI;DATA ZAPŁATY;NETTO;BRUTTO;KATEGORIA;OPIS DOKUMENTU;Business Type;Segment;MPK-DH1;OPIEKUN HANDLOWY
FS/064/1/2024;Abbsa S.A.;31.10.2024;########;########;1000.00;########;0-20.00 USŁUGA ZGODNIE Z UMOWĄ;Usługa zgodnie z umową;2.2- Bundled contracts;2- Large-owned company;########;Anna Węcko
```

### Obsługiwane formaty

#### Daty
- `dd.mm.yyyy` → 31.03.2024
- `yyyy-mm-dd` → 2024-03-31
- `dd/mm/yyyy` → 31/03/2024
- `dd-mm-yyyy` → 31-03-2024

#### Kwoty
- Z przecinkiem: `1000,50` → 1000.50
- Z kropką: `1000.50` → 1000.50
- Ze spacjami: `1 000,50` → 1000.50
- Specjalne: `########` → 0.00

### Bezpieczeństwo

#### Walidacja
- Sprawdzanie duplikatów faktur po numerze
- Walidacja formatu pliku (nagłówek)
- Walidacja dat i kwot
- Transakcje bazy danych (rollback w razie błędu)

#### Logowanie
Wszystkie operacje importu są logowane:
```
[2024-01-10 14:23:45] [INFO] Starting CSV import {"file":"faktu ry.csv","user_id":1,"invoice_type":"revenue"}
[2024-01-10 14:23:46] [INFO] Invoice imported successfully {"invoice_id":123,"invoice_number":"FS/001/2024"}
[2024-01-10 14:23:50] [INFO] CSV import completed {"imported":125,"skipped":5,"errors_count":5}
```

### Wydajność

#### Optymalizacje
- Transakcje bazy danych dla każdej faktury
- Indeksy na kluczowych polach (business_type, segment, mpk_dh1, contractor)
- Batch processing (możliwy w przyszłości)

#### Limity
- Domyślny limit pliku: 50 MB (konfigurowalny w `config.php`)
- Zalecane: max 500-1000 faktur na import
- Dla większych zbiorów: podzielić plik

### Dokumentacja

#### Nowe pliki
- `IMPORT_GUIDE.md` - Szczegółowy przewodnik importu
- `CHANGELOG_EXTENDED.md` - Ten plik
- `database/schema_extended.sql` - Skrypt migracji

#### Aktualizacje
- `README.md` - Dodano sekcję o imporcie
- `STRUCTURE.md` - Dodano nowe tabele i modele
- `INSTALLATION.md` - Dodano instrukcje migracji

### Kompatybilność wsteczna

#### Zachowana
- Wszystkie istniejące API endpoints działają bez zmian
- Istniejące faktury nie wymagają migracji
- Nowe pola są opcjonalne (nullable)

#### Zmiany wymagające uwagi
- Model `Invoice` ma rozszerzoną metodę `create()` - istniejący kod powinien działać (dodatkowe pola są opcjonalne)
- Dodano indeksy do tabeli invoices - może wpłynąć na wydajność insertów (nieznacznie)

### Testowanie

#### Testy jednostkowe (TODO)
```bash
# Testowanie importu
php tests/InvoiceImportServiceTest.php

# Testowanie modeli
php tests/InvoiceItemTest.php
php tests/ProjectCostTest.php
php tests/ProjectRevenueTest.php
```

#### Testy manualne
1. Import małego pliku (5-10 faktur)
2. Import pliku z błędami
3. Import duplikatów
4. Import z różnymi separatorami
5. Sprawdzenie powiązań z projektami
6. Sprawdzenie obliczania marży

### Znane problemy i ograniczenia

#### 1. Wartość `########` w Excel
- Pojawia się gdy kolumna jest za wąska
- System traktuje jako 0 lub NULL
- **Rozwiązanie:** Poszerz kolumny przed eksportem

#### 2. Format daty Excel
- Excel może eksportować daty w formacie liczb seryjnych
- **Rozwiązanie:** Przed eksportem sformatuj kolumny jako "Tekst"

#### 3. Duże pliki
- Import >1000 faktur może być wolny
- **Rozwiązanie:** Podziel plik na mniejsze części

#### 4. Kodowanie
- Excel domyślnie zapisuje CSV w innym kodowaniu niż UTF-8
- **Rozwiązanie:** Użyj "CSV UTF-8" w opcjach zapisu

### Przyszłe ulepszenia (Roadmap)

#### Wersja 1.2.0
- [ ] Import asynchroniczny (kolejka zadań)
- [ ] Progress bar podczas importu
- [ ] Podgląd danych przed importem
- [ ] Walidacja w czasie rzeczywistym
- [ ] Eksport do CSV/Excel
- [ ] Szablony importu

#### Wersja 1.3.0 - Pełna integracja KSeF, Email i Eksport
**Data wydania:** 2026-02-28

### Nowe funkcjonalności

#### 1. Pełna integracja z KSeF API
- Bezpośrednia komunikacja z systemem Ministerstwa Finansów.
- Obsługa autoryzacji RSA z szyfrowaniem tokenów (OpenSSL).
- Pobieranie listy faktur (nagłówków) dla zadanego okresu.
- Pobieranie pełnej treści faktur w formacie XML (FA-2) i automatyczny import do bazy.
- Parser XML FA(2) wyciągający pozycje faktury, stawki VAT i dane kontrahentów.

#### 2. Automatyczny import faktur z e-mail
- Serwis IMAP skanujący skrzynkę pocztową.
- Automatyczne wyodrębnianie załączników PDF i XML.
- Rozpoznawanie faktur KSeF XML i ich automatyczny import.
- Zapisywanie PDFów w module Dokumentów do późniejszej weryfikacji.
- Integracja z systemem zadań Cron.

#### 3. Eksport danych do CSV/Excel
- Możliwość generowania zestawień faktur do plików CSV.
- Obsługa filtrów (typ faktury, projekt) przy eksporcie.
- Kodowanie UTF-8 z BOM (poprawne wyświetlanie polskich znaków w Excelu).

#### 4. Uproszczenie architektury
- Całkowite usunięcie modułu wniosków urlopowych (Leave Requests) na rzecz zewnętrznego systemu.
- Usunięcie wszystkich powiązanych widoków, modeli i ścieżek API.

### Nowe serwisy
- `KsefService` - obsługa API KSeF i parsowanie XML.
- `EmailImportService` - integracja ze skrzynką IMAP.
- `ExportService` - generowanie raportów CSV.

### Zmiany w UI
- Nowy widok `/invoices/ksef` do zarządzania bezpośrednim importem.
- Przyciski "Pobierz z e-mail" oraz "Eksportuj do CSV" na liście faktur.
- Aktualizacja menu głównego i pulpitu.

---

**Wersja:** 1.3.0
**Data wydania:** 2026-02-28
**Autor:** ITSS Development Team
**Copyright:** ITSS Sp. z o.o.

