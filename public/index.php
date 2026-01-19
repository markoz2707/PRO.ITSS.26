<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
use ITSS\Core\Router;
use ITSS\Core\Request;
use ITSS\Core\Response;
use ITSS\Core\Session;
use ITSS\Core\Logger;
use ITSS\Modules\Auth\AuthMiddleware;

$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    die('Configuration file not found. Please copy config/config.example.php to config/config.php and configure it.');
}

$config = require $configFile;

date_default_timezone_set($config['app']['timezone']);

Logger::init(
    $config['logging']['path'],
    $config['logging']['enabled'],
    $config['logging']['level']
);

Database::getInstance($config['database']);

Session::start($config['session']);

$router = new Router();

$router->middleware([AuthMiddleware::class, 'handle']);

$router->get('/', function(Request $req, Response $res) {
    if (Session::get('user_id')) {
        $res->redirect('/dashboard');
    } else {
        $res->redirect('/auth/login');
    }
});

require __DIR__ . '/../src/routes.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

try {
    $router->dispatch($method, $uri);
} catch (\Exception $e) {
    Logger::error('Unhandled exception: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);

    $response = new Response();
    if ($config['app']['debug']) {
        $response->status(500)->json([
            'error' => 'Internal server error',
            'message' => $e->getMessage(),
            'trace' => $e->getTrace()
        ]);
    } else {
        $response->status(500)->json(['error' => 'Internal server error']);
    }
}
