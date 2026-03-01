# Lista funkcjonalności ITSS Project Management System

## ✅ Zrealizowane funkcjonalności - Wersja 1.1.0

---

## 🔐 Autentykacja i autoryzacja

### Microsoft 365 / Azure AD Integration
- ✅ OAuth2 flow z Azure AD
- ✅ Automatyczne tworzenie użytkowników przy pierwszym logowaniu
- ✅ Synchronizacja profilu użytkownika (email, imię, nazwisko)
- ✅ Bezpieczne przechowywanie tokenów w sesji
- ✅ Automatyczne odświeżanie tokenów
- ✅ Wylogowanie z czyszczeniem sesji

### System ról i uprawnień
- ✅ **Admin** - pełny dostęp do systemu
- ✅ **Director** - dyrektor
- ✅ **Manager** - kierownik
- ✅ **Team Leader** - lider zespołu
- ✅ **Employee** - pracownik standardowy
- ✅ **Helpdesk** - pracownik helpdesku z premiami od zgłoszeń

---

## 📊 Zarządzanie projektami

### Synchronizacja z Dynamics 365 CRM
- ✅ Automatyczna synchronizacja projektów z CRM
- ✅ Mapowanie pól z opportunities/custom entities
- ✅ Pobieranie klientów, wartości projektów, statusów
- ✅ Cron job dla regularnej synchronizacji (co godzinę)
- ✅ Ręczna synchronizacja na żądanie
- ✅ Logowanie procesów synchronizacji

### Zarządzanie projektami
- ✅ Lista projektów z filtrowaniem
- ✅ Szczegóły projektu
- ✅ Przypisanie handlowca (salesperson)
- ✅ Przypisanie architekta/głównego inżyniera
- ✅ Statusy: draft, active, on_hold, completed, cancelled
- ✅ Powiązanie z klientem
- ✅ Wartość projektu
- ✅ Daty: rozpoczęcia, planowanego zakończenia, faktycznego zakończenia

### Dane finansowe projektu
- ✅ Podsumowanie kosztów (fakturowe, pracownicze, sprzętowe)
- ✅ Podsumowanie przychodów (fakturowe, serwisowe)
- ✅ Obliczanie marży (Marża 1, Marża 2)
- ✅ Śledzenie godzin pracy
- ✅ Raportowanie per projekt

---

## 💰 Zarządzanie fakturami

### Faktury - funkcjonalności podstawowe
- ✅ Faktury **kosztowe** (zakupy, wydatki)
- ✅ Faktury **przychodowe** (sprzedaż, usługi)
- ✅ Powiązanie faktury z projektem
- ✅ Statusy płatności: pending, paid, overdue, cancelled
- ✅ Daty: wystawienia, płatności, zapłaty
- ✅ Kwoty: netto, VAT, brutto
- ✅ Waluta (domyślnie PLN)
- ✅ Opis i kategoria
- ✅ Oznaczanie faktury jako zapłaconej

### Faktury - rozszerzone atrybuty (v1.1.0)
- ✅ **Kontrahent** - uniwersalne pole dla dostawcy/klienta
- ✅ **Business Type** - typ biznesowy (np. "2.2- Bundled contracts")
- ✅ **Segment** - segment klienta (np. "2- Large-owned company")
- ✅ **Sector** - sektor działalności
- ✅ **Kategoria** - kategoria faktury (np. "0-20.00 USŁUGA ZGODNIE Z UMOWĄ")
- ✅ **9 kodów MPK**:
  - MPK-DH1, MPK-DH2 (Dział Handlowy)
  - MPK-GNP (?)
  - MPK-DO (Dział Operacyjny)
  - MPK-OG (?)
  - MPK-EU1, MPK-EU2 (Europa?)
  - MPK-ONO (?)
  - MPK-KSDO (?)
