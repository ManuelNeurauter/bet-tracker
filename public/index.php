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
require_once '../src/models/Bookmaker.php';
require_once '../src/models/Tag.php';
require_once '../src/models/Tipster.php';
require_once '../src/models/Sport.php';

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
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];
$base_path = '/';

// Remove base path if needed
if (strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}

// Remove trailing slash
$request_uri = rtrim($request_uri, '/');

// Parse segments
$segments = explode('/', filter_var($request_uri, FILTER_SANITIZE_URL));
$segments = array_filter($segments);
$segments = array_values($segments);

// Router
switch ($segments[0] ?? '') {
    // Auth Routes
    case 'login':
        if ($request_method === 'GET') {
            AuthController::showLogin();
        } elseif ($request_method === 'POST') {
            AuthController::handleLogin();
        }
        break;
    
    case 'register':
        if ($request_method === 'GET') {
            AuthController::showRegister();
        } elseif ($request_method === 'POST') {
            AuthController::handleRegister();
        }
        break;
    
    case 'logout':
        AuthController::handleLogout();
        break;
    
    // Dashboard Route
    case '':
    case 'dashboard':
        DashboardController::index();
        break;
    
    // Bet Routes
    case 'bets':
        if (isset($segments[1])) {
            if ($segments[1] === 'add') {
                if ($request_method === 'GET') {
                    BetController::showAdd();
                } elseif ($request_method === 'POST') {
                    BetController::handleAdd();
                }
            } elseif (is_numeric($segments[1])) {
                $betId = (int)$segments[1];
                if (isset($segments[2])) {
                    if ($segments[2] === 'edit') {
                        if ($request_method === 'GET') {
                            BetController::showEdit($betId);
                        } elseif ($request_method === 'POST') {
                            BetController::handleEdit($betId);
                        }
                    } elseif ($segments[2] === 'delete' && $request_method === 'POST') {
                        BetController::delete($betId);
                    }
                } else {
                    BetController::show($betId);
                }
            }
        } else {
            BetController::list();
        }
        break;
    
    // Statistics Route
    case 'statistics':
        StatisticsController::index();
        break;
    
    // Bookmaker Routes
    case 'bookmakers':
        if (isset($segments[1])) {
            if ($segments[1] === 'add') {
                if ($request_method === 'GET') {
                    BookmakerController::showAdd();
                } elseif ($request_method === 'POST') {
                    BookmakerController::handleAdd();
                }
            } elseif (is_numeric($segments[1])) {
                $bookmakerId = (int)$segments[1];
                if (isset($segments[2])) {
                    if ($segments[2] === 'edit') {
                        if ($request_method === 'GET') {
                            BookmakerController::showEdit($bookmakerId);
                        } elseif ($request_method === 'POST') {
                            BookmakerController::handleEdit($bookmakerId);
                        }
                    } elseif ($segments[2] === 'delete' && $request_method === 'POST') {
                        BookmakerController::delete($bookmakerId);
                    }
                }
            }
        } else {
            BookmakerController::list();
        }
        break;
    
    // Tag Routes
    case 'tags':
        if (isset($segments[1])) {
            if ($segments[1] === 'add') {
                if ($request_method === 'GET') {
                    TagController::showAdd();
                } elseif ($request_method === 'POST') {
                    TagController::handleAdd();
                }
            } elseif (is_numeric($segments[1])) {
                $tagId = (int)$segments[1];
                if (isset($segments[2])) {
                    if ($segments[2] === 'edit') {
                        if ($request_method === 'GET') {
                            TagController::showEdit($tagId);
                        } elseif ($request_method === 'POST') {
                            TagController::handleEdit($tagId);
                        }
                    } elseif ($segments[2] === 'delete' && $request_method === 'POST') {
                        TagController::delete($tagId);
                    }
                }
            }
        } else {
            TagController::list();
        }
        break;
    
    // Tipster Routes
    case 'tipsters':
        if (isset($segments[1])) {
            if ($segments[1] === 'add') {
                if ($request_method === 'GET') {
                    TipsterController::showAdd();
                } elseif ($request_method === 'POST') {
                    TipsterController::handleAdd();
                }
            } elseif (is_numeric($segments[1])) {
                $tipsterId = (int)$segments[1];
                if (isset($segments[2])) {
                    if ($segments[2] === 'edit') {
                        if ($request_method === 'GET') {
                            TipsterController::showEdit($tipsterId);
                        } elseif ($request_method === 'POST') {
                            TipsterController::handleEdit($tipsterId);
                        }
                    } elseif ($segments[2] === 'delete' && $request_method === 'POST') {
                        TipsterController::delete($tipsterId);
                    }
                }
            }
        } else {
            TipsterController::list();
        }
        break;
    
    // Settings Routes
    case 'settings':
        if ($request_method === 'GET') {
            SettingsController::show();
        } elseif ($request_method === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'update-password') {
                SettingsController::handleChangePassword();
            } else {
                SettingsController::handleUpdate();
            }
        }
        break;
    
    // API Routes for AJAX requests
    case 'api':
        require_once '../src/api.php';
        break;
    
    // 404
    default:
        http_response_code(404);
        include '../src/views/404.php';
        break;
}
?>
