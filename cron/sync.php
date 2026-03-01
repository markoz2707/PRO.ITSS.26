<?php

/**
 * Cron job for automatic synchronization
 * Run this script periodically (e.g., every hour) using cron:
 *
 * 0 * * * * php /path/to/cron/sync.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function ($class) {
    $prefix = 'ITSS\\';
    $baseDir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use ITSS\Core\Database;
use ITSS\Core\Logger;
use ITSS\Services\DynamicsCRMService;
use ITSS\Services\ServiceDeskService;

$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    die('Configuration file not found.');
}

$config = require $configFile;

date_default_timezone_set($config['app']['timezone']);

Logger::init(
    $config['logging']['path'],
    $config['logging']['enabled'],
    $config['logging']['level']
);

Database::getInstance($config['database']);

Logger::info('=== Starting automatic synchronization ===');

if ($config['dynamics_crm']['sync_interval'] ?? false) {
    try {
        Logger::info('Starting Dynamics CRM synchronization');
        $crmService = new DynamicsCRMService($config['dynamics_crm']);
        $projectsCount = $crmService->syncProjects();
        Logger::info("Dynamics CRM sync completed: {$projectsCount} projects synchronized");
    } catch (\Exception $e) {
        Logger::error('Dynamics CRM synchronization failed: ' . $e->getMessage());
    }
}

if ($config['servicedesk']['sync_interval'] ?? false) {
    try {
        Logger::info('Starting ServiceDesk synchronization');
        $serviceDeskService = new ServiceDeskService($config['servicedesk']);

        $hoursCount = $serviceDeskService->syncWorkHours();
        Logger::info("ServiceDesk work hours sync completed: {$hoursCount} hours synchronized");

        $ticketsCount = $serviceDeskService->syncHelpdeskTickets();
        Logger::info("ServiceDesk tickets sync completed: {$ticketsCount} tickets synchronized");

        // Synchronizacja kontraktów z modułu Umowy
        if ($config['servicedesk']['sync_contracts'] ?? false) {
            $contractsCount = $serviceDeskService->syncContracts();
            Logger::info("ServiceDesk contracts sync completed: {$contractsCount} contracts synchronized");
        }

        // Synchronizacja projektów z modułu Projekty
        if ($config['servicedesk']['sync_projects'] ?? false) {
            $sdProjectsCount = $serviceDeskService->syncSDProjects();
            Logger::info("ServiceDesk projects sync completed: {$sdProjectsCount} projects synchronized");
        }
    } catch (\Exception $e) {
        Logger::error('ServiceDesk synchronization failed: ' . $e->getMessage());
    }
}

// Automatyczne uspójnianie (opcjonalne)
if ($config['reconciliation']['auto_reconcile_on_sync'] ?? false) {
    try {
        Logger::info('Starting automatic data reconciliation');
        $reconciliationService = new \ITSS\Services\DataReconciliationService();
        $results = $reconciliationService->executeAutoReconciliation(0); // system user
        Logger::info("Auto-reconciliation completed: merged={$results['merged']}, skipped={$results['skipped']}");
    } catch (\Exception $e) {
        Logger::error('Auto-reconciliation failed: ' . $e->getMessage());
    }
}

// Synchronizacja faktur z e-mail
if ($config['email_import']['enabled'] ?? false) {
    try {
        Logger::info('Starting email invoice synchronization');
        $uploadPath = $config['upload']['documents_path'];
        $emailService = new \ITSS\Services\EmailImportService($config['email_import'], $uploadPath);
        $emailResult = $emailService->syncInvoices();
        
        if ($emailResult['success']) {
            Logger::info('Email sync completed', $emailResult['data']);
        } else {
            Logger::error('Email sync failed: ' . $emailResult['error']);
        }
    } catch (\Exception $e) {
        Logger::error('Email synchronization failed: ' . $e->getMessage());
    }
}

Logger::info('=== Automatic synchronization completed ===');

echo "Synchronization completed. Check logs for details.\n";
