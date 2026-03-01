<?php
/**
 * ITSS Project Management System
 * Configuration File
 *
 * Copy this file to config.php and fill in your settings
 */

return [
    // Database Configuration
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'itss_projects',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],

    // Application Settings
    'app' => [
        'name' => 'ITSS Project Management',
        'url' => 'http://localhost',
        'timezone' => 'Europe/Warsaw',
        'locale' => 'pl_PL',
        'debug' => true
    ],

    // Session Configuration
    'session' => [
        'lifetime' => 7200, // 2 hours
        'name' => 'ITSS_SESSION',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ],

    // Microsoft 365 / Azure AD Configuration
    'azure' => [
        'tenant_id' => '', // Your Azure AD Tenant ID
        'client_id' => '', // Your Application (client) ID
        'client_secret' => '', // Your Application Secret
        'redirect_uri' => 'http://localhost/auth/callback',
        'scopes' => ['openid', 'profile', 'email', 'User.Read']
    ],

    // Dynamics 365 CRM Configuration
    'dynamics_crm' => [
        'url' => 'https://itss.crm4.dynamics.com',
        'api_version' => '9.2',
        'client_id' => '', // CRM Application Client ID
        'client_secret' => '', // CRM Application Secret
        'resource' => 'https://itss.crm4.dynamics.com',
        'sync_interval' => 3600, // Sync every hour
        'projects_entity' => 'opportunities' // or custom entity name
    ],

    // ManageEngine ServiceDesk Plus MSP Configuration
    'servicedesk' => [
        'url' => '', // Your ServiceDesk Plus MSP URL (e.g. https://sdp.itss.pl)
        'api_key' => '', // ServiceDesk API Key
        'sync_interval' => 1800, // Sync every 30 minutes
        'technician_key' => '', // Technician key for API access
        'sync_contracts' => true, // Synchronize contracts module
        'sync_projects' => true, // Synchronize projects module
    ],

    // Data Reconciliation Settings
    'reconciliation' => [
        'auto_match_threshold' => 70, // Min confidence (%) for auto-matching
        'auto_reconcile_on_sync' => false, // Auto-reconcile after sync (true = no manual review)
    ],

    // KSeF Integration Configuration
    'ksef' => [
        'environment' => 'demo', // 'prod', 'test', 'demo'
        'nip' => '', // NIP Twojej firmy
        'token' => '', // Twój wygenerowany token KSeF
        'import_type' => 'cost', // Domyślny typ importowanych faktur (np. cost)
    ],

    // Email Invoice Import Configuration (IMAP)
    'email_import' => [
        'enabled' => false,
        'host' => 'imap.gmail.com',
        'port' => 993,
        'user' => '',
        'password' => '',
        'encryption' => 'ssl', // 'ssl', 'tls', 'none'
        'folder' => 'INBOX',
        'allowed_extensions' => ['pdf', 'xml'],
        'auto_archive' => true, // Przenoś do folderu 'Processed' po imporcie
        'processed_folder' => 'Processed'
    ],

    // File Upload Configuration
    'upload' => [
        'max_size' => 50 * 1024 * 1024, // 50 MB
        'allowed_types' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx',
            'jpg', 'jpeg', 'png', 'gif',
            'zip', 'rar', '7z'
        ],
        'documents_path' => __DIR__ . '/../uploads/documents',
        'invoices_path' => __DIR__ . '/../uploads/invoices'
    ],

    // Czasomat Integration
    'czasomat' => [
        'url' => 'https://czasomat.itss.pl'
    ],

    // Email Configuration (for notifications)
    'email' => [
        'from' => 'noreply@itss.pl',
        'from_name' => 'ITSS Project Management',
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls'
    ],

    // Bonus Calculation Settings
    'bonuses' => [
        'margin_1_formula' => 'revenue - direct_costs', // Marża 1 = Przychody - Koszty bezpośrednie
        'margin_2_formula' => 'margin_1 - labor_costs', // Marża 2 = Marża 1 - Koszty pracy
        'default_currency' => 'PLN'
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../logs',
        'level' => 'debug' // debug, info, warning, error
    ]
];
