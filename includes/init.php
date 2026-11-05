<?php
/**
 * ResourceHub Application Initialization
 * Starts the session, sets timezone, loads helpers and OOP classes.
 */

// Define application path constants
if (!defined('RH_ROOT')) {
    define('RH_ROOT', dirname(__DIR__));
    define('RH_PUBLIC', RH_ROOT . '/public');
    define('RH_SRC', RH_ROOT . '/src');
    define('RH_TEMPLATES', RH_ROOT . '/templates');
    define('RH_INCLUDES', RH_ROOT . '/includes');
    define('RH_CONFIG', RH_ROOT . '/config');
    define('RH_LOGS', RH_ROOT . '/logs');
}

// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', RH_LOGS . '/app.log');

// Set timezone
date_default_timezone_set('UTC');

// Load helper functions
require_once RH_INCLUDES . '/functions.php';

// Dynamic Class Autoloader (convention-over-configuration)
// Automatically discovers and loads any Service or Controller added to src/Controllers/ or src/Services/
spl_autoload_register(function ($class) {
    $searchPaths = [
        RH_SRC . '/Controllers',
        RH_SRC . '/Services',
        RH_SRC,
    ];

    $candidates = [
        $class,
        $class . 'Service',
        $class . 'Controller',
    ];

    foreach ($searchPaths as $dir) {
        foreach ($candidates as $name) {
            $file = $dir . '/' . str_replace('\\', '/', $name) . '.php';
            if (file_exists($file)) {
                require_once $file;
                if (class_exists($class, false)) {
                    return;
                }
            }
        }
    }
});

// // Pre-load Core Framework Classes
// require_once RH_SRC . '/Database.php';
// require_once RH_SRC . '/Services/AuthService.php';
// require_once RH_SRC . '/Router.php';

// Initialize session with secure flags (Page 9 requirement)
Auth::initSession();


