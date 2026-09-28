<?php
/**
 * BetLedger - Sports Betting Tracker
 * Main Router/Index File
 */

// Start session and load configuration
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../config/helpers.php';

startSession();

// Load Models
require_once '../src/models/User.php';
require_once '../src/models/Bet.php';
require_once '../src/models/BetLeg.php';
require_once '../src/models/Bookmaker.php';
require_once '../src/models/Tag.php';
require_once '../src/models/Tipster.php';
require_once '../src/models/Sport.php';
require_once '../src/models/Analytics.php';

// Load Controllers
require_once '../src/controllers/AuthController.php';
require_once '../src/controllers/DashboardController.php';
require_once '../src/controllers/BetController.php';
require_once '../src/controllers/StatisticsController.php';
require_once '../src/controllers/BookmakerController.php';
require_once '../src/controllers/TagController.php';
require_once '../src/controllers/TipsterController.php';
require_once '../src/controllers/SettingsController.php';

// Parse URL
$request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$request_method = $_SERVER['REQUEST_METHOD'];

// API routes for AJAX requests
if ($request_uri === 'api' || strpos($request_uri, 'api/') === 0) {
    require_once '../src/api.php';
    exit;
}

// Route table: [method, pattern, handler]. Captured ids are passed as ints.
$routes = [
    ['GET', '', 'DashboardController::index'],
    ['GET', 'dashboard', 'DashboardController::index'],

    ['GET', 'login', 'AuthController::showLogin'],
    ['POST', 'login', 'AuthController::handleLogin'],
    ['GET', 'register', 'AuthController::showRegister'],
    ['POST', 'register', 'AuthController::handleRegister'],
    ['GET', 'logout', 'AuthController::handleLogout'],
    ['POST', 'logout', 'AuthController::handleLogout'],

    ['GET', 'bets', 'BetController::list'],
    ['GET', 'bets/export', 'BetController::export'],
    ['GET', 'bets/add', 'BetController::showAdd'],
    ['POST', 'bets/add', 'BetController::handleAdd'],
    ['GET', 'bets/(\d+)', 'BetController::show'],
    ['GET', 'bets/(\d+)/edit', 'BetController::showEdit'],
    ['POST', 'bets/(\d+)/edit', 'BetController::handleEdit'],
    ['POST', 'bets/(\d+)/delete', 'BetController::delete'],

    ['GET', 'statistics', 'StatisticsController::index'],

    ['GET', 'bookmakers', 'BookmakerController::list'],
    ['GET', 'bookmakers/add', 'BookmakerController::showAdd'],
    ['POST', 'bookmakers/add', 'BookmakerController::handleAdd'],
    ['GET', 'bookmakers/(\d+)/edit', 'BookmakerController::showEdit'],
    ['POST', 'bookmakers/(\d+)/edit', 'BookmakerController::handleEdit'],
    ['POST', 'bookmakers/(\d+)/delete', 'BookmakerController::delete'],

    ['GET', 'tags', 'TagController::list'],
    ['GET', 'tags/add', 'TagController::showAdd'],
    ['POST', 'tags/add', 'TagController::handleAdd'],
    ['GET', 'tags/(\d+)/edit', 'TagController::showEdit'],
    ['POST', 'tags/(\d+)/edit', 'TagController::handleEdit'],
    ['POST', 'tags/(\d+)/delete', 'TagController::delete'],

    ['GET', 'tipsters', 'TipsterController::list'],
    ['GET', 'tipsters/add', 'TipsterController::showAdd'],
    ['POST', 'tipsters/add', 'TipsterController::handleAdd'],
    ['GET', 'tipsters/(\d+)/edit', 'TipsterController::showEdit'],
    ['POST', 'tipsters/(\d+)/edit', 'TipsterController::handleEdit'],
    ['POST', 'tipsters/(\d+)/delete', 'TipsterController::delete'],

    ['GET', 'settings', 'SettingsController::show'],
    ['POST', 'settings', 'SettingsController::handlePost'],
];

$pathMatched = false;
foreach ($routes as [$method, $pattern, $handler]) {
    if (!preg_match('#^' . $pattern . '$#', $request_uri, $matches)) {
        continue;
    }
    $pathMatched = true;
    if ($method !== $request_method) {
        continue;
    }
    $args = array_map('intval', array_slice($matches, 1));
    call_user_func_array($handler, $args);
    exit;
}

if ($pathMatched) {
    // Known page, wrong method (e.g. opening a delete URL directly)
    redirect('/' . preg_replace('#/(\d+)/(delete|edit)$#', '', $request_uri));
}

notFound();
