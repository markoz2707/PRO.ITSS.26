-- ROZSZERZENIE SCHEMATU BAZY DANYCH
-- Dodatkowe pola dla faktur i pozycje faktur

-- Rozszerzenie tabeli invoices o dodatkowe pola
ALTER TABLE invoices ADD COLUMN contractor VARCHAR(255) AFTER client_name;
ALTER TABLE invoices ADD COLUMN business_type VARCHAR(100) AFTER description;
ALTER TABLE invoices ADD COLUMN segment VARCHAR(100) AFTER business_type;
ALTER TABLE invoices ADD COLUMN sector VARCHAR(100) AFTER segment;
ALTER TABLE invoices ADD COLUMN category VARCHAR(255) AFTER sector;
ALTER TABLE invoices ADD COLUMN mpk_dh1 VARCHAR(50) AFTER category;
ALTER TABLE invoices ADD COLUMN mpk_dh2 VARCHAR(50) AFTER mpk_dh1;
ALTER TABLE invoices ADD COLUMN mpk_gnp VARCHAR(50) AFTER mpk_dh2;
ALTER TABLE invoices ADD COLUMN mpk_do VARCHAR(50) AFTER mpk_gnp;
ALTER TABLE invoices ADD COLUMN mpk_og VARCHAR(50) AFTER mpk_do;
ALTER TABLE invoices ADD COLUMN mpk_eu1 VARCHAR(50) AFTER mpk_og;
ALTER TABLE invoices ADD COLUMN mpk_eu2 VARCHAR(50) AFTER mpk_eu1;
ALTER TABLE invoices ADD COLUMN mpk_ono VARCHAR(50) AFTER mpk_eu2;
ALTER TABLE invoices ADD COLUMN mpk_ksdo VARCHAR(50) AFTER mpk_ono;
ALTER TABLE invoices ADD COLUMN operator_client VARCHAR(255) AFTER mpk_ksdo;
ALTER TABLE invoices ADD COLUMN payment_deadline_date DATE AFTER due_date;
ALTER TABLE invoices ADD COLUMN payment_received_date DATE AFTER payment_date;
ALTER TABLE invoices ADD COLUMN uwagi TEXT AFTER operator_client;
ALTER TABLE invoices ADD COLUMN baza_licze VARCHAR(255) AFTER uwagi;
ALTER TABLE invoices ADD COLUMN mpt VARCHAR(100) AFTER baza_licze;

-- Indeksy dla nowych pól
ALTER TABLE invoices ADD INDEX idx_business_type (business_type);
ALTER TABLE invoices ADD INDEX idx_segment (segment);
ALTER TABLE invoices ADD INDEX idx_sector (sector);
ALTER TABLE invoices ADD INDEX idx_mpk_dh1 (mpk_dh1);
ALTER TABLE invoices ADD INDEX idx_contractor (contractor);

-- Tabela pozycji faktur (szczegółowy podział)
CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    item_number INT DEFAULT 1,
    item_name VARCHAR(255),
    item_description TEXT,
    category VARCHAR(255),
    business_type VARCHAR(100),
    quantity DECIMAL(10, 3) DEFAULT 1,
    unit VARCHAR(50) DEFAULT 'szt',
    unit_net_price DECIMAL(15, 2),
    net_amount DECIMAL(15, 2) NOT NULL,
    vat_rate DECIMAL(5, 2) DEFAULT 23.00,
    vat_amount DECIMAL(15, 2) NOT NULL,
    gross_amount DECIMAL(15, 2) NOT NULL,
    mpk_dh1 VARCHAR(50),
    mpk_dh2 VARCHAR(50),
    mpk_gnp VARCHAR(50),
    mpk_do VARCHAR(50),
    mpk_og VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_business_type (business_type),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela kosztów przypisanych do projektów (szczegółowe koszty)
