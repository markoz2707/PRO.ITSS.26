# Database Schema Documentation

## 📁 Pliki schematu

### `schema.sql`
Podstawowy schemat bazy danych zawierający:
- Tabele użytkowników i sesji
- Tabele projektów
- Podstawowa struktura faktur
- Dokumenty
- Godziny pracy
- Wnioski urlopowe
- System premiowy
- Zgłoszenia helpdesk
- Logi synchronizacji

**Tabele:** 11 tabel podstawowych

### `schema_extended.sql`
Rozszerzenia schematu dla zaawansowanego zarządzania fakturami:
- Rozszerzone pola w tabeli `invoices` (MPK, Business Type, Segment, etc.)
- Tabela `invoice_items` - pozycje faktur
- Tabela `project_costs` - szczegółowe koszty projektów
- Tabela `project_revenues` - szczegółowe przychody projektów
- Tabele mapowania faktur do projektów
- Słowniki wartości

**Nowe tabele:** 6 tabel

---

## 🚀 Instalacja schematu

### Automatyczna (zalecane)

```bash
# Linux/macOS
./install.sh

# Windows
install.bat
```

Skrypt automatycznie zaimportuje oba pliki.

### Ręczna

#### 1. Utwórz bazę danych

```sql
CREATE DATABASE itss_projects CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'itss_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON itss_projects.* TO 'itss_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 2. Importuj schemat podstawowy

```bash
mysql -u itss_user -p itss_projects < schema.sql
```

#### 3. Importuj rozszerzenia

```bash
mysql -u itss_user -p itss_projects < schema_extended.sql
```

---

## 📊 Struktura tabel

### Tabele podstawowe (schema.sql)

#### `users`
Użytkownicy systemu z integracją Microsoft 365
- Autentykacja przez Azure AD
- Role: admin, director, manager, team_leader, employee, helpdesk

#### `projects`
Projekty zsynchronizowane z Dynamics 365 CRM
- Powiązania z handlowcami i architektami
- Statusy: draft, active, on_hold, completed, cancelled

#### `invoices`
Faktury kosztowe i przychodowe
- Powiązania z projektami
- Statusy płatności: pending, paid, overdue, cancelled

#### `documents`
Dokumenty projektowe i załączniki
- Kontrakty, faktury, protokoły

#### `work_hours`
Godziny pracy z ServiceDesk Plus
- Typy: presales, implementation, support
- Rozliczanie godzinowe

#### `leave_requests`
Wnioski urlopowe
- Proces dwupoziomowej akceptacji
- Typy: vacation, sick_leave, other

#### `bonus_schemes`
Schematy premiowe
- Typy: margin_1, margin_2, hourly_rate, helpdesk_percent, helpdesk_tickets

#### `calculated_bonuses`
Obliczone premie
- Powiązania z projektami i użytkownikami
- Statusy zatwierdzenia

#### `helpdesk_tickets`
Zgłoszenia helpdesk
- Synchronizacja z ServiceDesk Plus
- Śledzenie rozwiązań dla bonusów

### Tabele rozszerzone (schema_extended.sql)

#### `invoice_items`
Pozycje faktur z szczegółami:
- Ilość, jednostka, cena jednostkowa
- Kwoty: netto, VAT, brutto per pozycja
- Kategoria i typ biznesowy
- Kody MPK per pozycja

#### `project_costs`
Szczegółowe koszty projektu:
- Typy: invoice, labor, equipment, other
- Kategorie kosztów
- Kontrahenci
- Kody MPK

#### `project_revenues`
Szczegółowe przychody projektu:
- Typy: invoice, service, other
- Business Type, Segment, Sector
- Klienci
- Kody MPK

#### `invoice_cost_mapping`
Mapowanie faktur kosztowych do projektów (many-to-many):
- Procent alokacji
- Alokowana kwota netto

#### `invoice_revenue_mapping`
Mapowanie faktur przychodowych do projektów (many-to-many):
- Procent alokacji
- Alokowana kwota netto

#### `dictionaries`
Słowniki wartości dla:
- Business Types
- Segmenty
- Sektory
- Kategorie
- Kody MPK

---

## 🔄 Migracje

### Aktualizacja z wersji 1.0.0 do 1.1.0

```bash
# Backup bazy danych
mysqldump -u itss_user -p itss_projects > backup_$(date +%Y%m%d).sql

# Import rozszerzeń
mysql -u itss_user -p itss_projects < schema_extended.sql

