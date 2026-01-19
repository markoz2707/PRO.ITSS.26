# Instrukcja importu faktur z CSV

## Przegląd

System ITSS Project Management umożliwia import faktur (kosztowych i przychodowych) z plików CSV. Import obsługuje rozbudowaną strukturę danych widoczną w arkuszach Excel używanych w firmie.

## Dostęp do funkcji importu

1. Zaloguj się do systemu
2. Przejdź do **Faktury** → **Import faktur**
3. Lub bezpośrednio: `http://your-domain.com/invoices/import`

## Format pliku CSV

### Separator
- Domyślnie: **średnik (;)**
- Obsługiwane także: przecinek (,), kreska pionowa (|)

### Kodowanie
- **UTF-8** (zalecane)
- UTF-8 z BOM (obsługiwane)

### Struktura

Plik CSV musi zawierać **wiersz nagłówkowy** z nazwami kolumn.

## Kolumny

### Obowiązkowe

| Nazwa kolumny | Alternatywy | Opis | Przykład |
|---------------|-------------|------|----------|
| **NUMER** | Nazwa, invoice_number | Numer faktury | FS/064/1/2024 |
| **KONTRAHENT** | contractor, supplier_name, client_name | Nazwa kontrahenta | Abbsa S.A. |
| **DATA WYSTAWIENIA** | DATA SPRZEDAŻY, invoice_date, DATA_WYSTAWIENIA | Data wystawienia | 31.03.2024 |
| **NETTO** | net_amount | Kwota netto | 1000.00 |
| **BRUTTO** | gross_amount | Kwota brutto | 1230.00 |

### Opcjonalne

| Nazwa kolumny | Opis | Przykład |
|---------------|------|----------|
| **PROJECT** | Numer projektu (jeśli istnieje w bazie) | ITSS/24/101 |
| **DATA PŁATNOŚCI** | Termin płatności | 7.04.2025 |
| **DATA ZAPŁATY** | Data zapłaty (jeśli już zapłacono) | 5.04.2025 |
| **KATEGORIA** | Kategoria faktury | 0-20.00 USŁUGA ZGODNIE Z UMOWĄ |
| **OPIS DOKUMENTU** | Szczegółowy opis | Usługa zgodnie z umową z dnia... |
| **Business Type** | Typ biznesowy | 2.2- Bundled contracts |
| **Segment** | Segment klienta | 2- Large-owned company |
| **Sector** | Sektor | |
| **MPK-DH1** | Kod MPK DH1 | 3.345,00 zł |
| **MPK-DH2** | Kod MPK DH2 | |
| **MPK-GNP** | Kod MPK GNP | |
| **MPK-DO** | Kod MPK DO | |
| **MPK-OG** | Kod MPK OG | |
| **MPK-EU1** | Kod MPK EU1 | |
| **MPK-EU2** | Kod MPK EU2 | |
| **MPK-ONO** | Kod MPK ONO | |
| **MPK-KSDO** | Kod MPK KSDO | |
| **OPER.KU/KLIENTA** | Operator/opiekun handlowy | Daniel Jaworski |
| **UWAGI** | Uwagi dodatkowe | Przelewy w wyksz. BWP-9313... |
| **Baza Licze** | Baza licencji | |
| **MPT** | | |

## Formaty dat

System rozpoznaje następujące formaty dat:
- `dd.mm.yyyy` (np. 31.03.2024)
- `yyyy-mm-dd` (np. 2024-03-31)
- `dd/mm/yyyy` (np. 31/03/2024)
- `dd-mm-yyyy` (np. 31-03-2024)

## Formaty kwot

System rozpoznaje:
- Kwoty z przecinkiem: `1000,50`
- Kwoty z kropką: `1000.50`
- Kwoty ze spacjami: `1 000,50`
- Kwoty bez separatorów tysięcy: `1000.50`

## Przykładowy plik CSV

```csv
Nazwa;KONTRAHENT;DATA WYSTAWIENIA;DATA PŁATNOŚCI;NETTO;BRUTTO;KATEGORIA;OPIS DOKUMENTU;Business Type;Segment;MPK-DH1;OPIEKUN HANDLOWY
FS/064/1/2024;Abbsa S.A.;31.03.2024;7.04.2025;1000.00;1230.00;0-20.00 USŁUGA ZGODNIE Z UMOWĄ;Usługa zgodnie z umową z dnia 05.12.2024;2.2- Bundled contracts;2- Large-owned company;3345.00;Anna Wiecko
FS/064/2/2024;Caba S.A.;31.03.2024;7.04.2025;696.29;856.44;4.1- Hardware sales;Sprzedaż sprzętu;;;506.32;Anna Wiecko
```

## Proces importu krok po kroku

### 1. Przygotowanie pliku

1. Eksportuj dane z Excel do CSV
2. W Excelu: **Plik** → **Zapisz jako** → **CSV UTF-8 (rozdzielany przecinkami) (*.csv)**
3. Jeśli używasz średnika jako separatora, upewnij się że jest poprawnie ustawiony

### 2. Import w systemie

1. Przejdź do **Faktury** → **Import faktur**
2. Wybierz plik CSV
3. Wybierz typ faktury:
   - **Przychód** - faktury sprzedażowe
   - **Koszt** - faktury zakupowe
