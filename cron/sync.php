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
    } catch (\Exception $e) {
        Logger::error('ServiceDesk synchronization failed: ' . $e->getMessage());
    }
}

Logger::info('=== Automatic synchronization completed ===');

echo "Synchronization completed. Check logs for details.\n";