# Weryfikacja
mysql -u itss_user -p itss_projects -e "SHOW TABLES;"
mysql -u itss_user -p itss_projects -e "DESCRIBE invoices;"
```

### Weryfikacja po migracji

```sql
-- Sprawdź nowe tabele
SHOW TABLES LIKE 'invoice_%';
SHOW TABLES LIKE 'project_%';

-- Sprawdź nowe kolumny w invoices
DESCRIBE invoices;

-- Sprawdź indeksy
SHOW INDEX FROM invoices;
SHOW INDEX FROM invoice_items;
```

---

## 🔍 Najczęstsze zapytania

### Faktury z pozycjami

```sql
SELECT
    i.invoice_number,
    i.contractor,
    ii.item_name,
    ii.quantity,
    ii.net_amount
FROM invoices i
LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
WHERE i.invoice_type = 'revenue'
ORDER BY i.invoice_date DESC;
```

### Koszty i przychody projektu

```sql
SELECT
    p.project_number,
    p.project_name,
    SUM(CASE WHEN pc.cost_type = 'invoice' THEN pc.net_amount ELSE 0 END) as invoice_costs,
    SUM(CASE WHEN pr.revenue_type = 'invoice' THEN pr.net_amount ELSE 0 END) as invoice_revenues
FROM projects p
LEFT JOIN project_costs pc ON p.id = pc.project_id
LEFT JOIN project_revenues pr ON p.id = pr.project_id
WHERE p.id = 1
GROUP BY p.id;
```

### Faktury według Business Type

```sql
SELECT
    business_type,
    COUNT(*) as count,
    SUM(net_amount) as total_net,
    SUM(gross_amount) as total_gross
FROM invoices
WHERE invoice_type = 'revenue'
GROUP BY business_type
ORDER BY total_net DESC;
```

### Faktury według kodów MPK

```sql
SELECT
    mpk_dh1,
    COUNT(*) as count,
    SUM(net_amount) as total
FROM invoices
WHERE mpk_dh1 IS NOT NULL
GROUP BY mpk_dh1
ORDER BY total DESC;
```

---

## 🛠️ Konserwacja bazy danych

### Optymalizacja tabel

```sql
OPTIMIZE TABLE invoices;
OPTIMIZE TABLE invoice_items;
OPTIMIZE TABLE project_costs;
OPTIMIZE TABLE project_revenues;
```

### Sprawdzenie rozmiaru tabel

```sql
SELECT
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'itss_projects'
ORDER BY (data_length + index_length) DESC;
```

### Backup

```bash
# Pełny backup
mysqldump -u itss_user -p itss_projects > backup_full_$(date +%Y%m%d_%H%M%S).sql

# Backup tylko struktury (bez danych)
mysqldump -u itss_user -p --no-data itss_projects > backup_structure_$(date +%Y%m%d).sql

# Backup tylko danych
mysqldump -u itss_user -p --no-create-info itss_projects > backup_data_$(date +%Y%m%d).sql
```

---

## 📋 Indeksy

### Istniejące indeksy

```sql
-- Faktury
idx_invoices_project_id
idx_invoices_invoice_type
idx_invoices_payment_status
idx_invoices_invoice_date
idx_invoices_business_type
idx_invoices_segment
idx_invoices_mpk_dh1
idx_invoices_contractor

-- Pozycje faktur
idx_invoice_items_invoice_id
idx_invoice_items_category
idx_invoice_items_business_type

-- Koszty projektu
idx_project_costs_project_id
idx_project_costs_cost_type
idx_project_costs_invoice_id

-- Przychody projektu
idx_project_revenues_project_id
idx_project_revenues_revenue_type
idx_project_revenues_invoice_id
```

---

## 🔐 Bezpieczeństwo

### Użytkownicy bazy danych

```sql
-- Użytkownik aplikacyjny (ograniczone uprawnienia)
CREATE USER 'itss_app'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE ON itss_projects.* TO 'itss_app'@'localhost';

-- Użytkownik read-only (dla raportów)
CREATE USER 'itss_reports'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT ON itss_projects.* TO 'itss_reports'@'localhost';

FLUSH PRIVILEGES;
```

### Backup uprawnień

```bash
mysql -u root -p -e "SELECT * FROM mysql.user WHERE user LIKE 'itss%'" > users_backup.txt
```

---

## 📞 Wsparcie

W razie problemów z bazą danych:
1. Sprawdź logi MySQL: `sudo tail -f /var/log/mysql/error.log`
2. Sprawdź status: `systemctl status mysql`
3. Sprawdź połączenie: `mysql -u itss_user -p itss_projects -e "SELECT 1"`

---

**Wersja schematu:** 1.1.0
**Ostatnia aktualizacja:** 2026-01-11
**Copyright:** ITSS Sp. z o.o.
