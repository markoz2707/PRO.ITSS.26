-- ROZSZERZENIE SCHEMATU - Moduł uspójniania danych CRM <-> ServiceDesk Plus MSP
-- Wersja 1.2.0

-- Tabela kontraktów z ServiceDesk Plus MSP (moduł Umowy)
CREATE TABLE IF NOT EXISTS servicedesk_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sd_contract_id VARCHAR(100) UNIQUE NOT NULL COMMENT 'ID kontraktu w ServiceDesk Plus',
    contract_name VARCHAR(255) NOT NULL,
    contract_number VARCHAR(100),
    account_name VARCHAR(255) COMMENT 'Nazwa klienta/konta w SD',
    contract_type VARCHAR(100) COMMENT 'Typ umowy (np. SLA, Maintenance, License)',
    status VARCHAR(50) COMMENT 'Status umowy w SD',
    start_date DATE,
    end_date DATE,
    cost DECIMAL(15, 2) COMMENT 'Wartość umowy',
    currency VARCHAR(3) DEFAULT 'PLN',
    description TEXT,
    vendor_name VARCHAR(255),
    support_type VARCHAR(100) COMMENT 'Typ wsparcia (np. 24x7, 8x5)',
    sla_name VARCHAR(255) COMMENT 'Nazwa SLA powiązanego',
    notification_before_days INT COMMENT 'Dni powiadomienia przed wygaśnięciem',
    sd_raw_data JSON COMMENT 'Pełne dane surowe z API ServiceDesk',
    project_id INT NULL COMMENT 'Powiązanie z lokalnym projektem (po uspójnieniu)',
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    INDEX idx_sd_contract_id (sd_contract_id),
    INDEX idx_contract_name (contract_name),
    INDEX idx_account_name (account_name),
    INDEX idx_status (status),
    INDEX idx_project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela projektów z ServiceDesk Plus MSP (moduł Projekty)
CREATE TABLE IF NOT EXISTS servicedesk_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sd_project_id VARCHAR(100) UNIQUE NOT NULL COMMENT 'ID projektu w ServiceDesk Plus',
    project_name VARCHAR(255) NOT NULL,
    project_code VARCHAR(100),
    owner_name VARCHAR(255) COMMENT 'Właściciel projektu w SD',
    owner_email VARCHAR(255),
    status VARCHAR(50) COMMENT 'Status projektu w SD (np. Active, On Hold, Closed)',
    priority VARCHAR(50),
    start_date DATE,
    end_date DATE,
    actual_start_date DATE,
    actual_end_date DATE,
    scheduled_hours DECIMAL(10, 2),
    actual_hours DECIMAL(10, 2),
    description TEXT,
    percentage_completion DECIMAL(5, 2),
    sd_raw_data JSON COMMENT 'Pełne dane surowe z API ServiceDesk',
    project_id INT NULL COMMENT 'Powiązanie z lokalnym projektem (po uspójnieniu)',
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    INDEX idx_sd_project_id (sd_project_id),
    INDEX idx_project_name (project_name),
    INDEX idx_project_code (project_code),
    INDEX idx_owner_email (owner_email),
    INDEX idx_status (status),
    INDEX idx_project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rozszerzenie tabeli projects o pola z ServiceDesk
ALTER TABLE projects ADD COLUMN IF NOT EXISTS servicedesk_project_id VARCHAR(100) NULL AFTER crm_id;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS servicedesk_contract_id VARCHAR(100) NULL AFTER servicedesk_project_id;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS sd_contract_value DECIMAL(15, 2) NULL AFTER servicedesk_contract_id;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS sd_contract_type VARCHAR(100) NULL AFTER sd_contract_value;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS sd_sla_name VARCHAR(255) NULL AFTER sd_contract_type;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS sd_support_type VARCHAR(100) NULL AFTER sd_sla_name;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS sd_scheduled_hours DECIMAL(10, 2) NULL AFTER sd_support_type;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS sd_actual_hours DECIMAL(10, 2) NULL AFTER sd_scheduled_hours;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS sd_completion_percent DECIMAL(5, 2) NULL AFTER sd_actual_hours;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS sd_last_sync_at TIMESTAMP NULL AFTER sd_completion_percent;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS data_source VARCHAR(50) DEFAULT 'crm' COMMENT 'crm, servicedesk, reconciled' AFTER sd_last_sync_at;

ALTER TABLE projects ADD INDEX IF NOT EXISTS idx_sd_project_id (servicedesk_project_id);
ALTER TABLE projects ADD INDEX IF NOT EXISTS idx_sd_contract_id (servicedesk_contract_id);

-- Tabela logów uspójniania danych
CREATE TABLE IF NOT EXISTS data_reconciliation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reconciliation_type ENUM('auto_match', 'manual_match', 'merge', 'unlink') NOT NULL,
    project_id INT NULL COMMENT 'Lokalny projekt',
    crm_id VARCHAR(255) NULL COMMENT 'ID z CRM',
    sd_project_id VARCHAR(100) NULL COMMENT 'ID projektu z ServiceDesk',
    sd_contract_id VARCHAR(100) NULL COMMENT 'ID kontraktu z ServiceDesk',
    match_confidence DECIMAL(5, 2) COMMENT 'Stopień pewności dopasowania (0-100)',
    match_method VARCHAR(50) COMMENT 'Metoda dopasowania (name, number, manual)',
    fields_updated JSON COMMENT 'Pola zaktualizowane podczas merge',
    fields_before JSON COMMENT 'Wartości pól przed merge',
    fields_after JSON COMMENT 'Wartości pól po merge',
    status ENUM('pending', 'applied', 'rejected', 'reverted') DEFAULT 'pending',
    performed_by INT NULL,
    performed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_id (project_id),
    INDEX idx_reconciliation_type (reconciliation_type),
    INDEX idx_status (status),
    INDEX idx_performed_at (performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