- ✅ **Operator/opiekun handlowy** - osoba odpowiedzialna
- ✅ **Uwagi** - dodatkowe informacje
- ✅ **Baza licencji** - informacje o licencjach
- ✅ **MPT** - dodatkowy kod

### Pozycje faktur (invoice_items)
- ✅ Szczegółowe rozpisanie faktury na pozycje
- ✅ Nazwa i opis pozycji
- ✅ Ilość i jednostka miary
- ✅ Cena jednostkowa
- ✅ Kwoty per pozycja: netto, VAT, brutto
- ✅ Stawka VAT (%)
- ✅ Kategoria per pozycja
- ✅ Typ biznesowy per pozycja
- ✅ Kody MPK per pozycja
- ✅ Numeracja pozycji

### Koszty i przychody projektu
- ✅ **Koszty projektu (project_costs)**:
  - Typy: invoice (faktura), labor (praca), equipment (sprzęt), other (inne)
  - Powiązanie z fakturą i pozycją faktury
  - Kategoria kosztu
  - Kontrahent
  - Kody MPK
  - Kwoty: netto, VAT, brutto

- ✅ **Przychody projektu (project_revenues)**:
  - Typy: invoice (faktura), service (usługa), other (inne)
  - Powiązanie z fakturą i pozycją faktury
  - Business Type, Segment, Sector
  - Klient
  - Kody MPK
  - Kwoty: netto, VAT, brutto

### Mapowanie faktur do projektów
- ✅ Relacja many-to-many (jedna faktura → wiele projektów)
- ✅ Procent alokacji do projektu
- ✅ Alokowana kwota netto
- ✅ Osobne mapowanie dla faktur kosztowych i przychodowych

