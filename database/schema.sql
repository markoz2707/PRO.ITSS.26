-- ITSS Project Management System Database Schema
-- MariaDB Database

-- Tabela użytkowników (synchronizowana z M365)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    m365_id VARCHAR(255) UNIQUE,
    role ENUM('admin', 'director', 'manager', 'team_leader', 'employee', 'helpdesk') DEFAULT 'employee',
    manager_id INT NULL,
    team_leader_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (team_leader_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_m365_id (m365_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela projektów (synchronizowana z Dynamics CRM)
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_number VARCHAR(50) UNIQUE NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    crm_id VARCHAR(255) UNIQUE,
    salesperson_id INT NULL,
    architect_id INT NULL,
    status ENUM('planning', 'active', 'completed', 'on_hold', 'cancelled') DEFAULT 'planning',
    start_date DATE,
    end_date DATE,
    description TEXT,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (salesperson_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (architect_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_number (project_number),
    INDEX idx_crm_id (crm_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela faktur (kosztowe i przychodowe)
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(100) NOT NULL,
    invoice_type ENUM('cost', 'revenue') NOT NULL,
    project_id INT NULL,
    supplier_name VARCHAR(255),
    client_name VARCHAR(255),
    invoice_date DATE NOT NULL,
    due_date DATE,
    net_amount DECIMAL(15, 2) NOT NULL,
    vat_amount DECIMAL(15, 2) NOT NULL,
    gross_amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PLN',
    payment_status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    payment_date DATE NULL,
    description TEXT,
    ksef_id VARCHAR(255) NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_invoice_type (invoice_type),
    INDEX idx_project_id (project_id),
    INDEX idx_invoice_date (invoice_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela dokumentów
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_name VARCHAR(255) NOT NULL,
    document_type ENUM('contract', 'invoice_attachment', 'acceptance_protocol', 'other') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100),
    project_id INT NULL,
    invoice_id INT NULL,
    description TEXT,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_project_id (project_id),
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_document_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela godzin pracy (synchronizowana z ServiceDesk Plus)
CREATE TABLE work_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    work_type ENUM('implementation', 'presales', 'support') NOT NULL,
    hours DECIMAL(5, 2) NOT NULL,
    work_date DATE NOT NULL,
    description TEXT,
    servicedesk_ticket_id VARCHAR(100),
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_user (project_id, user_id),
    INDEX idx_work_date (work_date),
    INDEX idx_work_type (work_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela wniosków urlopowych
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type ENUM('vacation', 'sick_leave', 'unpaid', 'occasional', 'on_demand') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_count DECIMAL(3, 1) NOT NULL,
    reason TEXT,
    status ENUM('draft', 'pending_team_leader', 'pending_manager', 'approved', 'rejected', 'cancelled') DEFAULT 'draft',
    team_leader_id INT NULL,
    team_leader_approved_at TIMESTAMP NULL,
    team_leader_comment TEXT,
    manager_id INT NULL,
    manager_approved_at TIMESTAMP NULL,
    manager_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_leader_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela historii statusów wniosków urlopowych
CREATE TABLE leave_request_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leave_request_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leave_request_id) REFERENCES leave_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id),
    INDEX idx_leave_request_id (leave_request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela schematów premiowych
CREATE TABLE bonus_schemes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NULL,
    bonus_type ENUM('margin_1', 'margin_2', 'hourly_rate', 'tickets_fixed', 'tickets_percent') NOT NULL,
    percentage DECIMAL(5, 2) NULL,
    fixed_amount DECIMAL(10, 2) NULL,
    hourly_rate DECIMAL(10, 2) NULL,
    tickets_pool DECIMAL(15, 2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    valid_from DATE NOT NULL,
    valid_to DATE NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_user_project (user_id, project_id),
    INDEX idx_bonus_type (bonus_type),
    INDEX idx_valid_dates (valid_from, valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela rozwiązanych zgłoszeń (dla helpdesku)
CREATE TABLE helpdesk_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(100) NOT NULL,
    user_id INT NOT NULL,
    project_id INT NULL,
    resolved_date DATE NOT NULL,
    ticket_status ENUM('resolved', 'closed') NOT NULL,
    servicedesk_id VARCHAR(100),
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_resolved_date (resolved_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela obliczonych premii
CREATE TABLE calculated_bonuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    bonus_scheme_id INT NOT NULL,
    calculation_base DECIMAL(15, 2) NOT NULL,
    bonus_amount DECIMAL(15, 2) NOT NULL,
    status ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
    calculation_details JSON,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (bonus_scheme_id) REFERENCES bonus_schemes(id),
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_period (user_id, period_start, period_end),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela ustawień systemu
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wstawienie domyślnych ustawień
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('crm_sync_enabled', 'true', 'Włącz automatyczną synchronizację z Dynamics CRM'),
('crm_sync_interval', '3600', 'Interwał synchronizacji CRM w sekundach'),
('servicedesk_sync_enabled', 'true', 'Włącz synchronizację z ServiceDesk Plus'),
('servicedesk_sync_interval', '1800', 'Interwał synchronizacji ServiceDesk w sekundach'),
('azure_tenant_id', '', 'Azure AD Tenant ID'),
('azure_client_id', '', 'Azure AD Application Client ID'),
('azure_client_secret', '', 'Azure AD Application Client Secret');
