<!-- /controllers/routes.php -->
<?php
// Routing system

require_once __DIR__ . '/../utils/authMiddleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

checkAuth();

// Get the requested page
$page = $_GET['page'] ?? 'dashboard';

// Define allowed pages
$allowedPages = ['dashboard', 'stockbook'];

// Validate page
if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

// Route to appropriate view
switch ($page) {
    case 'stockbook':
        require_once __DIR__ . '/../views/stockbook.php';
        break;
    case 'dashboard':
    default:
        require_once __DIR__ . '/../views/dashboard.php';
        break;
}
?>