CREATE TABLE project_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    cost_type ENUM('invoice', 'labor', 'equipment', 'other') NOT NULL,
    cost_category VARCHAR(100),
    cost_name VARCHAR(255) NOT NULL,
    cost_description TEXT,
    invoice_id INT NULL,
    invoice_item_id INT NULL,
    net_amount DECIMAL(15, 2) NOT NULL,
    vat_amount DECIMAL(15, 2) DEFAULT 0,
    gross_amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PLN',
    cost_date DATE NOT NULL,
    contractor VARCHAR(255),
    mpk_code VARCHAR(50),
    przelewy_wyksztalci TEXT COMMENT 'Przelewy w wyksz. (np: BWP-9313kup/FS/03/03/2025 na kwote 24,438.15 zł)',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_item_id) REFERENCES invoice_items(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_project_id (project_id),
    INDEX idx_cost_type (cost_type),
    INDEX idx_cost_date (cost_date),
    INDEX idx_invoice_id (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela przychodów przypisanych do projektów (szczegółowe przychody)
CREATE TABLE project_revenues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    revenue_type ENUM('invoice', 'service', 'other') NOT NULL,
    revenue_category VARCHAR(100),
    revenue_name VARCHAR(255) NOT NULL,
    revenue_description TEXT,
    invoice_id INT NULL,
    invoice_item_id INT NULL,
    net_amount DECIMAL(15, 2) NOT NULL,
    vat_amount DECIMAL(15, 2) DEFAULT 0,
    gross_amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PLN',
    revenue_date DATE NOT NULL,
    client_name VARCHAR(255),
    business_type VARCHAR(100),
    segment VARCHAR(100),
    sector VARCHAR(100),
    mpk_code VARCHAR(50),
    przelewy_wyksztalci TEXT COMMENT 'Przelewy w wyksz.',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_item_id) REFERENCES invoice_items(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_project_id (project_id),
    INDEX idx_revenue_type (revenue_type),
    INDEX idx_revenue_date (revenue_date),
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_business_type (business_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela mapowania kosztów do faktur (wiele do wielu)
CREATE TABLE invoice_cost_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    project_id INT NOT NULL,
    cost_allocation_percent DECIMAL(5, 2) DEFAULT 100.00,
    allocated_net_amount DECIMAL(15, 2),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_invoice_project (invoice_id, project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela mapowania przychodów do faktur (wiele do wielu)
CREATE TABLE invoice_revenue_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    project_id INT NOT NULL,
    revenue_allocation_percent DECIMAL(5, 2) DEFAULT 100.00,
    allocated_net_amount DECIMAL(15, 2),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_invoice_project (invoice_id, project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela słowników dla kategorii, typów biznesowych itp.
CREATE TABLE dictionaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dictionary_type ENUM('business_type', 'segment', 'sector', 'category', 'cost_category', 'mpk') NOT NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_code VARCHAR(50),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_dict_code (dictionary_type, code),
    INDEX idx_dict_type (dictionary_type),
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wstawienie przykładowych danych słownikowych
INSERT INTO dictionaries (dictionary_type, code, name, description) VALUES
('business_type', '2.2', 'Bundled contracts', 'Umowy wiązkowe'),
('business_type', '4.1', 'Hardware sales', 'Sprzedaż sprzętu'),
('business_type', '5', 'Commercial', 'Komercyjne'),
('category', '0-20.00', 'USŁUGA ZGODNIE Z UMOWĄ ZAMÓWIENIEM', 'Standardowa usługa'),
('category', '6.00.10', 'KONSULTACJE', 'Usługi konsultacyjne'),
('category', '6.00.00', 'INNE USŁUGI', 'Inne usługi'),
('mpk', 'MPK-DH1', 'MPK-DH1', 'Miejsce powstawania kosztów DH1'),
('mpk', 'MPK-DH2', 'MPK-DH2', 'Miejsce powstawania kosztów DH2'),
('mpk', 'MPK-GNP', 'MPK-GNP', 'Miejsce powstawania kosztów GNP'),
('mpk', 'MPK-DO', 'MPK-DO', 'Miejsce powstawania kosztów DO'),
('mpk', 'MPK-OG', 'MPK-OG', 'Miejsce powstawania kosztów OG'),
('mpk', 'MPK-EU1', 'MPK-EU1', 'Miejsce powstawania kosztów EU1'),
('mpk', 'MPK-EU2', 'MPK-EU2', 'Miejsce powstawania kosztów EU2'),
('mpk', 'MPK-ONO', 'MPK-ONO', 'Miejsce powstawania kosztów ONO'),
('mpk', 'MPK-KSDO', 'MPK-KSDO', 'Miejsce powstawania kosztów KSDO');

-- Rozszerzenie tabeli projects o dodatkowe pola
ALTER TABLE projects ADD COLUMN opiekun_handlowy VARCHAR(255) AFTER architect_id;
ALTER TABLE projects ADD COLUMN uwagi TEXT AFTER description;
ALTER TABLE projects ADD COLUMN baza_licze VARCHAR(255) AFTER uwagi;
ALTER TABLE projects ADD COLUMN termin_platnosci DATE AFTER end_date;
ALTER TABLE projects ADD COLUMN termin_zaplaty DATE AFTER termin_platnosci;