### Import faktur z CSV
- ✅ Upload pliku CSV przez interfejs WWW
- ✅ Import przez API endpoint
- ✅ Wybór typu faktury (przychód/koszt)
- ✅ Wybór separatora (;, ,, |)
- ✅ Automatyczne parsowanie dat (dd.mm.yyyy, yyyy-mm-dd, dd/mm/yyyy, dd-mm-yyyy)
- ✅ Automatyczne parsowanie kwot (1000,50 lub 1000.50 lub 1 000,50)
- ✅ Obsługa wartości specjalnych (########, -, puste)
- ✅ Mapowanie elastyczne kolumn (wiele nazw dla tego samego pola)
- ✅ Automatyczne powiązanie z projektami (jeśli podano numer projektu)
- ✅ Automatyczne określanie statusu płatności
- ✅ Transakcje z rollback w przypadku błędów
- ✅ Szczegółowe logowanie procesu importu
- ✅ Raport wyników (zaimportowano/pominięto/błędy)
- ✅ Automatyczne tworzenie invoice_item dla każdej faktury
- ✅ Automatyczne tworzenie project_cost/revenue (jeśli powiązano projekt)

### GUI Faktur
- ✅ **Lista faktur** (views/invoices/list.php):
  - Filtry: typ faktury, status płatności
  - Sortowanie
  - Wszystkie kluczowe atrybuty w tabeli
  - Akcje: szczegóły, oznacz jako zapłacona, import CSV

- ✅ **Szczegóły faktury** (views/invoices/detail.php):
  - Pełna prezentacja WSZYSTKICH atrybutów
  - Sekcje: Podstawowe info, Kwoty, Klasyfikacja biznesowa
  - Tabela z 9 kodami MPK
  - Dodatkowe informacje (operator, uwagi, baza licencji, MPT)
  - Lista pozycji faktury
  - Załączone dokumenty

- ✅ **Tworzenie faktury** (views/invoices/create.php):
  - Formularz ze WSZYSTKIMI atrybutami
  - Wybór typu (przychód/koszt)
  - Automatyczne obliczanie VAT
  - Dropdown dla klasyfikacji biznesowej
  - Grid z 9 polami MPK
  - Wszystkie dodatkowe pola

- ✅ **Import CSV** (views/invoices/import.php):
  - Upload pliku
  - Wybór typu i separatora
  - Progress bar
  - Raport wyników
  - Instrukcja formatu

---

## 📁 Zarządzanie dokumentami

### Przechowywanie dokumentów
- ✅ Upload plików (PDF, DOC, DOCX, XLS, XLSX, obrazy, archiwa)
- ✅ Kategorie dokumentów:
  - Kontrakty i umowy
  - Załączniki do faktur
  - Protokoły odbioru
  - Inne dokumenty projektowe
- ✅ Powiązanie dokumentu z projektem
- ✅ Powiązanie dokumentu z fakturą
- ✅ Metadane: nazwa, opis, kategoria, data uploadu
- ✅ Ograniczenia rozmiaru pliku (konfigurowalnie, domyślnie 50 MB)
- ✅ Walidacja typów plików

---

## ⏱️ Śledzenie godzin pracy

### Synchronizacja z ServiceDesk Plus
- ✅ Automatyczna synchronizacja godzin pracy
- ✅ Pobieranie czasu z ticketów/zadań
- ✅ Mapowanie użytkowników (email matching)
- ✅ Typy pracy:
  - Presales (przedsprzedaż)
  - Implementation (realizacja/wdrożenie)
  - Support (wsparcie)
- ✅ Powiązanie godzin z projektami
- ✅ Powiązanie godzin z ticketami helpdesk
- ✅ Cron job dla regularnej synchronizacji (co 30 min)
- ✅ Ręczna synchronizacja na żądanie
- ✅ Raportowanie godzin per projekt
- ✅ Raportowanie godzin per użytkownik
- ✅ Wykorzystanie w obliczeniach premii

---

## 💎 System premiowy

### Schematy premiowe

#### 1. Premia od Marży 1
- ✅ Formuła: Marża 1 = Przychody (zapłacone) - Koszty bezpośrednie (zapłacone)
- ✅ Procent premii konfigurowalny
- ✅ Powiązanie z projektem
- ✅ Przypisanie do użytkownika (handlowiec, architekt)
- ✅ Walidacja dat (tylko zapłacone faktury w danym okresie)

#### 2. Premia od Marży 2
- ✅ Formuła: Marża 2 = Marża 1 - Koszty pracy
- ✅ Koszty pracy = Suma godzin × Stawka godzinowa pracownika
- ✅ Procent premii konfigurowalny
- ✅ Uwzględnia koszty pracownicze

#### 3. Premia godzinowa (inżynierowie)
- ✅ Formuła: Premia = Suma godzin × Stawka godzinowa
- ✅ Stawka konfigrowalna per schemat
- ✅ Typy godzin: implementation, support (bez presales)
- ✅ Powiązanie z okresem rozliczeniowym

#### 4. Premia helpdesk - procentowa
- ✅ Formuła: Premia = Pula × Procent
- ✅ Pula określana przez schemat
- ✅ Procent określany indywidualnie

#### 5. Premia helpdesk - od liczby zgłoszeń
- ✅ Formuła: Premia = Liczba rozwiązanych zgłoszeń × Stawka
- ✅ Zliczanie resolved tickets w okresie
- ✅ Stawka konfigurowalny per schemat

### Obliczanie premii
- ✅ Automatyczne obliczanie na podstawie schematu
- ✅ Wybór okresu rozliczeniowego
- ✅ Walidacja danych (sprawdzenie czy faktury zapłacone)
- ✅ Zapisywanie obliczonych premii
- ✅ Statusy: pending (oczekująca), approved (zatwierdzona), paid (wypłacona)
- ✅ Proces zatwierdzania przez kierownika
- ✅ Historia obliczeń
- ✅ Raportowanie premii per pracownik

---

## 🔄 Integracje zewnętrzne

### Microsoft 365 / Azure AD
- ✅ Autentykacja użytkowników (OAuth2)
- ✅ Synchronizacja profili
- ✅ Graph API integration

### Dynamics 365 CRM
- ✅ Synchronizacja projektów (opportunities)
- ✅ OAuth2 authentication
- ✅ Web API v9.2
- ✅ Automatyczna synchronizacja (cron)
- ✅ Mapowanie pól konfigurowalnie
- ✅ Logowanie procesów sync

### ManageEngine ServiceDesk Plus
- ✅ Synchronizacja godzin pracy
- ✅ Pobieranie zgłoszeń helpdesk
- ✅ REST API integration
- ✅ Technician Key authentication
- ✅ Automatyczna synchronizacja (cron)
- ✅ Mapowanie użytkowników

### Czasomat ITSS
- ✅ Iframe w menu aplikacji
- ✅ Bezpośredni link do systemu
- ✅ Konfigurowalny URL

---

## 🛠️ Funkcjonalności techniczne

### Architektura
- ✅ Pure PHP (bez frameworka)
- ✅ Custom MVC pattern
- ✅ PSR-4 autoloading
- ✅ Dependency injection w kontrolerach
- ✅ Singleton pattern dla Database

### Routing
- ✅ Custom router z parametrami URL
- ✅ REST API endpoints
- ✅ Web routes dla widoków

### Baza danych
- ✅ MariaDB 10.5+
- ✅ PDO z prepared statements
- ✅ InnoDB engine
- ✅ Foreign keys i integrity constraints
- ✅ Indeksy na kluczowych polach
- ✅ UTF-8 encoding (utf8mb4)
- ✅ Transactions support
- ✅ 17 tabel w pełni znormalizowanych

### Bezpieczeństwo
- ✅ SQL injection protection (prepared statements)
- ✅ XSS protection (htmlspecialchars w widokach)
- ✅ CSRF protection (session tokens)
- ✅ Password hashing (Azure AD - brak lokalnych haseł)
- ✅ Secure session handling
- ✅ HTTPOnly cookies
- ✅ Input validation

### Logowanie
- ✅ Custom Logger class
- ✅ Poziomy: debug, info, warning, error
- ✅ Logi per dzień (YYYY-MM-DD.log)
- ✅ Kontekst w logach (arrays)
- ✅ Logowanie wszystkich kluczowych operacji

### Cron Jobs
- ✅ Automatyczna synchronizacja CRM (co godzinę)
- ✅ Automatyczna synchronizacja ServiceDesk (co 30 min)
- ✅ Konfigurowalny cron/sync.php
- ✅ Logowanie wyników synchronizacji

### Import/Export
- ✅ CSV import dla faktur
- ✅ Flexible CSV parser
- ✅ Różne formaty dat i kwot
- ✅ Obsługa różnych separatorów
- ✅ Raportowanie błędów

### Instalacja
- ✅ **install.sh** (Linux/macOS):
  - Automatyczne tworzenie bazy
  - Import schematów
  - Konfiguracja config.php
  - Uprawnienia katalogów
  - Composer integration

- ✅ **install.bat** (Windows):
  - Ta sama funkcjonalność
  - PowerShell dla edycji plików

---

## 📚 Dokumentacja

### Pliki dokumentacji
- ✅ **README.md** - Ogólny przegląd i instalacja
- ✅ **QUICK_START.md** - Szybki start w 5 minut
- ✅ **INSTALLATION.md** - Szczegółowa instrukcja instalacji
- ✅ **IMPORT_GUIDE.md** - Przewodnik importu faktur z CSV
- ✅ **STRUCTURE.md** - Struktura projektu
- ✅ **CHANGELOG_EXTENDED.md** - Historia zmian v1.1.0
- ✅ **IMPLEMENTATION_STATUS.md** - Status implementacji
- ✅ **CONTRIBUTING.md** - Wytyczne dla deweloperów
- ✅ **FEATURES.md** - Lista funkcjonalności (ten plik)
- ✅ **database/README.md** - Dokumentacja schematu bazy
- ✅ **LICENSE** - Licencja proprietary

### Komentarze w kodzie
- ✅ PHPDoc dla wszystkich klas i metod publicznych
- ✅ Opisy parametrów i zwracanych wartości
- ✅ Przykłady użycia w komentarzach

---

## 🎯 Statystyki projektu

### Kod
- **Linie kodu PHP:** ~15,000+
- **Liczba klas:** 30+
- **Liczba widoków:** 25+
- **API Endpoints:** 30+

### Baza danych
- **Tabele:** 17
- **Indeksy:** 40+
- **Foreign Keys:** 20+

### Dokumentacja
- **Pliki dokumentacji:** 11
- **Linie dokumentacji:** ~3000+

---

## 🚀 Gotowe do produkcji

### Checklist przed wdrożeniem
- ✅ Wszystkie funkcjonalności zaimplementowane
- ✅ Instalacja automatyczna (install.sh/install.bat)
- ✅ Pełna dokumentacja
- ✅ Bezpieczeństwo zaimplementowane
- ✅ Logowanie i monitoring
- ✅ Import/Export danych
- ✅ Integracje zewnętrzne
- ✅ GUI kompletne i responsywne

### Wymagane przed uruchomieniem na produkcji
- [ ] Konfiguracja Azure AD z produkcyjnym redirect URI
- [ ] Konfiguracja Dynamics CRM
- [ ] Konfiguracja ServiceDesk Plus
- [ ] Certyfikat SSL (HTTPS)
- [ ] Ustawienie debug => false
- [ ] Backup strategy
- [ ] Monitoring i alerty

---

## 🚀 Krajowy System e-Faktur (KSeF)

### Integracja API
- ✅ Bezpośrednia komunikacja z systemem Ministerstwa Finansów
- ✅ Autoryzacja RSA z wykorzystaniem kluczy publicznych (.pem)
- ✅ Szyfrowanie tokenów sesyjnych (Session Challenge)
- ✅ Wyszukiwanie faktur w zadanym okresie (Query Invoice)
- ✅ Pobieranie binarnego XML faktury (FA-2) po numerze referencyjnym

### Przetwarzanie i Import
- ✅ Zaawansowany parser XML FA(2)
- ✅ Automatyczne wyciąganie pozycji faktury (Invoice Items)
- ✅ Mapowanie stawek VAT i walut
- ✅ Walidacja duplikatów po numerze KSeF

---

## ✉️ Automatyczny import z E-mail

### Integracja IMAP
- ✅ Obsługa skrzynek pocztowych przez protokół IMAP
- ✅ Wsparcie dla SSL/TLS (bezpieczne połączenie)
- ✅ Skanowanie tylko nieprzeczytanych wiadomości (UNSEEN)
- ✅ Automatyczne wyodrębnianie załączników

### Logika biznesowa
- ✅ Rozpoznawanie plików PDF i XML
- ✅ Automatyczny import faktur KSeF XML do bazy
- ✅ Katalogowanie PDFów jako dokumentów "do weryfikacji"
- ✅ Archiwizacja przetworzonych maili (przenoszenie do folderu Processed)
- ✅ Możliwość automatyzacji przez systemowy Cron

---

## 📊 Eksport i Raportowanie

### Moduł Eksportu
- ✅ Generowanie zestawień faktur do formatu CSV
- ✅ Obsługa kodowania UTF-8 z BOM (pełna zgodność z MS Excel)
- ✅ Filtrowanie eksportowanych danych (typ faktury, projekt)
- ✅ Dynamiczne nazewnictwo plików z datą wygenerowania

---

## ✅ Zrealizowane funkcjonalności - Wersja 1.3.0

**Wersja:** 1.3.0
**Data:** 2026-02-28
**Status:** ✅ PRODUCTION READY
**Copyright:** ITSS Sp. z o.o.