4. Wybierz separator (domyślnie średnik)
5. Kliknij **Importuj**

### 3. Weryfikacja wyników

Po imporcie system wyświetli:
- Liczbę zaimportowanych faktur
- Liczbę pominiętych wierszy
- Listę błędów (jeśli wystąpiły)

## Automatyczne powiązanie z projektami

Jeśli w pliku CSV znajduje się kolumna **PROJECT** z numerem projektu:
- System automatycznie wyszuka projekt w bazie
- Jeśli projekt istnieje, faktura zostanie do niego przypisana
- Dodatkowo zostanie utworzony wpis w:
  - `project_costs` (dla faktur kosztowych)
  - `project_revenues` (dla faktur przychodowych)

## Obsługa duplikatów

System sprawdza czy faktura o danym numerze już istnieje:
- Jeśli **TAK** - faktura zostanie pominięta (błąd)
- Jeśli **NIE** - faktura zostanie zaimportowana

## Automatyczne pozycje faktury

Dla każdej zaimportowanej faktury system automatycznie tworzy jedną pozycję w tabeli `invoice_items` zawierającą:
- Pełną kwotę faktury
- Kategorię
- Typ biznesowy
- Kody MPK
- Opis

## Status płatności

System automatycznie ustala status płatności:
- **paid** - jeśli podano datę zapłaty
- **overdue** - jeśli termin płatności minął i nie podano daty zapłaty
- **pending** - w pozostałych przypadkach

## Obsługa błędów

### Częste błędy i rozwiązania

#### "Invoice XXX already exists"
**Przyczyna:** Faktura o tym numerze już istnieje w bazie.
**Rozwiązanie:** Usuń duplikat z pliku CSV lub zmień numer faktury.

#### "Row X: Invalid data format"
**Przyczyna:** Wiersz ma niepoprawną liczbę kolumn.
**Rozwiązanie:** Sprawdź czy wszystkie komórki są poprawnie rozdzielone separatorami.

#### "Invalid CSV format - no header"
**Przyczyna:** Brak wiersza nagłówkowego w pliku.
**Rozwiązanie:** Dodaj wiersz z nazwami kolumn na początku pliku.

#### Kwoty wynoszą 0
**Przyczyna:** Niepoprawny format kwot lub kolumna zawiera `########`.
**Rozwiązanie:** Sprawdź format komórek w Excel, upewnij się że są liczbami, nie tekstem.

## Wartości specjalne

### Puste wartości
- Puste komórki są pomijane i traktowane jako `NULL`
- Dopuszczalne dla wszystkich kolumn opcjonalnych

### Wartość `########`
- System traktuje `########` jako pustą wartość
- Często pojawia się gdy kolumna w Excel jest za wąska

### Wartość `-`
- Traktowana jako pusta wartość
- Często używana dla nieuzupełnionych dat

## Import przez API

### Endpoint
```
POST /api/invoices/import
```

### Parametry
- `csv_file` (required, file) - Plik CSV
- `invoice_type` (required, string) - `revenue` lub `cost`
- `delimiter` (optional, string) - Separator, domyślnie `;`

### Przykład z cURL

```bash
curl -X POST http://your-domain.com/api/invoices/import \
  -F "csv_file=@faktury.csv" \
  -F "invoice_type=revenue" \
  -F "delimiter=;" \
  -H "Cookie: ITSS_SESSION=your_session_id"
```

### Odpowiedź JSON

```json
{
  "success": true,
  "imported": 125,
  "skipped": 5,
  "errors": [
    "Row 10: Invoice FS/001/2024 already exists",
    "Row 23: Invalid data format"
  ]
}
```

## Najlepsze praktyki

1. **Testuj na małym pliku**
   - Przed importem dużego pliku, przetestuj na 5-10 wierszach

2. **Sprawdź duplikaty**
   - Przed importem sprawdź czy faktury nie istnieją już w systemie

3. **Waliduj dane**
   - Upewnij się że wszystkie kwoty są liczbami
   - Sprawdź formaty dat

4. **Backup**
   - Wykonaj backup bazy danych przed dużym importem

5. **Logi**
   - Sprawdź logi aplikacji: `/logs/YYYY-MM-DD.log`

## Rozwiązywanie problemów

### Import trwa bardzo długo
- Podziel plik na mniejsze części (np. 100-200 faktur)
- Zwiększ limit czasu PHP: `max_execution_time` w `php.ini`

### Błędy pamięci
- Zwiększ `memory_limit` w `php.ini`
- Podziel plik na mniejsze części

### Błędne kodowanie znaków (krzaczki)
- Zapisz plik jako UTF-8 w edytorze tekstu
- Użyj **CSV UTF-8** w Excel, nie zwykłego CSV

### Faktury importują się ale bez projektów
- Sprawdź czy numery projektów w CSV są dokładnie takie same jak w bazie
- Wielkość liter ma znaczenie: `ITSS/24/101` ≠ `itss/24/101`

## Wsparcie

W razie problemów z importem:
1. Sprawdź logi: `/logs/YYYY-MM-DD.log`
2. Sprawdź format pliku CSV
3. Skontaktuj się z administratorem systemu

---

**Wersja:** 1.0
**Data aktualizacji:** 2024
**Copyright:** ITSS Sp. z o.o.
